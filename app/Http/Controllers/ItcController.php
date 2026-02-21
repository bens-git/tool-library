<?php

namespace App\Http\Controllers;

use App\Models\ItcLedger;
use App\Models\User;
use App\Services\ItcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ItcController extends Controller
{
    protected ItcService $itcService;

    public function __construct(ItcService $itcService)
    {
        $this->itcService = $itcService;
    }

    /**
     * Get current user's ITC balance and stats
     */
    public function balance()
    {
        $user = Auth::user();
        
        $balance = $this->itcService->getOrCreateBalance($user);
        $stats = $this->itcService->getUserStats($user);

        return response()->json([
            'balance' => $balance->balance,
            'lifetime_earned' => $balance->lifetime_earned,
            'lifetime_spent' => $balance->lifetime_spent,
            'stats' => $stats,
        ]);
    }

    /**
     * Get transaction history
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        $limit = $request->input('limit', 50);
        $type = $request->input('type');
        
        $query = ItcLedger::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $transactions = $query->limit($limit)->get();

        return response()->json([
            'transactions' => $transactions->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'category' => $t->category,
                    'amount' => $t->amount,
                    'balance_after' => $t->balance_after,
                    'description' => $t->description,
                    'item_id' => $t->item_id,
                    'rental_id' => $t->rental_id,
                    'created_at' => $t->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get user stats summary
     */
    public function stats()
    {
        $user = Auth::user();
        $stats = $this->itcService->getUserStats($user);

        return response()->json($stats);
    }

    /**
     * Check if user has enough credits for a rental
     */
    public function checkBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $amount = $request->input('amount');
        
        $hasEnough = $this->itcService->hasEnoughCredits($user, $amount);
        $balance = $this->itcService->getBalance($user);

        return response()->json([
            'has_enough' => $hasEnough,
            'balance' => $balance,
            'required' => $amount,
            'deficit' => max(0, $amount - $balance),
        ]);
    }

    /**
     * Get ITC dashboard data
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        $balance = $this->itcService->getOrCreateBalance($user);
        $stats = $this->itcService->getUserStats($user);
        
        // Recent transactions
        $recentTransactions = ItcLedger::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'category' => $t->category,
                    'amount' => $t->amount,
                    'description' => $t->description,
                    'created_at' => $t->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'balance' => $balance->balance,
            'lifetime_earned' => $balance->lifetime_earned,
            'lifetime_spent' => $balance->lifetime_spent,
            'stats' => $stats,
            'recent_transactions' => $recentTransactions,
        ]);
    }
}

