<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcceptanceController; // Aggiungi questo in cima al file
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

// Rotta per il reindirizzamento SSO alla gestione utenti
Route::get('/user-management/redirect', [AuthController::class, 'redirectToUserManagement'])->name('user-management.redirect')->middleware('auth.api');

// Rotte Protette


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

Route::get('/acceptance/{acceptance}/pdf', [AcceptanceController::class, 'generatePdf'])->name('acceptance.pdf');    

// Rotte per i test specifici
use App\Http\Controllers\TestAController;
use App\Http\Controllers\TestBController;
use App\Http\Controllers\TestCController;

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

Route::post('/test-a/{test_a_result}/sign', [TestAController::class, 'sign'])
    ->name('test-a.sign')
    ->middleware('auth.api');

Route::post('/test-a/{test_a_result}/validate', [TestAController::class, 'validateTest'])
    ->name('test-a.validate')
    ->middleware('auth.api');

Route::get('/acceptance/{acceptance}/test-b/create', [TestBController::class, 'create'])
    ->name('test-b.create')
    ->middleware('auth.api');

Route::post('/acceptance/{acceptance}/test-b', [TestBController::class, 'store'])
    ->name('test-b.store')
    ->middleware('auth.api');

Route::get('/test-b/{test_b_result}/edit', [TestBController::class, 'edit'])
    ->name('test-b.edit')
    ->middleware('auth.api');

Route::put('/test-b/{test_b_result}', [TestBController::class, 'update'])
    ->name('test-b.update')
    ->middleware('auth.api');

Route::post('/test-b/{test_b_result}/sign', [TestBController::class, 'sign'])
    ->name('test-b.sign')
    ->middleware('auth.api');

Route::post('/test-b/{test_b_result}/validate', [TestBController::class, 'validateTest'])
    ->name('test-b.validate')
    ->middleware('auth.api');

// Rotte per il Test C
Route::get('/acceptance/{acceptance}/test-c/create', [TestCController::class, 'create'])
    ->name('test-c.create')
    ->middleware('auth.api');

Route::post('/acceptance/{acceptance}/test-c', [TestCController::class, 'store'])
    ->name('test-c.store')
    ->middleware('auth.api');

Route::get('/test-c/{test_c_result}/edit', [TestCController::class, 'edit'])
    ->name('test-c.edit')
    ->middleware('auth.api');

Route::put('/test-c/{test_c_result}', [TestCController::class, 'update'])
    ->name('test-c.update')
    ->middleware('auth.api');

Route::post('/test-c/{test_c_result}/sign', [TestCController::class, 'sign'])
    ->name('test-c.sign')
    ->middleware('auth.api');

Route::post('/test-c/{test_c_result}/validate', [TestCController::class, 'validateTest'])
    ->name('test-c.validate')
    ->middleware('auth.api');

// Rotte per la gestione degli strumenti (solo Admin)
use App\Http\Controllers\InstrumentController;
Route::middleware(['auth.api'])->group(function () {
    Route::get('/instruments', [InstrumentController::class, 'index'])->name('instruments.index');
    // Route::post('/instruments/type', [InstrumentController::class, 'storeInstrument'])->name('instruments.storeType'); // Se si volesse aggiungere tipi dinamicamente
    Route::post('/instruments/item', [InstrumentController::class, 'storeItem'])->name('instruments.storeItem');
    Route::put('/instruments/item/{item}', [InstrumentController::class, 'updateItem'])->name('instruments.updateItem');
    Route::delete('/instruments/item/{item}', [InstrumentController::class, 'destroyItem'])->name('instruments.destroyItem');
});


// In routes/web.php

// ... altre rotte ...

Route::middleware(['auth.session'])->group(function () {
    // ...
    
    // Method Revisions Management (Admin only)
    Route::get('/methods', [App\Http\Controllers\MethodRevisionController::class, 'index'])->name('methods.index');
    Route::put('/methods', [App\Http\Controllers\MethodRevisionController::class, 'update'])->name('methods.update');

    // ... altre rotte ...
});

// Rotta per la cronologia delle modifiche
use App\Http\Controllers\AuditController;
Route::get('/history/{modelNameShort}/{id}', [AuditController::class, 'show'])
    ->name('history.show')
    ->middleware('auth.api');
