<?php

use App\Http\Controllers\ArchetypeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
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



require __DIR__ . '/auth.php';
