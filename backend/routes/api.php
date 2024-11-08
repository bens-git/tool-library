<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UsageController;
use App\Http\Controllers\BrandController;

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
Route::get('/paginated-types', [TypeController::class, 'getPaginatedTypes']);
Route::get('/all-types', [TypeController::class, 'getAllTypes']);
Route::get('/types/{id}', [TypeController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/usages', [TypeController::class, 'getUsages']);
Route::get('/brands', [BrandController::class, 'getUsages']);

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::put('/user/{userId}', [AuthController::class, 'update']);
    Route::put('/user/location/{locationId}', [AuthController::class, 'updateLocation']);
    Route::get('/user/location', [AuthController::class, 'getLocation']);
    Route::get('/user/items', [ItemController::class, 'index'])->name('user.items');
    Route::get('/user/types', [TypeController::class, 'getUserTypes'])->name('user.types');
    Route::get('/user/categories', [CategoryController::class, 'getUserCategories'])->name('user.categories');
    Route::get('/user/usages', [UsageController::class, 'getUserUsages'])->name('user.usages');
    Route::get('/user/brands', [BrandController::class, 'getUserBrands'])->name('user.brands');
    Route::get('/rented-dates', [RentalController::class, 'getRentedDates']);
    Route::get('/item/rented-dates', [RentalController::class, 'getItemRentedDates']);
    Route::get('/item/unavailable-dates', [ItemController::class, 'getItemUnavailableDates']);
    Route::post('/rentals', [RentalController::class, 'bookRental']);
    Route::get('/user/rentals', [RentalController::class, 'getUserRentals']);
    Route::get('/user/loans', [RentalController::class, 'getUserLoans']);
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::post('/types', [TypeController::class, 'store']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::post('/usages', [UsageController::class, 'store']);
    Route::post('/brands', [BrandController::class, 'store']);
    Route::post('/update-item/{id}', [ItemController::class, 'update']);
    Route::post('/update-type/{id}', [TypeController::class, 'update']);
    Route::post('/update-category/{id}', [CategoryController::class, 'update']);
    Route::post('/update-usage/{id}', [UsageController::class, 'update']);
    Route::post('/update-brand/{id}', [BrandController::class, 'update']);
    Route::post('/update-item-availability/{id}', [ItemController::class, 'updateItemAvailability']);
    Route::patch('/rentals/{id}', [RentalController::class, 'update']);
    Route::delete('/user', [AuthController::class, 'deleteUser']);
    Route::delete('/user/rentals/{id}', [RentalController::class, 'destroy']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    Route::delete('/types/{id}', [TypeController::class, 'destroy']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::delete('/usages/{id}', [UsageController::class, 'destroy']);
    Route::delete('/brands/{id}', [BrandController::class, 'destroy']);
    Route::post('/link-with-discord', [AuthController::class, 'linkWithDiscord']);
});
