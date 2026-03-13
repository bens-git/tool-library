<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usage;
use App\Models\Item;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmUsageEmail;
use App\Mail\ConfirmUsageDeletionEmail;
use App\Mail\ConfirmLoanEmail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Notifications\UsageDeletedNotification;
use Inertia\Inertia;

class UsageController extends Controller
{
    /**
     * Check if an item has active usages.
     */
    public function isItemUsed($itemId)
    {
        $hasActiveUsage = Usage::where('item_id', $itemId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->exists();

        return response()->json(['data' => $hasActiveUsage]);
    }

    /**
     * Store a newly created usage in storage.
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

        // Prevent users from using their own items
        if ($item->owned_by === Auth::id()) {
            return response()->json([
                'message' => 'You cannot use your own item.'
            ], 422);
        }

        // Check if item is already used (has an active usage)
        $existingUsage = Usage::where('item_id', $itemId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($existingUsage) {
            return response()->json([
                'message' => 'The item is already used.'
            ], 409);
        }

        $user = Auth::user();

        // Create the usage
        $usage = Usage::create([
            'used_by' => $user->id,
            'item_id' => $itemId,
            'used_at' => now(),
            'status' => 'booked',
        ]);
        $usage->load('user:id,name,email');

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

        // Send usage confirmation email
        /** @var \App\Models\User $user */
        Mail::to($user->email)->send(new ConfirmUsageEmail($user, $item, $usage));

        // Send loan confirmation email
        /** @var \App\Models\User $owner */
        $owner = $item->owner;
        Mail::to($owner->email)->send(new ConfirmLoanEmail($user, $item, $usage));

        // Create a private conversation for this usage
        $conversation = Conversation::create([
            'type' => 'private',
            'usage_id' => $usage->id,
        ]);

        // Add both participants (user and owner)
        $conversation->participants()->attach([$user->id, $owner->id]);

        // Get item name for messages
        $itemName = $item->name ?? $item->archetype->name ?? 'the item';

        // Send system message to user
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => "Hi! You've used {$itemName}. Please contact {$owner->name} to arrange pickup/drop-off.",
            'is_system_message' => true,
        ]);

        // Send system message to owner
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
            'body' => "Hi! {$user->name} has used your {$itemName}. Please contact them to arrange pickup/drop-off.",
            'is_system_message' => true,
        ]);

// Return JSON with redirect for Inertia/AJAX frontend handling
        return response()->json([
            'success' => true,
            'message' => 'Usage booked successfully! Check My Usage page.',
            'redirect_url' => route('my-usage'),
            'usage' => $usage->load(['item', 'item.owner'])
        ]);
    }

    /**
     * Get all usages for a specific user.
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

        // Fetch usages associated with the authenticated user
        $usages = Usage::where('used_by', $user->id)
            ->with(['item', 'item.owner', 'conversation'])
            ->get()
            ->map(function ($usage) {
                return [
                    'id' => $usage->id,
                    'item_id' => $usage->item_id,
                    'used_at' => $usage->used_at,
                    'status' => $usage->status,
                    'item' => $usage->item ? [
                        'id' => $usage->item->id,
                        'name' => $usage->item->name ?? $usage->item->archetype?->name,
                        'archetype' => $usage->item->archetype ? [
                            'name' => $usage->item->archetype->name,
                        ] : null,
                    ] : null,
                    'owner' => $usage->item && $usage->item->owner ? [
                        'id' => $usage->item->owner->id,
                        'name' => $usage->item->owner->name,
                        'email' => $usage->item->owner->email,
                    ] : null,
                    'conversation_id' => $usage->conversation?->id,
                ];
            });

        $response['data'] = $usages;
        $response['total'] = $usages->count();

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

        // Fetch usages associated with the authenticated user
        /** @var \Illuminate\Database\Eloquent\Collection $loans */
        $loans = Usage::whereHas('item', function ($query) use ($user) {
            $query->where('owned_by', $user->id);
        })
            ->with(['item', 'item.owner', 'user', 'conversation'])
            ->get()
            ->map(function (Usage $usage): array {
                return [
                    'id' => $usage->id,
                    'item_id' => $usage->item_id,
                    'used_at' => $usage->used_at,
                    'status' => $usage->status,
                    'item' => $usage->item ? [
                        'id' => $usage->item->id,
                        'name' => $usage->item->name ?? $usage->item->archetype?->name,
                        'archetype' => $usage->item->archetype ? [
                            'name' => $usage->item->archetype->name,
                        ] : null,
                    ] : null,
                    'user' => $usage->user ? [
                        'id' => $usage->user->id,
                        'name' => $usage->user->name,
                        'email' => $usage->user->email,
                    ] : null,
                    'conversation_id' => $usage->conversation?->id,
                ];
            });

        return response()->json($loans);
    }

    /**
     * Update the specified usage.
     */
    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'status' => 'required|string|in:booked,active,cancelled,completed,holding',
        ]);

        // Find the usage by ID
        $usage = Usage::find($id);

        if (!$usage) {
            return response()->json(['message' => 'Usage not found'], 404);
        }

        // Update the usage status
        $usage->status = $validatedData['status'];
        $usage->save();

        // Return a JSON response indicating success
        return response()->json([
            'message' => 'Usage status updated successfully',
            'usage' => $usage
        ]);
    }

    /**
     * Remove the specified usage from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Find the usage by ID
        $usage = Usage::with(['user', 'conversation'])->find($id);

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
            ->where('items.id', $usage->item_id)
            ->first();

        // Check if the usage exists
        if (!$usage) {
            return response()->json(['message' => 'Usage not found'], 404);
        }

        // Check if the authenticated user is the owner of the usage or the loaner
        if (Auth::id() !== $usage->used_by && $usage->item->owned_by != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get item name for the message
        $itemName = $item->name ?? $item->archetype->name ?? 'the item';
        
        // Get the current user who is cancelling
        $cancellingUser = Auth::user();
        $cancellerName = $cancellingUser->name;
        
        // Determine who cancelled (user or owner)
        $isUserCancelling = Auth::id() === $usage->used_by;
        $cancellerRole = $isUserCancelling ? 'user' : 'owner';

        // Attempt to delete the usage
        try {
            // Add system message to conversation before deleting the usage
            if ($usage->conversation) {
                Message::create([
                    'conversation_id' => $usage->conversation->id,
                    'user_id' => $cancellingUser->id,
                    'body' => "This usage for {$itemName} has been cancelled by {$cancellerName} ({$cancellerRole}).",
                    'is_system_message' => true,
                ]);
            }

            $usage->delete();

            // Send usage notification
            $item->owner->notify(new UsageDeletedNotification($item, $usage));
            $usage->user->notify(new UsageDeletedNotification($item, $usage));

            return response()->json(['message' => 'Usage canceled successfully'], 200);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json(['message' => 'Failed to cancel usage', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if an item is rented (has active usage).
     *
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function isItemRented($itemId)
    {
        $hasActiveUsage = Usage::where('item_id', $itemId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->exists();

        return response()->json(['data' => $hasActiveUsage]);
    }
}

