<?php

namespace App\Http\Controllers;

use App\Models\MethodRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class MethodRevisionController extends Controller
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
     * Mostra la pagina di gestione delle revisioni dei metodi.
     */
    public function index()
    {
        // Assicura che i record esistano, creandoli con i valori di default se non presenti.
        // Questo agisce come un seeder automatico.
        MethodRevision::firstOrCreate(
            ['method_key' => 'test_a'],
            ['method_name' => 'MA_09_Misurazione del pH', 'revision_string' => 'MA09 Rev.5 del 20.10.2023']
        );
        MethodRevision::firstOrCreate(
            ['method_key' => 'test_b'],
            ['method_name' => 'MA_61_Contaminazione microbica', 'revision_string' => 'MA61 Rev.2 del 07.03.2024']
        );
        MethodRevision::firstOrCreate(
            ['method_key' => 'test_c'],
            ['method_name' => 'MA_60_Valutazione produttività XLD', 'revision_string' => 'MA60 Rev.4 del 07.03.2024']
        );

        $methods = MethodRevision::all()->keyBy('method_key');

        return view('methods.index', [
            'methods' => $methods,
            'currentUser' => Session::get('user'),
        ]);
    }

    /**
     * Aggiorna le revisioni dei metodi.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'revisions' => 'required|array',
            'revisions.test_a.revision_string' => 'required|string|max:255',
            'revisions.test_b.revision_string' => 'required|string|max:255',
            'revisions.test_c.revision_string' => 'required|string|max:255',
        ]);

        foreach ($validated['revisions'] as $key => $data) {
            MethodRevision::where('method_key', $key)->update($data);
        }

        return redirect()->route('methods.index')->with('success', 'Revisioni dei metodi aggiornate con successo.');
    }
}
