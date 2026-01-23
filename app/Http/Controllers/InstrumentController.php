<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\InstrumentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class InstrumentController extends Controller
{
    public function __construct()
    {
        // Middleware per assicurare che solo gli admin (ruolo 1) possano accedere
        $this->middleware(function ($request, $next) {
            $currentUser = Session::get('user');
            $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;

            if (!$isAdmin) {
                abort(403, 'Accesso non autorizzato.');
            }

            return $next($request);
        });
    }

    /**
     * Mostra la pagina di gestione degli strumenti.
     */
    public function index()
    {
        $instruments = Instrument::all();
        $instrumentItems = InstrumentItem::with('instrument')->get();

        return view('instruments.index', [
            'instruments' => $instruments,
            'instrumentItems' => $instrumentItems,
            'currentUser' => Session::get('user'),
        ]);
    }

    /**
     * Salva un nuovo strumento (anagrafica).
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|exists:instruments,id',
            'identifier' => 'required|string|max:255|unique:instrument_items,identifier',
            'description' => 'nullable|string|max:255',
        ]);

        InstrumentItem::create($validated);

        return redirect()->route('instruments.index')->with('success', 'Strumento aggiunto con successo.');
    }

    /**
     * Aggiorna uno strumento esistente.
     */
    public function updateItem(Request $request, InstrumentItem $item)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|exists:instruments,id',
            'identifier' => ['required', 'string', 'max:255', Rule::unique('instrument_items')->ignore($item->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $item->update($validated);

        return redirect()->route('instruments.index')->with('success', 'Strumento aggiornato con successo.');
    }

    /**
     * Rimuove uno strumento.
     */
    public function destroyItem(InstrumentItem $item)
    {
        // Nota: in un'applicazione reale, si dovrebbe verificare se lo strumento è
        // utilizzato in qualche test prima di permetterne l'eliminazione.
        $item->delete();

        return redirect()->route('instruments.index')->with('success', 'Strumento eliminato con successo.');
    }
}