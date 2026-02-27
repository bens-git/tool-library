<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Get public feed posts.
     */
    public function publicFeed(): JsonResponse
    {
        // Find the public conversation
        $publicConversation = Conversation::public()->first();
        
        if (!$publicConversation) {
            // Create public conversation if it doesn't exist
            $publicConversation = Conversation::create([
                'type' => 'public',
            ]);
        }

        // Get all messages from the public conversation
        $messages = Message::where('conversation_id', $publicConversation->id)
            ->where('is_system_message', false)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($messages);
    }

    /**
     * Create a new public post.
     */
    public function createPublicPost(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Find or create public conversation
        $publicConversation = Conversation::public()->first();

        if (!$publicConversation) {
            $publicConversation = Conversation::create([
                'type' => 'public',
            ]);
        }

        $message = Message::create([
            'conversation_id' => $publicConversation->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'is_system_message' => false,
        ]);

        $message->load('user');

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $message,
        ], 201);
    }

    /**
     * Get user's conversations (private only).
     */
    public function conversations(): JsonResponse
    {
        $user = Auth::user();

        $conversations = Conversation::forUser($user->id)
            ->private()
            ->with(['latestMessage.user', 'participants', 'rental.item'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherParticipant = $conversation->getOtherParticipant($user->id);
                
                // Get unread count
                $unreadCount = $conversation->messages()
                    ->where('user_id', '!=', $user->id)
                    ->whereDoesntHave('reads', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->count();

                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'rental_id' => $conversation->rental_id,
                    'other_participant' => $otherParticipant ? [
                        'id' => $otherParticipant->id,
                        'name' => $otherParticipant->name,
                        'email' => $otherParticipant->email,
                    ] : null,
                    'rental' => $conversation->rental ? [
                        'id' => $conversation->rental->id,
                        'item' => [
                            'id' => $conversation->rental->item->id,
                            'name' => $conversation->rental->item->name ?? $conversation->rental->item->archetype?->name,
                        ],
                    ] : null,
                    'latest_message' => $conversation->latestMessage->first() ? [
                        'id' => $conversation->latestMessage->first()->id,
                        'body' => $conversation->latestMessage->first()->body,
                        'user_id' => $conversation->latestMessage->first()->user_id,
                        'created_at' => $conversation->latestMessage->first()->created_at,
                        'is_system_message' => $conversation->latestMessage->first()->is_system_message,
                    ] : null,
                    'unread_count' => $unreadCount,
                    'updated_at' => $conversation->updated_at,
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function show(int $conversationId): JsonResponse
    {
        $user = Auth::user();

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'user_id' => $message->user_id,
                    'body' => $message->body,
                    'is_system_message' => $message->is_system_message,
                    'created_at' => $message->created_at,
                    'user' => $message->user ? [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                    ] : null,
                ];
            });

        // Mark all messages as read
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get()
            ->each(function ($message) use ($user) {
                $message->markAsReadBy($user->id);
            });

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'rental_id' => $conversation->rental_id,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message to a conversation.
     */
    public function store(Request $request, int $conversationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Check if user is a participant
        if (!$conversation->hasParticipant($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'is_system_message' => false,
        ]);

        $message->load('user');

        // Update conversation timestamp
        $conversation->touch();

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'body' => $message->body,
                'is_system_message' => $message->is_system_message,
                'created_at' => $message->created_at,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ],
            ],
        ], 201);
    }

    /**
     * Start a new private conversation with another user.
     */
    public function startConversation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'rental_id' => 'nullable|exists:rentals,id',
            'initial_message' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $targetUserId = $request->input('user_id');
        $rentalId = $request->input('rental_id');

        // Can't start conversation with yourself
        if ($user->id === $targetUserId) {
            return response()->json(['message' => 'Cannot start conversation with yourself'], 400);
        }

        // Check if conversation already exists for this rental
        if ($rentalId) {
            $existingConversation = Conversation::where('rental_id', $rentalId)
                ->where('type', 'private')
                ->first();

            if ($existingConversation) {
                return response()->json([
                    'message' => 'Conversation already exists',
                    'conversation_id' => $existingConversation->id,
                ]);
            }
        }

        // Create new conversation
        $conversation = Conversation::create([
            'type' => 'private',
            'rental_id' => $rentalId,
        ]);

        // Add participants
        $conversation->participants()->attach([$user->id, $targetUserId]);

        // Add initial message if provided
        if ($request->filled('initial_message')) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => $request->input('initial_message'),
                'is_system_message' => false,
            ]);
        }

        return response()->json([
            'message' => 'Conversation started successfully',
            'conversation_id' => $conversation->id,
        ], 201);
    }

    /**
     * create conversation for a Get or rental.
     */
    public function getRentalConversation(int $rentalId): JsonResponse
    {
        $user = Auth::user();

        $rental = Rental::with('item.owner')->find($rentalId);

        if (!$rental) {
            return response()->json(['message' => 'Rental not found'], 404);
        }

        // Check if user is either the renter or the owner
        if ($rental->rented_by !== $user->id && $rental->item->owned_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find or create conversation
        $conversation = Conversation::where('rental_id', $rentalId)
            ->where('type', 'private')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
                'rental_id' => $rentalId,
            ]);

            // Add participants
            $conversation->participants()->attach([$rental->rented_by, $rental->item->owned_by]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Get unread message count for current user.
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();

        $count = $user->unreadMessageCount();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Update user's last community visit timestamp.
     */
    public function markCommunityVisited(): JsonResponse
    {
        $user = Auth::user();
        
        $user->updateLastCommunityVisit();
        
        // Refresh the user model to get the updated timestamp
        $user->refresh();

        return response()->json(['message' => 'Community visit updated', 'last_visit' => $user->last_community_visit]);
    }
}

