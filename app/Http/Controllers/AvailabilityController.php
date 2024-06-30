<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeeklyAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    /**
     * Sets the weekly availability for a user.
     *
     * @param Request $request The incoming request containing availability data.
     * @return JsonResponse The JSON response indicating success or failure.
     */
    public function setAvailability(Request $request): JsonResponse
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // If validation fails, return a JSON response with errors and a 401 status code
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 401);
        }

        // Find the user by ID
        $user = User::find($request['sessionUser']['id']);

        // If user not found, return a JSON response with an error message and a 404 status code
        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'User cannot be found.'
            ], 404);
        }

        // Create Carbon instances for the start and end times, using the user's timezone
        $startTime = Carbon::createFromFormat('H:i', $request->input('start_time'), $user['timezone'] ?? 'UTC');
        $endTime = Carbon::createFromFormat('H:i', $request->input('end_time'), $user['timezone'] ?? 'UTC');

        // Create a new WeeklyAvailability instance and set its properties
        $availabilityModel = new WeeklyAvailability();
        $availabilityModel->user_id = $request['sessionUser']['id'];
        $availabilityModel->day_of_week = $request->input('day_of_week');
        $availabilityModel->start_time = $startTime;
        $availabilityModel->end_time = $endTime;

        // Attempt to save the availability model to the database
        if (!$availabilityModel->save()) {
            // If save fails, return a JSON response with an error message and a 401 status code
            return response()->json([
                'success' => false,
                'message' => 'Weekly availability cannot be saved.'
            ], 401);
        }

        // If save is successful, return a JSON response indicating success with a 200 status code
        return response()->json([
            'success' => true,
            'message' => 'Weekly availability has been saved.'
        ], 200);
    }

    /**
     * Retrieves the weekly availability for a user.
     *
     * @param Request $request The incoming request.
     * @param int $userId The ID of the user whose availability is being requested.
     * @return JsonResponse The JSON response containing the availability data or an error message.
     */
    public function getAvailability(Request $request, int $userId): JsonResponse
    {
        // Find the user by ID
        $user = User::find($userId);

        // If the user is not found, return a JSON response with an error message and a 404 status code
        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'User cannot be found.'
            ], 404);
        }

        // Get the user's timezone, defaulting to 'UTC' if not set
        $userTimezone = $user['timezone'] ?? 'UTC';

        // Retrieve all weekly availabilities for the user
        $availabilities = WeeklyAvailability::where('user_id', $userId)->get();

        // Determine the requester's timezone based on their IP address
        $buyerTimezone = self::getTimezoneByIp($request->ip());

        // Map over the availabilities to convert start and end times to the requester's timezone
        $result = $availabilities->map(function ($availability) use ($buyerTimezone, $userTimezone) {
            // Convert start time from user timezone to buyer timezone
            $start = Carbon::createFromTimeString($availability->start_time, $userTimezone)
                ->setTimezone($userTimezone)
                ->setTimezone($buyerTimezone);

            // Convert end time from user timezone to buyer timezone
            $end = Carbon::createFromTimeString($availability->end_time, $userTimezone)
                ->setTimezone($userTimezone)
                ->setTimezone($buyerTimezone);

            // Return the converted availability information
            return [
                'day_of_week' => ucfirst($availability->day_of_week),
                'start_time' => $start->format('h:i A'),
                'end_time' => $end->format('h:i A'),
            ];
        });

        // Return a JSON response with the converted availability data and a success message
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Retrieves the timezone of a user based on their IP address using an external API.
     *
     * @param string $ipAddress The IP address of the user.
     * @return string The timezone of the user, or 'UTC' if the timezone cannot be determined.
     */
    public static function getTimezoneByIp(string $ipAddress): string
    {
        try {
            // Initialize cURL session
            $curl = curl_init();

            // Set cURL options
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://ip-api.com/json/'.$ipAddress, // API endpoint with IP address
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            // Execute cURL request and store the response
            $response = curl_exec($curl);

            // Close cURL session
            curl_close($curl);

            // Decode the JSON response
            $response = json_decode($response);

            // Check if the API request was successful
            if ($response->status == 'success') {
                return $response->timezone; // Return the timezone from the API response
            }

            return 'UTC'; // Return 'UTC' if the API request was not successful or timezone is not available
        } catch (\Exception $exception) {
            // Log any exceptions that occur during the API request
            Log::error('Cannot find buyer timezone:'.$exception->getMessage());

            return 'UTC'; // Return 'UTC' if an exception occurs during the API request
        }
    }


}
