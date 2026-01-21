<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /**
     * Mostra la cronologia per un dato record.
     *
     * @param string $modelNameShort
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show(string $modelNameShort, int $id)
    {
        // Policy di sicurezza: solo gli amministratori (ruolo 1) possono vedere la cronologia.
        $currentUser = Session::get('user');
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;

        if (!$isAdmin) {
            abort(403, 'Accesso non autorizzato. Solo gli amministratori possono visualizzare la cronologia.');
        }

        // Mappa i nomi brevi usati nella URL alle classi complete dei modelli
        $modelMap = [
            'acceptance' => \App\Models\Acceptance::class,
            'test-a-result' => \App\Models\TestAResult::class,
            'test-b-result' => \App\Models\TestBResult::class, // Aggiunto Test B
        ];

        if (!array_key_exists($modelNameShort, $modelMap)) {
            abort(404, 'Tipo di modello non trovato.');
        }

        $modelClass = $modelMap[$modelNameShort];
        $record = $modelClass::findOrFail($id);

        // Carica tutti i log per quel record, dal più recente al più vecchio
        $logs = AuditLog::where('auditable_type', $modelClass)
                        ->where('auditable_id', $id)
                        ->latest()
                        ->get();

        // Ottiene un nome "leggibile" del modello per il titolo della pagina
        $modelDisplayName = (new \ReflectionClass($record))->getShortName();

        return view('audits.history', [
            'record' => $record,
            'logs' => $logs,
            'modelDisplayName' => $modelDisplayName,
        ]);
    }
}