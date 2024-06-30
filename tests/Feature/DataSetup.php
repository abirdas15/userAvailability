<?php

use App\Models\User;
use Tests\TestCase;
class DataSetup extends TestCase
{
    public function test_data_import()
    {
        // Run the DatabaseSeeder...
        $this->seed();

        // Add passport
        $this->artisan('passport:install', ['--force' => true]);


        /** Tests from seeded data **/
        // Test user.
        $user = User::where('email', 'john@doe.com')->first();
        $this->assertEquals('John Doe', $user['name']);
        $this->assertEquals('john@doe.com', $user['email']);
    }
}
