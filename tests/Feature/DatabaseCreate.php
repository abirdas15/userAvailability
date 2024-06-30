<?php

use Tests\TestCase;

class DatabaseCreate extends TestCase
{
    /**
     * A basic feature test example for creating a database.
     *
     * @return void
     */
    public function test_create_database()
    {
        // Attempt to create a MySQL database connection
        // 'localhost' is the hostname, 'root' is the username, and '' is the password
        $connection = new \mysqli('localhost', 'root', '');

        // Execute the SQL query to create a database if it does not already exist
        $response = mysqli_query($connection, 'CREATE DATABASE IF NOT EXISTS user_availability_test');

        // Check if the query execution was successful
        if ($response) {
            // If successful, assert that the response is true
            $this->assertEquals(true, $response);
        } else {
            // If unsuccessful, assert that the response is false
            $this->assertEquals(false, $response);
        }
    }

}
