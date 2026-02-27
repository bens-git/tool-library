<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\Item;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
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
use Inertia\Inertia;

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

        // Get the item to check ownership
        $item = Item::find($itemId);
        
        if (!$item) {
            return response()->json([
                'message' => 'Item not found.'
            ], 404);
        }

        // Prevent users from renting their own items
        if ($item->owned_by === Auth::id()) {
            return response()->json([
                'message' => 'You cannot rent your own item.'
            ], 422);
        }

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

        // Create the rental
        $rental = Rental::create([
            'rented_by' => $user->id,
            'item_id' => $itemId,
            'rented_at' => now(),
            'status' => 'booked',
        ]);
        $rental->load('renter:id,name,email');

        // Find the item by ID
        /** @var \App\Models\Item|null $item */
        $item = Item::with(['owner'])
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

        // Send rental confirmation email
        /** @var \App\Models\User $user */
        Mail::to($user->email)->send(new ConfirmRentalEmail($user, $item, $rental));

        // Send loan confirmation email
        /** @var \App\Models\User $owner */
        $owner = $item->owner;
        Mail::to($owner->email)->send(new ConfirmLoanEmail($user, $item, $rental));

        // Create a private conversation for this rental
        $conversation = Conversation::create([
            'type' => 'private',
            'rental_id' => $rental->id,
        ]);

        // Add both participants (renter and owner)
        $conversation->participants()->attach([$user->id, $owner->id]);

        // Get item name for messages
        $itemName = $item->name ?? $item->archetype?->name ?? 'the item';

        // Send system message to renter
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => "Hi! You've rented {$itemName}. Please contact {$owner->name} to arrange pickup/drop-off.",
            'is_system_message' => true,
        ]);

        // Send system message to owner
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
            'body' => "Hi! {$user->name} has rented your {$itemName}. Please contact them to arrange pickup/drop-off.",
            'is_system_message' => true,
        ]);

        // Return redirect to my-rentals page
        return redirect()->route('my-rentals');
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
            ->with(['item', 'item.owner', 'conversation'])
            ->get()
            ->map(function ($rental) {
                return [
                    'id' => $rental->id,
                    'item_id' => $rental->item_id,
                    'rented_at' => $rental->rented_at,
                    'status' => $rental->status,
                    'item' => $rental->item ? [
                        'id' => $rental->item->id,
                        'name' => $rental->item->name ?? $rental->item->archetype?->name,
                        'archetype' => $rental->item->archetype ? [
                            'name' => $rental->item->archetype->name,
                        ] : null,
                    ] : null,
                    'owner' => $rental->item && $rental->item->owner ? [
                        'id' => $rental->item->owner->id,
                        'name' => $rental->item->owner->name,
                        'email' => $rental->item->owner->email,
                    ] : null,
                    'conversation_id' => $rental->conversation?->id,
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
            ->with(['item', 'item.owner', 'renter', 'conversation'])
            ->get()
            ->map(function (Rental $rental): array {
                return [
                    'id' => $rental->id,
                    'item_id' => $rental->item_id,
                    'rented_at' => $rental->rented_at,
                    'status' => $rental->status,
                    'item' => $rental->item ? [
                        'id' => $rental->item->id,
                        'name' => $rental->item->name ?? $rental->item->archetype?->name,
                        'archetype' => $rental->item->archetype ? [
                            'name' => $rental->item->archetype->name,
                        ] : null,
                    ] : null,
                    'renter' => $rental->renter ? [
                        'id' => $rental->renter->id,
                        'name' => $rental->renter->name,
                        'email' => $rental->renter->email,
                    ] : null,
                    'conversation_id' => $rental->conversation?->id,
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
        $rental = Rental::with(['renter', 'conversation'])->find($id);

        // Find the item by ID
        $item = Item::with(['owner'])
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

        // Get item name for the message
        $itemName = $item->name ?? $item->archetype?->name ?? 'the item';
        
        // Get the current user who is cancelling
        $cancellingUser = Auth::user();
        $cancellerName = $cancellingUser->name;
        
        // Determine who cancelled (renter or owner)
        $isRenterCancelling = Auth::id() === $rental->rented_by;
        $cancellerRole = $isRenterCancelling ? 'renter' : 'owner';

        // Attempt to delete the rental
        try {
            // Add system message to conversation before deleting the rental
            if ($rental->conversation) {
                Message::create([
                    'conversation_id' => $rental->conversation->id,
                    'user_id' => $cancellingUser->id,
                    'body' => "This rental for {$itemName} has been cancelled by {$cancellerName} ({$cancellerRole}).",
                    'is_system_message' => true,
                ]);
            }

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

