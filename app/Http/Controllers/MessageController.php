<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagePoll;
use App\Models\MessagePollOption;
use App\Models\MessagePollVote;
use App\Models\MessageReaction;
use App\Models\Usage;
use App\Models\User;
use App\Notifications\NewPollNotification;
use App\Notifications\NewPrivateMessageNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class MessageController extends Controller
{
    /**
     * Get public feed posts.
     */
    public function publicFeed(): JsonResponse
    {
        $user = Auth::user();

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
            ->with('poll.options')
            ->with('reactions.user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $messages->getCollection()->transform(function ($message) use ($user) {
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

                'poll' => $message->poll
                    ? $this->formatPoll($message->poll, $user->id)
                    : null,

                'reactions' => $this->formatMessageReactions($message),
            ];
        });

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
            ->with(['latestMessage.user', 'participants', 'usage.item'])
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
                    'usage_id' => $conversation->usage_id,
                    'other_participant' => $otherParticipant ? [
                        'id' => $otherParticipant->id,
                        'name' => $otherParticipant->name,
                        'email' => $otherParticipant->email,
                    ] : null,
                    'usage' => $conversation->usage ? [
                        'id' => $conversation->usage->id,
                        'item' => [
                            'id' => $conversation->usage->item->id,
                            'name' => $conversation->usage->item->name ?? $conversation->usage->item->archetype?->name,
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
            ->with('poll.options')
            ->with('reactions.user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($user) {
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
'poll' => $message->poll ? $this->formatPoll($message->poll, $user->id) : null,
                    'reactions' => $this->formatMessageReactions($message),
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
                'usage_id' => $conversation->usage_id,
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

        // Send email notification to other participants about the new private message
        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->get();
        
        if ($otherParticipants->isNotEmpty()) {
            Notification::send($otherParticipants, new NewPrivateMessageNotification($message, $user));
        }

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
            'usage_id' => 'nullable|exists:usages,id',
            'initial_message' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $targetUserId = $request->input('user_id');
        $usageId = $request->input('usage_id');

        // Can't start conversation with yourself
        if ($user->id === $targetUserId) {
            return response()->json(['message' => 'Cannot start conversation with yourself'], 400);
        }

        // Check if conversation already exists for this usage
        if ($usageId) {
            $existingConversation = Conversation::where('usage_id', $usageId)
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
            'usage_id' => $usageId,
        ]);

        // Add participants
        $conversation->participants()->attach([$user->id, $targetUserId]);

        // Add initial message if provided and notify the recipient
        if ($request->filled('initial_message')) {
            $initialMessage = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => $request->input('initial_message'),
                'is_system_message' => false,
            ]);

            // Send email notification to the recipient about the new private message
            $targetUser = User::find($targetUserId);
            if ($targetUser) {
                $targetUser->notify(new NewPrivateMessageNotification($initialMessage, $user));
            }
        }

        return response()->json([
            'message' => 'Conversation started successfully',
            'conversation_id' => $conversation->id,
        ], 201);
    }

    /**
     * create conversation for a Get or usage.
     */
    public function getUsageConversation(int $usageId): JsonResponse
    {
        $user = Auth::user();

        $usage = Usage::with('item.owner')->find($usageId);

        if (!$usage) {
            return response()->json(['message' => 'Usage not found'], 404);
        }

        // Check if user is either the renter or the owner
        if ($usage->used_by !== $user->id && $usage->item->owned_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find or create conversation
        $conversation = Conversation::where('usage_id', $usageId)
            ->where('type', 'private')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
                'usage_id' => $usageId,
            ]);

            // Add participants
            $conversation->participants()->attach([$usage->used_by, $usage->item->owned_by]);
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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $count = $user->unreadMessageCount();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Update user's last community visit timestamp.
     */
    public function markCommunityVisited(): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $user->updateLastCommunityVisit();

        // Refresh the user model to get the updated timestamp
        $user->refresh();

        return response()->json(['message' => 'Community visit updated', 'last_visit' => $user->last_community_visit]);
    }

    // ============ POLL METHODS ============

    /**
     * Create a poll attached to a message.
     */
    public function createPoll(Request $request, int $messageId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:500',
            'options' => 'required|array|min:2|max:10',
            'options.*' => 'required|string|max:200',
            'is_multiple_choice' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $message = Message::find($messageId);

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        // Check if user can create poll on this message
        if (!$this->canUserInteractWithMessage($message, $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if poll already exists on this message
        if ($message->poll()->exists()) {
            return response()->json(['message' => 'Poll already exists on this message'], 400);
        }

        $poll = MessagePoll::create([
            'message_id' => $messageId,
            'question' => $request->input('question'),
            'is_multiple_choice' => $request->input('is_multiple_choice', false),
            'expires_at' => $request->input('expires_at'),
        ]);

        // Create options
        $options = [];
        foreach ($request->input('options') as $optionText) {
            $options[] = MessagePollOption::create([
                'poll_id' => $poll->id,
                'option_text' => $optionText,
            ]);
        }

        $poll->load('options');

        // Send email notification to all users about the new poll
        $allUsers = User::where('id', '!=', $user->id)->get();
        Notification::send($allUsers, new NewPollNotification($poll, $message));

        return response()->json([
            'message' => 'Poll created successfully',
            'poll' => $this->formatPoll($poll, $user->id),
        ], 201);
    }

    /**
     * Vote on a poll.
     */
    public function votePoll(Request $request, int $pollId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'option_ids' => 'required|array|min:1',
            'option_ids.*' => 'integer|exists:message_poll_options,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $poll = MessagePoll::with('options')->find($pollId);

        if (!$poll) {
            return response()->json(['message' => 'Poll not found'], 404);
        }

        // Check if user can vote
        if (!$poll->canVote($user->id)) {
            return response()->json(['message' => 'Cannot vote on this poll'], 400);
        }

        // Check if user has already voted on this poll (prevent multiple votes)
        if ($poll->hasUserVoted($user->id)) {
            return response()->json(['message' => 'You have already voted on this poll'], 400);
        }

        // Validate that all option_ids belong to this poll
        $validOptionIds = $poll->options->pluck('id')->toArray();
        foreach ($request->input('option_ids') as $optionId) {
            if (!in_array($optionId, $validOptionIds)) {
                return response()->json(['message' => 'Invalid option ID'], 400);
            }
        }

        // Check multiple choice constraint
        if (!$poll->is_multiple_choice && count($request->input('option_ids')) > 1) {
            return response()->json(['message' => 'This poll only allows single choice'], 400);
        }

        // Add new votes
        foreach ($request->input('option_ids') as $optionId) {
            MessagePollVote::create([
                'poll_id' => $pollId,
                'option_id' => $optionId,
                'user_id' => $user->id,
            ]);

            // Increment vote count
            MessagePollOption::where('id', $optionId)->increment('vote_count');
        }

        $poll->refresh();
        $poll->load('options');

        return response()->json([
            'message' => 'Vote recorded successfully',
            'poll' => $this->formatPoll($poll, $user->id),
        ]);
    }

    /**
     * Get poll details.
     */
    public function getPoll(int $pollId): JsonResponse
    {
        $user = Auth::user();
        $poll = MessagePoll::with('options')->find($pollId);

        if (!$poll) {
            return response()->json(['message' => 'Poll not found'], 404);
        }

        return response()->json([
            'poll' => $this->formatPoll($poll, $user->id),
        ]);
    }

    /**
     * Close a poll.
     */
    public function closePoll(int $pollId): JsonResponse
    {
        $user = Auth::user();
        $poll = MessagePoll::find($pollId);

        if (!$poll) {
            return response()->json(['message' => 'Poll not found'], 404);
        }

        // Check if user owns the message
        if ($poll->message->user_id !== $user->id) {
            return response()->json(['message' => 'Only the message author can close the poll'], 403);
        }

        $poll->update(['is_closed' => true]);

        return response()->json([
            'message' => 'Poll closed successfully',
            'poll' => $this->formatPoll($poll, $user->id),
        ]);
    }

    // ============ REACTION METHODS ============

    /**
     * Add or update a reaction on a message.
     */
    public function addReaction(Request $request, int $messageId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $message = Message::find($messageId);

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        // Check if user can interact with this message
        if (!$this->canUserInteractWithMessage($message, $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $emoji = $request->input('emoji');

        // Check if reaction already exists
        $existingReaction = MessageReaction::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existingReaction) {
            // Reaction already exists, return success
            return response()->json([
                'message' => 'Reaction already exists',
                'reactions' => $this->getFormattedReactions($message),
            ]);
        }

        // Remove existing reaction with different emoji (if any)
        MessageReaction::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->delete();

        // Create new reaction
        MessageReaction::create([
            'message_id' => $messageId,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);

        return response()->json([
            'message' => 'Reaction added successfully',
            'reactions' => $this->getFormattedReactions($message),
        ], 201);
    }

    /**
     * Remove a reaction from a message.
     */
    public function removeReaction(Request $request, int $messageId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $message = Message::find($messageId);

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        MessageReaction::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->where('emoji', $request->input('emoji'))
            ->delete();

        return response()->json([
            'message' => 'Reaction removed successfully',
            'reactions' => $this->getFormattedReactions($message),
        ]);
    }

    // ============ HELPER METHODS ============

    /**
     * Check if user can interact with a message (is participant in conversation).
     */
    private function canUserInteractWithMessage(Message $message, User $user): bool
    {
        $conversation = $message->conversation;

        // For public conversations, anyone can interact
        if ($conversation && $conversation->type === 'public') {
            return true;
        }

        // For private conversations, must be a participant
        return $conversation && $conversation->hasParticipant($user->id);
    }

    /**
     * Format a poll for JSON response.
     */
    private function formatPoll(MessagePoll $poll, int $userId): array
    {
        $options = $poll->options->map(function ($option) use ($userId) {
            return [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'vote_count' => $option->vote_count,
                'has_voted' => $option->hasUserVoted($userId),
            ];
        });

        $totalVotes = $options->sum('vote_count');

        return [
            'id' => $poll->id,
            'message_id' => $poll->message_id,
            'question' => $poll->question,
            'is_multiple_choice' => $poll->is_multiple_choice,
            'is_closed' => $poll->is_closed,
            'expires_at' => $poll->expires_at,
            'has_expired' => $poll->hasExpired(),
            'has_user_voted' => $poll->hasUserVoted($userId),
            'user_vote_ids' => $poll->getUserVotes($userId),
            'total_votes' => $totalVotes,
            'options' => $options,
            'can_vote' => $poll->canVote($userId),
        ];
    }

    /**
     * Get formatted reactions for a message.
     */
    private function getFormattedReactions(Message $message): array
    {
        $reactions = $message->reactions()->with('user')->get();

        return $reactions->groupBy('emoji')->map(function ($grouped, $emoji) {
            return [
                'emoji' => $emoji,
                'count' => $grouped->count(),
                'user_ids' => $grouped->pluck('user_id')->toArray(),
                'user_names' => $grouped->pluck('user.name')->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * Format reactions for a message model.
     */
    private function formatMessageReactions($message): array
    {
        if (!$message->reactions || $message->reactions->isEmpty()) {
            return [];
        }

        return $message->reactions->groupBy('emoji')->map(function ($grouped, $emoji) {
            return [
                'emoji' => $emoji,
                'count' => $grouped->count(),
                'user_ids' => $grouped->pluck('user_id')->toArray(),
                'user_names' => $grouped->pluck('user.name')->toArray(),
            ];
        })->values()->toArray();
    }
}
