<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handles user login and returns an access token upon successful authentication.
     *
     * @param Request $request The incoming request containing the login credentials.
     * @return JsonResponse The JSON response indicating success or failure of the login attempt.
     */
    public function login(Request $request): JsonResponse
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        // If validation fails, return a JSON response with validation errors and a 400 status code
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        // Attempt to find the user by email
        $user = User::where('email', $request->input('email'))->first();

        // If the user is not found, return a JSON response with an error message and a 404 status code
        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'User cannot be found.'
            ], 404);
        }

        // Check if the provided password matches the stored hashed password
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong password.'
            ]);
        }

        // Generate an access token for the authenticated user
        $accessToken = $user->createToken('authToken')->accessToken;

        // Return a JSON response indicating successful login along with the access token
        return response()->json([
            'success' => true,
            'message' => 'User logged in successfully.',
            'access_token' => $accessToken,
            'user' => $user
        ]);
    }

}
