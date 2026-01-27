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

    /**
     * Redirects an admin user to the external user management panel with an SSO token.
     */
    public function redirectToUserManagement()
    {
        $user = Session::get('user');

        // Ensure user is an admin
        if (!isset($user['user17025']) || $user['user17025'] != 1) {
            abort(403, 'Accesso non autorizzato.');
        }

        $secret = env('API_LOGIN_SHARED_SECRET');
        $timestamp = time();
        $token = hash_hmac('sha256', (string) $timestamp, $secret);

        // The target URL for the SSO login handler
        $ssoLoginUrl = env('APP_URL_USER') . "/api_user_liof/sso_login.php?timestamp={$timestamp}&token={$token}";

        // Since the user management is on the same domain, we can just redirect.
        return redirect()->to($ssoLoginUrl);
    }
}