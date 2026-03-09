<?php

use App\Http\Controllers\ArchetypeController;
use App\Http\Controllers\CreditVoteController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItcController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsageController;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Customize redirect for authenticated users - go to library-catalog instead of home
RedirectIfAuthenticated::redirectUsing(function ($request) {
    return route('library-catalog');
});

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/items/featured', [ItemController::class, 'featured'])->name('items.featured');

Route::get('landing-page', function () {
    return Inertia::render('LandingPage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('landing-page');

Route::get('/library-catalog', function () {
    return Inertia::render('LibraryCatalog');
})->middleware(['auth', 'verified'])->name('library-catalog');



Route::get('/my-offerings', function () {
    return Inertia::render('MyOfferings');
})->middleware(['auth', 'verified'])->name('my-offerings');

Route::get('/my-usage', function () {
    return Inertia::render('MyUsage');
})->middleware(['auth', 'verified'])->name('my-usage');

// ITC Pages
Route::get('/itc', function () {
    return Inertia::render('ItcDashboard');
})->middleware(['auth', 'verified'])->name('itc');

Route::get('/itc/info', function () {
    return Inertia::render('ItcInfo');
})->middleware(['auth', 'verified'])->name('itc.info');

Route::get('/credit-voting', function () {
    return Inertia::render('CreditVoting');
})->middleware(['auth', 'verified'])->name('credit-voting');

// Messages Pages
Route::get('/messages', function () {
    return Inertia::render('MessagesList');
})->middleware(['auth', 'verified'])->name('messages');

Route::get('/community', function () {
    return Inertia::render('PublicFeed');
})->middleware(['auth', 'verified'])->name('community');


Route::get('/archetypes', [ArchetypeController::class, 'index'])
    ->middleware(['auth'])
    ->name('archetypes.index');

Route::get('/archetypes/resources', [ArchetypeController::class, 'getResources'])
    ->middleware(['auth'])
    ->name('archetypes.resources');

Route::post('/archetypes', [ArchetypeController::class, 'store'])
    ->middleware(['auth'])
    ->name('archetypes.store');

Route::get('/archetypes/{id}', [ArchetypeController::class, 'show'])
    ->middleware(['auth'])
    ->name('archetypes.show');

Route::put('/archetypes/{id}', [ArchetypeController::class, 'update'])
    ->middleware(['auth'])
    ->name('archetypes.update');

Route::delete('/archetypes/{id}', [ArchetypeController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('archetypes.destroy');

Route::middleware('auth')->group(function () {
    Route::resource('items', ItemController::class);
});

// Custom item routes
Route::middleware('auth')->group(function () {
    Route::get('/items/{itemId}/is-rented', [UsageController::class, 'isItemRented'])
        ->name('item.is-rented');
    Route::post('/items/{itemId}/images', [ItemController::class, 'storeImage'])
        ->name('item-images.store');
});


Route::middleware('auth')->group(function () {
    Route::resource('usages', UsageController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/me/usages', [UsageController::class, 'index'])
        ->name('me.usages.index');
    
    Route::get('/me/loans', [UsageController::class, 'getUserLoans'])
        ->name('me.loans.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/logout-page', function () {
        return Inertia::render('LogoutPage');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ITC Routes - Time Credit System
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/itc/dashboard', [ItcController::class, 'dashboard'])->name('itc.dashboard');
    
    // Balance
    Route::get('/itc/balance', [ItcController::class, 'balance'])->name('itc.balance');
    
    // Transactions
    Route::get('/itc/transactions', [ItcController::class, 'transactions'])->name('itc.transactions');
    
    // Stats
    Route::get('/itc/stats', [ItcController::class, 'stats'])->name('itc.stats');
    
    // Check balance
    Route::post('/itc/check-balance', [ItcController::class, 'checkBalance'])->name('itc.check-balance');
});

// Credit Vote Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Cast vote (now for archetypes)
    Route::post('/votes', [CreditVoteController::class, 'store'])->name('votes.store');
    
    // Get archetype votes
    Route::get('/archetypes/{archetypeId}/votes', [CreditVoteController::class, 'archetypeVotes'])->name('votes.archetype');
    
    // Get user's vote for archetype
    Route::get('/archetypes/{archetypeId}/my-vote', [CreditVoteController::class, 'userVote'])->name('votes.user');
    
    // Get user's all votes
    Route::get('/votes/my-votes', [CreditVoteController::class, 'myVotes'])->name('votes.my');
    
    // Check if can vote on archetype
    Route::get('/archetypes/{archetypeId}/can-vote', [CreditVoteController::class, 'canVote'])->name('votes.can-vote');
});

// Message Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Public feed
    Route::get('/messages/public', [MessageController::class, 'publicFeed'])->name('messages.public');
    Route::post('/messages/public', [MessageController::class, 'createPublicPost'])->name('messages.public.create');
    
    // Private conversations
    Route::get('/messages/conversations', [MessageController::class, 'conversations'])->name('messages.conversations');
    Route::post('/messages/conversations', [MessageController::class, 'startConversation'])->name('messages.conversations.start');
    Route::get('/messages/conversations/{conversationId}', [MessageController::class, 'show'])->name('messages.conversations.show');
    Route::post('/messages/conversations/{conversationId}', [MessageController::class, 'store'])->name('messages.conversations.store');
    
    // Usage conversation
    Route::get('/messages/usage/{usageId}', [MessageController::class, 'getUsageConversation'])->name('messages.usage');
    
    // Unread count
    Route::get('/messages/unread', [MessageController::class, 'unreadCount'])->name('messages.unread');
    
    // Mark community visited
    Route::post('/messages/community/visited', [MessageController::class, 'markCommunityVisited'])->name('messages.community.visited');

    // Poll routes
    Route::post('/messages/{messageId}/poll', [MessageController::class, 'createPoll'])->name('messages.poll.create');
    Route::post('/polls/{pollId}/vote', [MessageController::class, 'votePoll'])->name('messages.poll.vote');
    Route::get('/polls/{pollId}', [MessageController::class, 'getPoll'])->name('messages.poll.show');
    Route::post('/polls/{pollId}/close', [MessageController::class, 'closePoll'])->name('messages.poll.close');

    // Reaction routes
    Route::post('/messages/{messageId}/reaction', [MessageController::class, 'addReaction'])->name('messages.reaction.add');
    Route::delete('/messages/{messageId}/reaction', [MessageController::class, 'removeReaction'])->name('messages.reaction.remove');
});



require __DIR__ . '/auth.php';

// Catch-all route for non-existent routes - redirect to landing page
Route::get('/{any}', function () {
    return redirect('/');
})->where('any', '.+');
