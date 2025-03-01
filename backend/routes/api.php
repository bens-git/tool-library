<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ArchetypeController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\JobController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



//Authentication routes
// Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

Route::middleware(['api', 'web'])->post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/request-password-reset', [AuthController::class, 'requestPasswordReset']);
Route::put('/user/password-with-token', [AuthController::class, 'resetPasswordWithToken']);


Route::get('/items/{id}', [ItemController::class, 'show']);
Route::get('/archetypes-with-items', [ArchetypeController::class, 'getArchetypesWithItems']);
Route::get('/archetypes', [ArchetypeController::class, 'index']);
Route::get('/archetypes/{id}', [ArchetypeController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/usages', [ArchetypeController::class, 'getUsages']);
Route::get('/brands', [BrandController::class, 'getUsages']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/brands/{id}', [BrandController::class, 'destroy']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
    Route::delete('/archetypes/{id}', [ArchetypeController::class, 'destroy']);
    Route::delete('/usages/{id}', [UsageController::class, 'destroy']);
    Route::delete('/user', [AuthController::class, 'deleteUser']);
    Route::delete('/user/rentals/{id}', [RentalController::class, 'destroy']);

    Route::get('/items/{id}/rented-dates', [RentalController::class, 'getItemRentedDates']);
    Route::get('/items/{id}/unavailable-dates', [ItemController::class, 'getItemUnavailableDates']);
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/rented-dates', [RentalController::class, 'getRentedDates']);
    Route::get('/me/brands', [BrandController::class, 'index'])->name('user.brands');
    Route::get('/me/categories', [CategoryController::class, 'getMyCategories'])->name('user.categories');
    Route::get('/me/items', [ItemController::class, 'index'])->name('user.items');
    Route::get('/user/loans', [RentalController::class, 'getUserLoans']);
    Route::get('/me/location', [AuthController::class, 'getLocation']);
    Route::get('/user/rentals', [RentalController::class, 'getUserRentals']);
    Route::get('/me/archetypes', [ArchetypeController::class, 'index'])->name('user.archetypes');
    Route::get('/me/usages', [UsageController::class, 'getMyUsages'])->name('user.usages');
    Route::get('/resources', [ArchetypeController::class, 'getResources']);

    Route::patch('/items/{id}/availability', [ItemController::class, 'updateItemAvailability']);
    Route::patch('/rentals/{id}', [RentalController::class, 'update']);

    Route::post('/brands', [BrandController::class, 'store']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::post('/items/{id}/image', [ItemController::class, 'storeImage']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::post('/link-with-discord', [AuthController::class, 'linkWithDiscord']);
    Route::post('/rentals', [RentalController::class, 'bookRental']);
    Route::post('/archetypes', [ArchetypeController::class, 'store']);
    Route::post('/update-category/{id}', [CategoryController::class, 'update']);
    Route::post('/update-usage/{id}', [UsageController::class, 'update']);
    Route::post('/usages', [UsageController::class, 'store']);
    
    Route::put('/user/{userId}', [AuthController::class, 'update']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::put('/user/location/{locationId}', [AuthController::class, 'updateLocation']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::put('/usages/{id}', [UsageController::class, 'update']);
    Route::put('/archetypes/{id}', [ArchetypeController::class, 'update']);
    Route::put('/items/{id}', [ItemController::class, 'update']);
    Route::put('/brands/{id}', [BrandController::class, 'update']);
});
