routes yo iko terbaru :

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| USER CRUD (Public / bisa kamu beri middleware nanti)
|--------------------------------------------------------------------------
*/

Route::get('/users',        [UserController::class, 'index']);
Route::get('/users/{id}',   [UserController::class, 'show']);
Route::post('/users',       [UserController::class, 'store']);
Route::put('/users/{id}',   [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| AUTH API
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // profile user login
    Route::get('/profile', [AuthController::class, 'apiProfile']);

    // logout
    Route::post('/logout', [AuthController::class, 'apiLogout']);

    // user bawaan sanctum (opsional)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});