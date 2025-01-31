<?php

use Elminson\DQL\DatabaseQueryLogger;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
	// Set up the database connection
	$db = new DB;
	$db->addConnection([
						   'driver' => 'sqlite',
						   'database' => ':memory:',
					   ]);
	$db->setAsGlobal();
	$db->bootEloquent();

	// Create a test table
	DB::schema()->create('users', function (Blueprint $table) {
		$table->increments('id');
		$table->string('email');
	});

	// Insert a test record
	DB::table('users')->insert([
								   'email' => 'example@example.com',
							   ]);
});

it('dumps the SQL query with print = false and return = false', function () {
	$logger = new DatabaseQueryLogger();
	$query = DB::table('users')->where('email', 'example@example.com');

	// Capture the output of ddQuery
	$output = "";
	try {
		$output = $logger->logQuery($query, false, false);
	} catch (Exception $e) {
		dd($e->getMessage());
	}

	expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('tests log_query with print = false and return = false', function () {
	$query = DB::table('users')->where('email', 'example@example.com');

	// Capture the output of log_query
	ob_start();
	try {
		log_query($query, false, false);
	} catch (Exception $e) {
		dd($e->getMessage());
	}
	$output = ob_get_clean();
	expect($output)->toBe('');
});

it('tests log_query with print = true and return = false', function () {
	$query = DB::table('users')->where('email', 'example@example.com');

	// Capture the output of log_query
	ob_start();
	try {
		log_query($query, true, false);
	} catch (Exception $e) {
		dd($e->getMessage());
	}
	$output = ob_get_clean();
	expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('tests log_query with print = false and return = true', function () {
	$query = DB::table('users')->where('email', 'example@example.com');

	// Capture the output of log_query
	$result = "";
	try {
		$result = log_query($query, false, true);
	} catch (Exception $e) {
		dd($e->getMessage());
	}
	expect($result)->toBe('select * from "users" where "email" = \'example@example.com\'');
});

it('tests log_query with print = true and return = true', function () {
	$query = DB::table('users')->where('email', 'example@example.com');

	// Capture the output of log_query
	ob_start();
	$result = "";
	try {
		$result = log_query($query, true, true);
	} catch (Exception $e) {
		dd($e->getMessage());
	}
	$output = ob_get_clean();
	expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
	expect($result)->toBe('select * from "users" where "email" = \'example@example.com\'');
});
