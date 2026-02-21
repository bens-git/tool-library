<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use App\Services\CreditVoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditVoteController extends Controller
{
    protected CreditVoteService $voteService;

    public function __construct(CreditVoteService $voteService)
    {
        $this->voteService = $voteService;
    }

    /**
     * Cast or update a vote on an item's credit rate
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'vote_value' => 'required|numeric|min:0.1|max:10',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $item = Item::findOrFail($request->input('item_id'));
        
        $result = $this->voteService->castVote(
            $user,
            $item,
            $request->input('vote_value'),
            $request->input('reason')
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'vote' => $result['vote'],
            'new_rate' => $result['new_rate'],
        ]);
    }

    /**
     * Get votes for an item
     */
    public function itemVotes($itemId)
    {
        $item = Item::findOrFail($itemId);
        
        $votes = $this->voteService->getItemVotes($item);
        $stats = $this->voteService->getItemVoteStats($item);

        return response()->json([
            'votes' => $votes,
            'stats' => $stats,
        ]);
    }

    /**
     * Get user's vote for a specific item
     */
    public function userVote($itemId)
    {
        $user = Auth::user();
        $item = Item::findOrFail($itemId);
        
        $vote = $this->voteService->getUserVoteForItem($user, $item);
        $canVote = $this->voteService->canVote($user, $item);

        return response()->json([
            'has_voted' => $vote !== null,
            'vote' => $vote ? [
                'id' => $vote->id,
                'vote_value' => $vote->vote_value,
                'reason' => $vote->reason,
                'created_at' => $vote->created_at->toIso8601String(),
            ] : null,
            'can_vote' => $canVote['can_vote'],
            'cooldown_days' => $canVote['cooldown_days'] ?? null,
        ]);
    }

    /**
     * Get user's voted items
     */
    public function myVotes()
    {
        $user = Auth::user();
        $votedItems = $this->voteService->getUserVotedItems($user);

        return response()->json([
            'items' => $votedItems,
        ]);
    }

    /**
     * Check if user can vote on an item
     */
    public function canVote($itemId)
    {
        $user = Auth::user();
        $item = Item::findOrFail($itemId);
        
        $result = $this->voteService->canVote($user, $item);

        return response()->json($result);
    }
}

