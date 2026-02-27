<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
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
     * Cast or update a vote on an archetype's credit rate
     */
    public function store(Request $request)
    {
        $request->validate([
            'archetype_id' => 'required|exists:archetypes,id',
            'vote_value' => 'required|numeric|min:0.1|max:10',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $archetype = Archetype::findOrFail($request->input('archetype_id'));
        
        $result = $this->voteService->castArchetypeVote(
            $user,
            $archetype,
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
     * Get votes for an archetype
     */
    public function archetypeVotes($archetypeId)
    {
        $archetype = Archetype::findOrFail($archetypeId);
        
        $votes = $this->voteService->getArchetypeVotes($archetype);
        $stats = $this->voteService->getArchetypeVoteStats($archetype);

        return response()->json([
            'votes' => $votes,
            'stats' => $stats,
        ]);
    }

    /**
     * Get user's vote for a specific archetype
     */
    public function userVote($archetypeId)
    {
        $user = Auth::user();
        $archetype = Archetype::findOrFail($archetypeId);
        
        $vote = $this->voteService->getUserVoteForArchetype($user, $archetype);
        $canVote = $this->voteService->canVoteOnArchetype($user, $archetype);

        return response()->json([
            'has_vote' => $vote !== null,
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
     * Get user's voted archetypes
     */
    public function myVotes()
    {
        $user = Auth::user();
        $votedArchetypes = $this->voteService->getUserVotedArchetypes($user);

        return response()->json([
            'archetypes' => $votedArchetypes,
        ]);
    }

    /**
     * Check if user can vote on an archetype
     */
    public function canVote($archetypeId)
    {
        $user = Auth::user();
        $archetype = Archetype::findOrFail($archetypeId);
        
        $result = $this->voteService->canVoteOnArchetype($user, $archetype);

        return response()->json($result);
    }
}

