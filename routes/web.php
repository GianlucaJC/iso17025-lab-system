<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Rotte di Autenticazione
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest.api');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Rotte Protette
Route::middleware('auth.api')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
});

use App\Http\Controllers\AcceptanceController; // Aggiungi questo in cima al file

// ... altre tue rotte ...

// Rotte per la gestione delle accettazioni
Route::get('/acceptance/create', [AcceptanceController::class, 'create'])
    ->name('acceptance.create')
    ->middleware('auth.api'); // Proteggiamo la rotta
