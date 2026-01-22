<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rinomina le colonne di validazione nella tabella dei risultati del Test B usando DB::statement per compatibilità
        try {
            DB::statement('ALTER TABLE test_b_results CHANGE validator_id rl_signature_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE test_b_results CHANGE validation_date rl_signed_at TIMESTAMP NULL');
        } catch (QueryException $e) {
            // Se le colonne non esistono o sono già state rinominate, ignora l'errore
            // Questo può accadere se la migrazione è stata parzialmente eseguita o se le colonne non sono presenti
            // in un ambiente di sviluppo/test specifico.
            // In un ambiente di produzione, è preferibile che la migrazione sia idempotente.
        }

        // Aggiunge le colonne per la firma del tecnico e rinomina quelle di validazione per il Test A
        Schema::table('test_a_results', function (Blueprint $table) {
            // Aggiunge campi per la firma del tecnico
            $table->unsignedBigInteger('lab_signature_id')->nullable()->after('operator_id');
            $table->timestamp('lab_signed_at')->nullable()->after('lab_signature_id');
        });
        // Rinomina le colonne di validazione per il Test A usando DB::statement
        try {
            DB::statement('ALTER TABLE test_a_results CHANGE validator_id rl_signature_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE test_a_results CHANGE validation_date rl_signed_at TIMESTAMP NULL');
        } catch (QueryException $e) {
            // Ignora l'errore se le colonne non esistono o sono già state rinominate
        }
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE test_b_results CHANGE rl_signature_id validator_id BIGINT UNSIGNED NULL');
        try {
            DB::statement('ALTER TABLE test_b_results CHANGE rl_signed_at validation_date TIMESTAMP NULL');
        } catch (QueryException $e) {
            // Ignora l'errore se la colonna non esiste o è già stata rinominata
        }

        Schema::table('test_a_results', function (Blueprint $table) {
            $table->dropColumn(['lab_signature_id', 'lab_signed_at']);
        });
        try {
            DB::statement('ALTER TABLE test_a_results CHANGE rl_signature_id validator_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE test_a_results CHANGE rl_signed_at validation_date TIMESTAMP NULL');
        } catch (QueryException $e) {
            // Ignora l'errore se la colonna non esiste o è già stata rinominata
        }
    }
};