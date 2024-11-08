<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ValidateUserEmail;
use App\Mail\PasswordResetEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class Coordinates
{
    public $latitude;
    public $longitude;

    public function __construct($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
}

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'building_name' => 'nullable|string|max:255',
            'floor_number' => 'nullable|string|max:50',
            'unit_number' => 'nullable|string|max:50',
        ]);

        // Check if the email already exists with an email_verification_token
        $existingUser = User::where('email', $validatedData['email'])
            ->whereNotNull('email_verification_token')
            ->first();

        if ($existingUser) {
            // Update existing user's email_verification_token
            $existingUser->email_verification_token = Str::random(60);
            $existingUser->save();

            // Send new validation email
            Mail::to($existingUser->email)->send(new ValidateUserEmail($existingUser));

            return response()->json(['message' => 'A user with this email is already registered but has not verified their email. A new verification email has been sent.']);
        }

        // Validate that the email is unique for new registrations
        $request->validate([
            'email' => 'unique:users',
        ]);

        // Use a geocoding service to get latitude and longitude
        $geoData = $this->getCoordinatesFromAddress($validatedData);
        $latitude = $geoData->latitude;
        $longitude = $geoData->longitude;


        // Create a new location
        $location = Location::create([
            'street_address' => $validatedData['street_address'],
            'city' => $validatedData['city'],
            'state' => $validatedData['state'],
            'country' => $validatedData['country'],
            'postal_code' => $validatedData['postal_code'],
            'building_name' => $validatedData['building_name'] ?? null,
            'floor_number' => $validatedData['floor_number'] ?? null,
            'unit_number' => $validatedData['unit_number'] ?? null,
            'latitude' => $latitude ?? null,
            'longitude' => $longitude ?? null,
        ]);

        // Create a new user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'location_id' => $location->id,
            'password' => Hash::make($validatedData['password']),
            'email_verification_token' => Str::random(60),
        ]);


        // Send validation email
        Mail::to($user->email)->send(new ValidateUserEmail($user));

        return response()->json(['message' => 'User registered successfully. Please check your email to validate your account.']);
    }


    public function requestPasswordReset(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'email' => 'required|string|email|max:255',
            ]);

            // Check if the email already exists with a verified email
            $existingUser = User::where('email', $validatedData['email'])
                ->whereNotNull('email_verified_at')
                ->first();

            if (!$existingUser) {
                return response()->json(['message' => 'User not found.', 'error' => 'User not found.'], 500);
            } else {

                //set password reset token
                $existingUser->password_reset_token = Str::random(60);
                $existingUser->save();


                // Send new validation email
                Mail::to($existingUser->email)->send(new PasswordResetEmail($existingUser));

                return response()->json(['message' => 'A password reset email has been sent to ' . $existingUser->email . '.']);
            }
        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['message' => 'Failed to register', 'error' => $e->getMessage()], 500);
        }
    }

    private function getCoordinatesFromAddress($data)
    {
        // Construct the full address
        $address = urlencode("{$data['city']}, {$data['state']},  {$data['country']}}");

        // Make the request to the Nominatim API
        $url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

        $client = new Client();

        // Make the request
        $response = $client->request('GET', $url, [
            'headers' => [
                'User-Agent' => 'YourAppName/1.0 (your-email@example.com)'
            ]
        ]);

        // Get the body and decode the JSON
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        // Check if any result is returned
        if (!empty($data) && isset($data[0])) {
            return new Coordinates($data[0]['lat'], $data[0]['lon']);
        }
        // Return a fallback or throw an exception if no result is found
        throw new \Exception("Coordinates not found for the given address.");
    }

    public function validateEmail($token)
    {
        $user = User::where('email_verification_token', $token)->first();


        if (!$user) {
            return redirect()->away(config('app.front_end_url') . '/email-verified?error=true');
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();
        Auth::login($user);

        return redirect()->away(config('app.front_end_url') . '/email-verified?success=true');
    }




    public function resetPasswordWithToken(Request $request)
    {
        $user = User::where('password_reset_token', $request->token)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 403);
        }


        // Validate the incoming request
        $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'], // Ensure the new password is at least 8 characters and matches confirmation
        ]);


        // Update the password
        $user->password = Hash::make($request->input('new_password'));
        $user->password_reset_token = null;
        /** @var \App\Models\User $user */
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([['message' => 'Invalid credentials']], 401);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        // Revoke the current token that was used for authentication
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $userId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'required|exists:locations,id',

        ]);

        // Update the user fields
        $user->name = $validatedData['name'];
        $user->location_id = $validatedData['location_id'];

        // Save the updated user and location
        /** @var \App\Models\User $user */
        $user->save();

        // Return the updated user data
        return response()->json($user);
    }

    /**
     * Update the authenticated user's associated location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocation(Request $request, $locationId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the location belongs to the user
        if ($user->location_id !== (int) $locationId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate the request data
        $validatedData = $request->validate([
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'building_name' => 'nullable|string|max:255',
            'floor_number' => 'nullable|integer',
            'unit_number' => 'nullable|integer',
        ]);

        // Find the location and update it
        $location = Location::findOrFail($locationId);
        $location->update($validatedData);

        // Return the updated location data
        return response()->json($location);
    }


    /**
     * Get the authenticated user's location.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocation()
    {
        // Get the authenticated user
        $user = Auth::user();


        // Check if the user has an associated location
        if ($user && $user->location) {
            return response()->json([
                'success' => true,
                'location' => $user->location
            ]);
        }

        // If the user has no associated location, return a not found response
        return response()->json([
            'success' => false,
            'message' => 'Location not found'
        ], 404);
    }

    public function deleteUser()
    {
        $user = Auth::user();


        /** @var \App\Models\User $user */
        $user->delete();
        return response()->json(['message' => 'User deleted successfully.']);
    }
    /**
     * Update the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Validate the incoming request
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'], // Ensure the new password is at least 8 characters and matches confirmation
        ]);

        // Check if the current password is correct
        if (!Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match our records.'],
            ]);
        }

        // Update the password
        $user->password = Hash::make($request->input('new_password'));
        /** @var \App\Models\User $user */
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }


    public function linkWithDiscord(Request $request)
    {
        $code = $request->code;
        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        //  return config('app.discord_redirect_uri');
        $response = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => config('app.discord_client_id'),
            'client_secret' => config('app.discord_client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('app.discord_redirect_uri'),
        ]);

        $data = $response->json();

        if (!isset($data['access_token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Fetch user info from Discord
        $userResponse = Http::withToken($data['access_token'])->get('https://discord.com/api/users/@me');

        $discordUser = $userResponse->json();


        if (isset($discordUser['id'])) {

            //if logged in, associate this discord user with the logged in user
            $user = User::find(Auth::id());


            if ($user) {


                $user->discord_user_id = $discordUser['id'];
                $user->discord_username = $discordUser['username'];
                $user->discord_discriminator = $discordUser['discriminator'];
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Discord Linked',
                ], 200);
            }
        }


        if (!isset($data['access_token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
    }
}
