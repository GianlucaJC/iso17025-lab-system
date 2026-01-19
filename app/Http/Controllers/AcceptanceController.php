<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class AcceptanceController extends Controller
{
    /**
     * Mostra il form per creare una nuova accettazione.
     */
    public function create()
    {
        return view('create');
    }

    /**
     * Mostra l'elenco di tutte le accettazioni.
     */
    public function index()
    {
        // --- Inizio blocco recupero utenti via API ---
        $usersMap = [];
        try {
            // Prepara il client HTTP (logica copiata da AuthController per coerenza)
            $httpClient = Http::getFacadeRoot();
            $certPath = env('API_CERT_PATH');

            if ($certPath && file_exists($certPath)) {
                $httpClient = $httpClient->withOptions(['verify' => $certPath]);
            }
            elseif (filter_var(env('API_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN) === false) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $usersResponse = $httpClient->post(env('API_LOGIN_URL'), [
                'api_token' => env('API_LOGIN_SHARED_SECRET'),
                'action' => 'get_users'
            ]);

            if ($usersResponse->successful() && !empty($usersResponse->json('users'))) {
                // Crea una mappa 'user_id' => ['operatore' => 'Nome Operatore', ...]
                $usersMap = collect($usersResponse->json('users'))->keyBy('id')->all();
            }
        } catch (ConnectionException $e) {
            // In caso di errore di connessione, la mappa utenti sarà vuota.
            // Potremmo loggare l'errore qui per debug futuro.
            // \Log::error("Impossibile recuperare la lista utenti dall'API: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        $acceptances = Acceptance::latest()->get();

        return view('acceptance.index', [
            'acceptances' => $acceptances,
            'usersMap' => $usersMap
        ]);
    }

    /**
     * Salva una nuova accettazione nel database.
     */
    public function store(Request $request)
    {
        $rules = [
            'acceptance_number' => 'required|string|max:255|unique:acceptances,acceptance_number',
            'lotto' => 'required|string|max:255',
            'sampling_date' => 'required|date',
            'acceptance_date' => 'required|date',
            'tests' => 'required|array|min:1', // Almeno un test deve essere selezionato
            'tests.*' => 'string|in:test1,test2,test3', // Valori validi per i test
        ];

        // Regole di validazione condizionali per gli ID delle piastre
        $selectedTests = $request->input('tests', []);

        // Test A (plates 1-5, indices 0-4)
        for ($i = 0; $i < 5; $i++) {
            $rules["plates.{$i}"] = in_array('test1', $selectedTests)
                ? 'required|numeric'
                : 'nullable|numeric';
        }

        // Test B (plates 6-10, indices 5-9)
        for ($i = 5; $i < 10; $i++) {
            $rules["plates.{$i}"] = in_array('test2', $selectedTests)
                ? 'required|numeric'
                : 'nullable|numeric';
        }

        // Test C (plates 11-15, indices 10-14)
        for ($i = 10; $i < 15; $i++) {
            $rules["plates.{$i}"] = in_array('test3', $selectedTests)
                ? 'required|numeric'
                : 'nullable|numeric';
        }

        $validatedData = $request->validate($rules);

        // Aggiungiamo l'ID dell'utente loggato ai dati da salvare
        $validatedData['user_id'] = Session::get('user')['id'];

        // Creiamo il record nel database
        Acceptance::create($validatedData);

        // Reindirizziamo alla dashboard con un messaggio di successo
        return redirect()->route('acceptance.index')->with('success', 'Accettazione campioni salvata con successo!');
    }

    /**
     * Mostra il form per modificare un'accettazione esistente.
     *
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Acceptance $acceptance)
    {
        // Policy di autorizzazione: solo il creatore può modificare.
        $user = Session::get('user');
        $isOwner = $acceptance->user_id === $user['id'];

        return view('acceptance.edit', [
            'acceptance' => $acceptance,
            // Passa un flag alla vista per renderla di sola lettura se l'utente non è il proprietario
            'is_readonly' => !$isOwner
        ]);
    }

    /**
     * Aggiorna un'accettazione esistente nel database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Acceptance $acceptance)
    {
        // Policy di autorizzazione: solo il creatore può modificare.
        $user = Session::get('user');
        $isOwner = $acceptance->user_id === $user['id'];

        if (!$isOwner) {
            abort(403, 'Azione non autorizzata.');
        }

        $rules = [
            'acceptance_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('acceptances')->ignore($acceptance->id),
            ],
            'lotto' => 'required|string|max:255',
            'sampling_date' => 'required|date',
            'acceptance_date' => 'required|date',
            'tests' => 'required|array|min:1',
            'tests.*' => 'string|in:test1,test2,test3',
        ];

        $selectedTests = $request->input('tests', []);

        for ($i = 0; $i < 5; $i++) {
            $rules["plates.{$i}"] = in_array('test1', $selectedTests) ? 'required|numeric' : 'nullable|numeric';
        }
        for ($i = 5; $i < 10; $i++) {
            $rules["plates.{$i}"] = in_array('test2', $selectedTests) ? 'required|numeric' : 'nullable|numeric';
        }
        for ($i = 10; $i < 15; $i++) {
            $rules["plates.{$i}"] = in_array('test3', $selectedTests) ? 'required|numeric' : 'nullable|numeric';
        }

        $validatedData = $request->validate($rules);

        $acceptance->update($validatedData);

        return redirect()->route('acceptance.index')->with('success', 'Accettazione aggiornata con successo!');
    }
}
