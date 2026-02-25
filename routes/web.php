<?php

use App\Http\Controllers\ArchetypeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditVoteController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItcController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\UsageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/archetype-list', function () {
    return Inertia::render('ArchetypeList');
})->middleware(['auth', 'verified'])->name('archetype-list');



Route::get('/my-rentals', function () {
    return Inertia::render('MyRentals');
})->middleware(['auth', 'verified'])->name('my-rentals');

Route::get('/my-loans', function () {
    return Inertia::render('MyLoans');
})->middleware(['auth', 'verified'])->name('my-loans');

// ITC Pages
Route::get('/itc', function () {
    return Inertia::render('ItcDashboard');
})->middleware(['auth', 'verified'])->name('itc');

Route::get('/credit-voting', function () {
    return Inertia::render('CreditVoting');
})->middleware(['auth', 'verified'])->name('credit-voting');

// Messages Pages
Route::get('/messages', function () {
    return Inertia::render('Messages');
})->middleware(['auth', 'verified'])->name('messages');

Route::get('/community', function () {
    return Inertia::render('PublicFeed');
})->middleware(['auth', 'verified'])->name('community');


Route::get('/archetypes', [ArchetypeController::class, 'index'])
    ->middleware(['auth'])
    ->name('archetypes.index');

Route::middleware('auth')->group(function () {
    Route::resource('brands', BrandController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('items', ItemController::class);
});

// Custom item routes
Route::middleware('auth')->group(function () {
    Route::get('/items/{itemId}/is-rented', [RentalController::class, 'isItemRented'])
        ->name('item.is-rented');
    Route::get('/items/{itemId}/unavailable-dates', [ItemController::class, 'getItemUnavailableDates'])
        ->name('item.index-unavailable-dates');
    Route::post('/items/{itemId}/images', [ItemController::class, 'storeImage'])
        ->name('item-images.store');
});


Route::middleware('auth')->group(function () {
    Route::resource('rentals', RentalController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('usages', UsageController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/me/rentals', [RentalController::class, 'index'])
        ->name('me.rentals.index');
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
    // Cast vote
    Route::post('/votes', [CreditVoteController::class, 'store'])->name('votes.store');
    
    // Get item votes
    Route::get('/items/{itemId}/votes', [CreditVoteController::class, 'itemVotes'])->name('votes.item');
    
    // Get user's vote for item
    Route::get('/items/{itemId}/my-vote', [CreditVoteController::class, 'userVote'])->name('votes.user');
    
    // Get user's all votes
    Route::get('/votes/my-votes', [CreditVoteController::class, 'myVotes'])->name('votes.my');
    
    // Check if can vote
    Route::get('/items/{itemId}/can-vote', [CreditVoteController::class, 'canVote'])->name('votes.can-vote');
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
    
    // Rental conversation
    Route::get('/messages/rental/{rentalId}', [MessageController::class, 'getRentalConversation'])->name('messages.rental');
    
    // Unread count
    Route::get('/messages/unread', [MessageController::class, 'unreadCount'])->name('messages.unread');
});



require __DIR__ . '/auth.php';
