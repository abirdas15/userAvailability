<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataCleanup extends TestCase
{
    /**
     * A basic feature test example for cleaning up data in the database.
     *
     * @return void
     */
    public function test_cleanup_data()
    {
        // Delete all tables in the database, retaining the schema (data only).
        $this->artisan('db:wipe');

        // Clear and reset the database, re-running all migrations (data only).
        $this->artisan('migrate:fresh');

        // Retrieve all records from the 'users' table to check if it's empty.
        $users = DB::table('users')->get();

        // Decode the retrieved data to an associative array.
        $response = json_decode($users, true);

        // Assert that the 'users' table is empty after clearing the database.
        $this->assertEquals([], $response);

        // Uncomment the following line to dump and die, which helps in debugging.
        // dd($response);
    }

}
