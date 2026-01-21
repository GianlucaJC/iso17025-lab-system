<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('test_b_results', function (Blueprint $table) {
            // Rimuovi le vecchie colonne (se esistono e non sono state rimosse da una migration precedente)
            $table->dropColumn(['plate_id_start_run1', 'plate_id_mid_run1', 'plate_id_end_run1',
                                'growth_result_35_start_run1', 'growth_result_35_mid_run1', 'growth_result_35_end_run1',
                                'growth_result_22_start_run1', 'growth_result_22_mid_run1', 'growth_result_22_end_run1',
                                'plate_id_start_run2', 'plate_id_mid_run2', 'plate_id_end_run2',
                                'growth_result_35_start_run2', 'growth_result_35_mid_run2', 'growth_result_35_end_run2',
                                'growth_result_22_start_run2', 'growth_result_22_mid_run2', 'growth_result_22_end_run2']);

            // Aggiungi le nuove colonne per Run 1
            $table->string('plate_id_start_plate1_run1')->nullable()->after('test_end_datetime');
            $table->string('plate_id_start_plate2_run1')->nullable()->after('plate_id_start_plate1_run1');
            $table->string('plate_id_mid_plate1_run1')->nullable()->after('plate_id_start_plate2_run1');
            $table->string('plate_id_mid_plate2_run1')->nullable()->after('plate_id_mid_plate1_run1');
            $table->string('plate_id_end_plate1_run1')->nullable()->after('plate_id_mid_plate2_run1');
            $table->string('plate_id_end_plate2_run1')->nullable()->after('plate_id_end_plate1_run1');

            $table->enum('growth_result_35_start_plate1_run1', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_35_run1');
            $table->enum('growth_result_35_start_plate2_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_start_plate1_run1');
            $table->enum('growth_result_35_mid_plate1_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_start_plate2_run1');
            $table->enum('growth_result_35_mid_plate2_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_mid_plate1_run1');
            $table->enum('growth_result_35_end_plate1_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_mid_plate2_run1');
            $table->enum('growth_result_35_end_plate2_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_end_plate1_run1');

            $table->enum('growth_result_22_start_plate1_run1', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_22_run1');
            $table->enum('growth_result_22_start_plate2_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_start_plate1_run1');
            $table->enum('growth_result_22_mid_plate1_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_start_plate2_run1');
            $table->enum('growth_result_22_mid_plate2_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_mid_plate1_run1');
            $table->enum('growth_result_22_end_plate1_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_mid_plate2_run1');
            $table->enum('growth_result_22_end_plate2_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_end_plate1_run1');

            // Aggiungi le nuove colonne per Run 2 (se non sono state aggiunte da una migration precedente)
            $table->string('plate_id_start_plate1_run2')->nullable()->after('growth_result_22_end_plate2_run1');
            $table->string('plate_id_start_plate2_run2')->nullable()->after('plate_id_start_plate1_run2');
            $table->string('plate_id_mid_plate1_run2')->nullable()->after('plate_id_start_plate2_run2');
            $table->string('plate_id_mid_plate2_run2')->nullable()->after('plate_id_mid_plate1_run2');
            $table->string('plate_id_end_plate1_run2')->nullable()->after('plate_id_mid_plate2_run2');
            $table->string('plate_id_end_plate2_run2')->nullable()->after('plate_id_end_plate1_run2');

            $table->enum('growth_result_35_start_plate1_run2', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_35_run2');
            $table->enum('growth_result_35_start_plate2_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_start_plate1_run2');
            $table->enum('growth_result_35_mid_plate1_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_start_plate2_run2');
            $table->enum('growth_result_35_mid_plate2_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_mid_plate1_run2');
            $table->enum('growth_result_35_end_plate1_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_mid_plate2_run2');
            $table->enum('growth_result_35_end_plate2_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_end_plate1_run2');

            $table->enum('growth_result_22_start_plate1_run2', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_22_run2');
            $table->enum('growth_result_22_start_plate2_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_start_plate1_run2');
            $table->enum('growth_result_22_mid_plate1_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_start_plate2_run2');
            $table->enum('growth_result_22_mid_plate2_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_mid_plate1_run2');
            $table->enum('growth_result_22_end_plate1_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_mid_plate2_run2');
            $table->enum('growth_result_22_end_plate2_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_end_plate1_run2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_b_results', function (Blueprint $table) {
            // Rimuovi le nuove colonne
            $table->dropColumn([
                'plate_id_start_plate1_run1', 'plate_id_start_plate2_run1',
                'plate_id_mid_plate1_run1', 'plate_id_mid_plate2_run1',
                'plate_id_end_plate1_run1', 'plate_id_end_plate2_run1',
                'growth_result_35_start_plate1_run1', 'growth_result_35_start_plate2_run1',
                'growth_result_35_mid_plate1_run1', 'growth_result_35_mid_plate2_run1',
                'growth_result_35_end_plate1_run1', 'growth_result_35_end_plate2_run1',
                'growth_result_22_start_plate1_run1', 'growth_result_22_start_plate2_run1',
                'growth_result_22_mid_plate1_run1', 'growth_result_22_mid_plate2_run1',
                'growth_result_22_end_plate1_run1', 'growth_result_22_end_plate2_run1',
                'plate_id_start_plate1_run2', 'plate_id_start_plate2_run2',
                'plate_id_mid_plate1_run2', 'plate_id_mid_plate2_run2',
                'plate_id_end_plate1_run2', 'plate_id_end_plate2_run2',
                'growth_result_35_start_plate1_run2', 'growth_result_35_start_plate2_run2',
                'growth_result_35_mid_plate1_run2', 'growth_result_35_mid_plate2_run2',
                'growth_result_35_end_plate1_run2', 'growth_result_35_end_plate2_run2',
                'growth_result_22_start_plate1_run2', 'growth_result_22_start_plate2_run2',
                'growth_result_22_mid_plate1_run2', 'growth_result_22_mid_plate2_run2',
                'growth_result_22_end_plate1_run2', 'growth_result_22_end_plate2_run2',
            ]);

            // Ri-aggiungi le vecchie colonne (se necessario, in base alla tua cronologia di migration)
            // Questo blocco è un esempio e potrebbe dover essere adattato alla tua situazione specifica.
            // Se la migration precedente ha già aggiunto le colonne _run1 e _run2 senza _plate1/_plate2,
            // allora dovresti ripristinare quelle. Se invece le colonne originali erano senza _runX,
            // allora dovresti ripristinare quelle.
            // Per semplicità, qui ripristino le colonne come erano prima dell'introduzione di _plateX.
            $table->string('plate_id_start_run1')->nullable();
            $table->string('plate_id_mid_run1')->nullable();
            $table->string('plate_id_end_run1')->nullable();
            $table->enum('growth_result_35_start_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->string('plate_id_start_run2')->nullable();
            $table->string('plate_id_mid_run2')->nullable();
            $table->string('plate_id_end_run2')->nullable();
            $table->enum('growth_result_35_start_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_run2', ['rilevata', 'non_rilevata'])->nullable();
        });
    }
};
