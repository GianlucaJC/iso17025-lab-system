<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Mostra il form di login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Gestisce la richiesta di login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Prepara il client HTTP. La verifica SSL è attiva di default.
            $httpClient = Http::getFacadeRoot();
            $certPath = env('API_CERT_PATH');

            // SOLUZIONE SICURA (RACCOMANDATA): Usa un bundle di certificati personalizzato.
            if ($certPath && file_exists($certPath)) {
                $httpClient = $httpClient->withOptions(['verify' => $certPath]);
            }
            // SOLUZIONE DI SVILUPPO: Disabilita la verifica SSL (meno sicura).
            elseif (filter_var(env('API_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN) === false) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->post(env('API_LOGIN_URL'), [
                'api_token' => env('API_LOGIN_SHARED_SECRET'),
                'username' => $request->username,
                'password' => $request->password,
                'app_name' => 'pannello_testliof'
            ]);
        } catch (ConnectionException $e) {
            // Questo blocco intercetta errori di connessione (es. server non raggiungibile, DNS non risolto).
            $errorMessage = 'Impossibile connettersi al servizio di autenticazione.';
            if (config('app.debug')) {
                $errorMessage .= ' [Debug Info: ' . $e->getMessage() . ']';
            }
            return back()->withErrors(['credentials' => $errorMessage])->withInput();
        }

        if ($response->failed() || !$response->json('success')) {
            // Per il debug, aggiungiamo la risposta completa dell'API all'errore.
            // In questo modo puoi vedere esattamente cosa risponde il server.
            $errorMessage = $response->json('message') ?? 'Credenziali non valide o errore API.';

            // Aggiungiamo l'intera risposta del body solo se il debug è attivo nel file .env (APP_DEBUG=true)
            if (config('app.debug')) {
                $errorMessage .= ' [Debug Info: ' . $response->body() . ']';
            }
            return back()->withErrors(['credentials' => $errorMessage])->withInput();
        }

        $userData = $response->json();

        // Ulteriore controllo di sicurezza: l'utente deve avere un ruolo valido per questa applicazione.
        // Blocchiamo l'accesso se il ruolo è 0 (disabilitato), non è presente o non è valido.
        if (empty($userData['user17025']) || intval($userData['user17025']) <= 0) {
            return back()->withErrors([
                'credentials' => 'L\'utente non è autorizzato ad accedere a questa applicazione.'
            ])->withInput();
        }

        // Login riuscito, salviamo i dati utente in sessione
        Session::put('user', $userData);
        Session::regenerate();

        return redirect()->intended(route('acceptance.index'));
    }

    public function logout(Request $request)
    {
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}