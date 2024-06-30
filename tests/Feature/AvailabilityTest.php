<?php

use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    /**
     * A basic feature test example for user login.
     *
     * @return array The user data containing the access token.
     */
    public function test_user_login(): array
    {
        // Send a POST request to the login endpoint with the specified headers and payload
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->post('/api/v1/auth/login', [
            'email' => 'john@doe.com',
            'password' => '12345678!@',
            'remember' => ''
        ]);

        // Assert that the response status is 200 (OK)
        $response->assertStatus(200);

        // Decode the response content to an associative array
        $userData = json_decode($response->content(), true);

        // Test if the 'access_token' key exists in the user data
        $this->assertArrayHasKey('access_token', $userData);

        // Return the user data
        return $userData;
    }

    /**
     * Create new availability test...
     * @depends test_user_login
     */
    public function test_set_availability(array $userData)
    {
        // Send a POST request to set availability with authorization headers
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $userData['access_token'], // Include the access token in the Authorization header
            'Accept' => 'application/json', // Specify that the response should be in JSON format
        ])->post('/api/v1/availability', [
            'user_id' => $userData['user']['id'], // Provide the user ID
            'day_of_week' => 'monday', // Specify the day of the week for availability
            'start_time' => '09:00', // Set the start time for availability
            'end_time' => '12:00', // Set the end time for availability
        ]);

        // Decode the JSON response content into an array
        $responseNewAvailability = json_decode($response->content(), true);

        // Assert that the response contains a 'message' key
        $this->assertArrayHasKey('message', $responseNewAvailability);

        // Assert that the 'message' value matches the expected success message
        $this->assertEquals('Weekly availability has been saved.', $responseNewAvailability['message']);

        // Return the response for potential further testing
        return $responseNewAvailability;
    }
    /**
     * Get created availability test...
     * @depends test_user_login
     */
    public function test_get_availability()
    {
        // Fetch the first user from the database
        $user = \App\Models\User::first();

        // Send a GET request to fetch availability for the user
        $response = $this->withHeaders([
            'Accept' => 'application/json', // Specify that the response should be in JSON format
        ])->get('/api/v1/availability/' . $user->id, []);

        // Decode the JSON response content into an array
        $response = json_decode($response->content(), true);

        // Assert that the response contains a 'data' key
        $this->assertArrayHasKey('data', $response);
    }

}
