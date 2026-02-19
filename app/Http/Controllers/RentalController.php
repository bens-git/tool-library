<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmRentalEmail;
use App\Mail\ConfirmRentalDeletionEmail;
use App\Mail\ConfirmLoanEmail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Notifications\RentalDeletedNotification;

class RentalController extends Controller
{
    /**
     * Check if an item has active rentals.
     */
    public function isItemRented($itemId)
    {
        $hasActiveRental = Rental::where('item_id', $itemId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->exists();

        return response()->json(['data' => $hasActiveRental]);
    }

    /**
     * Store a newly created rental in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item' => 'required|array',
            'item.id' => 'required|exists:items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $itemId = $request->input('item.id');

        // Check if item is already rented (has an active rental)
        $existingRental = Rental::where('item_id', $itemId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($existingRental) {
            return response()->json([
                'message' => 'The item is already rented.'
            ], 409);
        }

        $user = Auth::user();

        // Check if $user is not null before loading location
        if ($user) {
            $user = User::with('location')->find($user->id);
        }

        // Create the rental without date ranges
        $rental = Rental::create([
            'rented_by' => $user->id,
            'item_id' => $itemId,
            'rented_at' => now(),
            'status' => 'booked',
        ]);

        $rental->load('renter:id,discord_username,name,email');

        // Find the item by ID
        /** @var \App\Models\Item|null $item */
        $item = Item::with(['owner', 'location'])
            ->leftJoin('users', 'users.id', '=', 'items.owned_by')
            ->leftJoin('archetypes', 'archetypes.id', '=', 'items.archetype_id')
            ->select('items.*')
            ->selectRaw("
            CONCAT(
                LOWER(REGEXP_REPLACE(LEFT(users.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                LOWER(REGEXP_REPLACE(LEFT(archetypes.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                items.id
            ) AS item_name
        ")
            ->where('items.id', $itemId)
            ->first();

        Log::info($item);

        // Set the rental's location property to the item's location if it exists, otherwise use the user's location
        /** @var \App\Models\Location|null $location */
        $location = $item->location ?? $user->location;
        $rental->location = $location;

        // Send rental confirmation email
        /** @var \App\Models\User $user */
        Mail::to($user->email)->send(new ConfirmRentalEmail($user, $item, $rental));

        // Send loan confirmation email
        /** @var \App\Models\User $owner */
        $owner = $item->owner;
        Mail::to($owner->email)->send(new ConfirmLoanEmail($user, $item, $rental));

        return response()->json([
            'message' => 'Rental successfully created.',
        ], 201);
    }

    /**
     * Get all rentals for a specific user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Fetch rentals associated with the authenticated user
        $rentals = Rental::where('rented_by', $user->id)
            ->with(['item.location', 'user.location']) // Eager load item and user location
            ->get()
            ->map(function ($rental) use ($user) {
                // Check if item has a location, if not, use the user's location
                $location = $rental->item->location ?? $user->location;

                return [
                    'id' => $rental->id,
                    'item_id' => $rental->item_id,
                    'rented_at' => $rental->rented_at,
                    'status' => $rental->status,
                    'location' => [
                        'id' => $location->id,
                        'city' => $location->city,
                        'state' => $location->state,
                        'country' => $location->country,
                        'created_at' => $location->created_at,
                        'updated_at' => $location->updated_at,
                    ],
                ];
            });

        $response['data'] = $rentals;
        $response['total'] = $rentals->count();

        // Return response
        return response()->json($response);
    }

    /**
     * Get all loans for a specific user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserLoans()
    {
        // Get the authenticated user
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Fetch rentals associated with the authenticated user
        /** @var \Illuminate\Database\Eloquent\Collection $loans */
        $loans = Rental::whereHas('item', function ($query) use ($user) {
            $query->where('owned_by', $user->id);
        })
            ->with(['item.location', 'user.location', 'renter.location'])
            ->get()
            ->map(function (Rental $rental): array {
                // Get the item's location, if not available, fallback to the rented_by user's location
                $location = $rental->item->location ?? $rental->user->location;

                return [
                    'id' => $rental->id,
                    'item_id' => $rental->item_id,
                    'rented_at' => $rental->rented_at,
                    'status' => $rental->status,
                    'location' => [
                        'id' => $location->id,
                        'city' => $location->city,
                        'state' => $location->state,
                        'country' => $location->country,
                        'created_at' => $location->created_at,
                        'updated_at' => $location->updated_at,
                    ],
                    'rented_by' => [
                        'id' => $rental->renter->id,
                        'name' => $rental->renter->name,
                        'email' => $rental->renter->email,
                        'location' => [
                            'id' => $rental->renter->location->id,
                            'city' => $rental->renter->location->city,
                            'state' => $rental->renter->location->state,
                            'country' => $rental->renter->location->country,
                            'created_at' => $rental->renter->location->created_at,
                            'updated_at' => $rental->renter->location->updated_at,
                        ],
                    ],
                ];
            });

        return response()->json($loans);
    }

    /**
     * Update the specified rental.
     */
    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'status' => 'required|string|in:booked,active,cancelled,completed,holding',
        ]);

        // Find the rental by ID
        $rental = Rental::find($id);

        if (!$rental) {
            return response()->json(['message' => 'Rental not found'], 404);
        }

        // Update the rental status
        $rental->status = $validatedData['status'];
        $rental->save();

        // Return a JSON response indicating success
        return response()->json([
            'message' => 'Rental status updated successfully',
            'rental' => $rental
        ]);
    }

    /**
     * Remove the specified rental from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the rental by ID
        $rental = Rental::with(['renter'])->find($id);

        // Find the item by ID
        $item = Item::with(['owner', 'location'])
            ->leftJoin('users', 'users.id', '=', 'items.owned_by')
            ->leftJoin('archetypes', 'archetypes.id', '=', 'items.archetype_id')
            ->select('items.*')
            ->selectRaw("
                CONCAT(
                    LOWER(REGEXP_REPLACE(LEFT(users.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                    LOWER(REGEXP_REPLACE(LEFT(archetypes.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                    items.id
                ) AS item_name
            ")
            ->where('items.id', $rental->item_id)
            ->first();

        // Check if the rental exists
        if (!$rental) {
            return response()->json(['message' => 'Rental not found'], 404);
        }

        // Check if the authenticated user is the owner of the rental or the loaner
        if (Auth::id() !== $rental->rented_by && $rental->item->owned_by != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Attempt to delete the rental
        try {
            $rental->delete();

            // Send rental notification
            $item->owner->notify(new RentalDeletedNotification($item, $rental));
            $rental->renter->notify(new RentalDeletedNotification($item, $rental));

            return response()->json(['message' => 'Rental canceled successfully'], 200);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['message' => 'Failed to cancel rental', 'error' => $e->getMessage()], 500);
        }
    }
}

