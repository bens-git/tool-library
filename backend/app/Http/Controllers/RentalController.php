<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\Item;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmRentalEmail;
use App\Mail\ConfirmRentalDeletionEmail;
use App\Mail\ConfirmLoanEmail;
use Illuminate\Support\Facades\Log;

class RentalController extends Controller
{
    /**
     * Get rented dates for a specific item type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRentedDates(Request $request)
    {
        $typeId = $request->query('typeId');

        // Fetch rentals for the specified typeId
        $rentals = Rental::whereHas('item', function ($query) use ($typeId) {
            $query->where('type_id', $typeId); // Assuming 'type_id' exists in items table
        })->get(['starts_at', 'ends_at']); // Adjust the columns as needed

        // Format dates if needed
        $rentedDates = $rentals->map(function ($rental) {
            return [
                'start' => Carbon::parse($rental->starts_at)->format('Y-m-d'),
                'end' => Carbon::parse($rental->ends_at)->format('Y-m-d'),
            ];
        });

        return response()->json(['data' => $rentedDates]);
    }

    public function getItemRentedDates(Request $request)
    {
        $itemId = $request->query('itemId');

        // Fetch rentals for the specified typeId
        $rentals = Rental::where('item_id', '=', $itemId)->get(['starts_at', 'ends_at']); // Adjust the columns as needed

        // Format dates if needed
        $rentedDates = $rentals->map(function ($rental) {
            return [
                'start' => Carbon::parse($rental->starts_at)->format('Y-m-d'),
                'end' => Carbon::parse($rental->ends_at)->format('Y-m-d'),
            ];
        });

        return response()->json(['data' => $rentedDates]);
    }


    public function bookRental(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'itemId' => 'required|exists:items,id',
            'startDate' => 'required|date_format:Y-m-d H:i:s',
            'endDate' => 'required|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item_id = $request->input('itemId');
        $start_date = $request->input('startDate');
        $end_date = $request->input('endDate');

        // Check for conflicts with existing rentals
        $existingRental = Rental::where('item_id', $item_id)
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('starts_at', [$start_date, $end_date])
                    ->orWhereBetween('ends_at', [$start_date, $end_date])
                    ->orWhereRaw('? BETWEEN starts_at AND ends_at', [$start_date])
                    ->orWhereRaw('? BETWEEN starts_at AND ends_at', [$end_date]);
            })
            ->exists();

        if ($existingRental) {
            return response()->json([
                'message' => 'The item is already rented within the requested date range.'
            ], 409);
        }

        $user = Auth::user();

        // Check if $user is not null before loading location
        if ($user) {
            $user = User::with('location')->find($user->id);
        }

        // Create the rental for the entire date range
        $rental = Rental::create([
            'rented_by' => $user->id,
            'item_id' => $item_id,
            'rented_at' => now(),
            'starts_at' => $start_date,
            'ends_at' => $end_date,
            'status' => 'booked', // or any other initial status
        ]);

        // Find the item by ID
        $item = Item::with(['owner', 'location'])
            ->leftJoin('users', 'users.id', '=', 'items.owned_by')
            ->leftJoin('types', 'types.id', '=', 'items.type_id')
            ->select('items.*') // Select all columns from the items table
            ->selectRaw("
            CONCAT(
                LOWER(REGEXP_REPLACE(LEFT(users.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                LOWER(REGEXP_REPLACE(LEFT(types.name, 3), '[^a-zA-Z0-9]', '')), '_', 
                items.id
            ) AS item_name
        ")
            ->where('items.id', $item_id)
            ->first();



        Log::info($item);

        // Set the rental's location property to the item's location if it exists, otherwise use the user's location
        $rental->location = $item->location ?? $user->location;

        // Send rental confirmation email
        /** @var \App\Models\User $user */
        Mail::to($user->email)->send(new ConfirmRentalEmail($user, $item, $rental));

        //send load confirmation email
        Mail::to($item->owner->email)->send(new ConfirmLoanEmail($item->owner, $user, $item, $rental));

        return response()->json([
            'message' => 'Rental successfully created.',
            'rental' => $rental,
        ], 201);
    }

    /**
     * Get all rentals for a specific user.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserRentals()
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
                    'starts_at' => $rental->starts_at,
                    'ends_at' => $rental->ends_at,
                    'status' => $rental->status,
                    'location' => [
                        'id' => $location->id,
                        'city' => $location->city,
                        'state' => $location->state,
                        'country' => $location->country,
                        'created_at' => $location->created_at,
                        'updated_at' => $location->updated_at,
                    ],
                    // You can include other rental fields here as needed
                ];
            });

        return response()->json($rentals);
    }




    /**
     * Get all loans for a specific user.
     *
     * @param  int  $userId
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
        $loans = Rental::whereHas('item', function ($query) use ($user) {
            $query->where('owned_by', $user->id);
        })
            ->with(['item.location', 'user.location', 'renter.location']) // Eager load item, user location, and renter user location
            ->get()
            ->map(function ($rental) {
                // Get the item's location, if not available, fallback to the rented_by user's location
                $location = $rental->item->location ?? $rental->user->location;

                return [
                    'id' => $rental->id,
                    'item_id' => $rental->item_id,
                    'starts_at' => $rental->starts_at,
                    'ends_at' => $rental->ends_at,
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
                    // Include other rental fields here as needed
                ];
            });

        return response()->json($loans);
    }


    /**
     * Update the specified rental.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Rental $rental
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Find the rental by ID

        $rental = Rental::with(['renter'])->find($id);

        // Find the item by ID
        $item = Item::with(['owner', 'location'])
            ->leftJoin('users', 'users.id', '=', 'items.owned_by')
            ->leftJoin('types', 'types.id', '=', 'items.type_id')
            ->select('items.*') // Select all columns from the items table
            ->selectRaw("
          CONCAT(
              LOWER(REGEXP_REPLACE(LEFT(users.name, 3), '[^a-zA-Z0-9]', '')), '_', 
              LOWER(REGEXP_REPLACE(LEFT(types.name, 3), '[^a-zA-Z0-9]', '')), '_', 
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

            // Send rental confirmation email
            /** @var \App\Models\User $user */
            Mail::to($item->owner->email)->send(new ConfirmRentalDeletionEmail($item, $rental));
            Mail::to($rental->renter->email)->send(new ConfirmRentalDeletionEmail($item, $rental));



            return response()->json(['message' => 'Rental canceled successfully'], 200);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['message' => 'Failed to cancel rental', 'error' => $e->getMessage()], 500);
        }
    }
}
