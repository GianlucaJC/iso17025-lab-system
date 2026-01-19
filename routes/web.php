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
use App\Http\Controllers\AcceptanceController; // Aggiungi questo in cima al file

// ... altre tue rotte ...

// Rotte per la gestione delle accettazioni
Route::get('/acceptance/create', [AcceptanceController::class, 'create'])
    ->name('acceptance.create')
    ->middleware('auth.api'); // Proteggiamo la rotta

Route::get('/acceptances', [AcceptanceController::class, 'index'])
    ->name('acceptance.index')
    ->middleware('auth.api');

Route::post('/acceptance', [AcceptanceController::class, 'store'])
    ->name('acceptance.store')
    ->middleware('auth.api'); // Proteggiamo la rotta

// Rotte per la modifica delle accettazioni
Route::get('/acceptance/{acceptance}/edit', [AcceptanceController::class, 'edit'])
    ->name('acceptance.edit')
    ->middleware('auth.api');

Route::put('/acceptance/{acceptance}', [AcceptanceController::class, 'update'])
    ->name('acceptance.update')
    ->middleware('auth.api');

// Rotte per i test specifici
use App\Http\Controllers\TestAController;

Route::get('/acceptance/{acceptance}/test-a/create', [TestAController::class, 'create'])
    ->name('test-a.create')
    ->middleware('auth.api');

Route::post('/acceptance/{acceptance}/test-a', [TestAController::class, 'store'])
    ->name('test-a.store')
    ->middleware('auth.api');

Route::get('/test-a/{test_a_result}/edit', [TestAController::class, 'edit'])
    ->name('test-a.edit')
    ->middleware('auth.api');

Route::put('/test-a/{test_a_result}', [TestAController::class, 'update'])
    ->name('test-a.update')
    ->middleware('auth.api');
