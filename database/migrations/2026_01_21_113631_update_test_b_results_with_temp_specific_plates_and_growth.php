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
            // Rimuovi tutte le colonne che sono state aggiunte dalla migration precedente (2026_01_21_104133)
            // Queste sono le colonne con _plateX_runY ma SENZA _temp_ suffix nel nome.
            $columnsFromPreviousMigration = [
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
            ];

            foreach ($columnsFromPreviousMigration as $column) {
                if (Schema::hasColumn('test_b_results', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Aggiungi le nuove colonne con il suffisso di temperatura.
            // Senza 'after()' per evitare errori di colonne non trovate e per maggiore robustezza.
            // Laravel le aggiungerà alla fine della tabella.

            // Run 1 - 35C Incubation Plates
            $table->string('plate_id_start_plate1_35_run1')->nullable();
            $table->string('plate_id_start_plate2_35_run1')->nullable();
            $table->string('plate_id_mid_plate1_35_run1')->nullable();
            $table->string('plate_id_mid_plate2_35_run1')->nullable();
            $table->string('plate_id_end_plate1_35_run1')->nullable();
            $table->string('plate_id_end_plate2_35_run1')->nullable();

            // Run 1 - 22C Incubation Plates
            $table->string('plate_id_start_plate1_22_run1')->nullable();
            $table->string('plate_id_start_plate2_22_run1')->nullable();
            $table->string('plate_id_mid_plate1_22_run1')->nullable();
            $table->string('plate_id_mid_plate2_22_run1')->nullable();
            $table->string('plate_id_end_plate1_22_run1')->nullable();
            $table->string('plate_id_end_plate2_22_run1')->nullable();

            // Run 1 - Growth results for 35C (NOMI COLONNE CORRETTI con suffisso _35_)
            $table->enum('growth_result_35_start_plate1_35_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_start_plate2_35_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate1_35_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate2_35_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate1_35_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate2_35_run1', ['rilevata', 'non_rilevata'])->nullable();

            // Run 1 - Growth results for 22C (NOMI COLONNE CORRETTI con suffisso _22_)
            $table->enum('growth_result_22_start_plate1_22_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate2_22_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate1_22_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate2_22_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate1_22_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate2_22_run1', ['rilevata', 'non_rilevata'])->nullable();

            // Run 2 - 35C Incubation Plates (For double test)
            $table->string('plate_id_start_plate1_35_run2')->nullable();
            $table->string('plate_id_start_plate2_35_run2')->nullable();
            $table->string('plate_id_mid_plate1_35_run2')->nullable();
            $table->string('plate_id_mid_plate2_35_run2')->nullable();
            $table->string('plate_id_end_plate1_35_run2')->nullable();
            $table->string('plate_id_end_plate2_35_run2')->nullable();

            // Run 2 - 22C Incubation Plates (For double test)
            $table->string('plate_id_start_plate1_22_run2')->nullable();
            $table->string('plate_id_start_plate2_22_run2')->nullable();
            $table->string('plate_id_mid_plate1_22_run2')->nullable();
            $table->string('plate_id_mid_plate2_22_run2')->nullable();
            $table->string('plate_id_end_plate1_22_run2')->nullable();
            $table->string('plate_id_end_plate2_22_run2')->nullable();

            // Run 2 - Growth results for 35C (NOMI COLONNE CORRETTI con suffisso _35_)
            $table->enum('growth_result_35_start_plate1_35_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_start_plate2_35_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate1_35_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate2_35_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate1_35_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate2_35_run2', ['rilevata', 'non_rilevata'])->nullable();

            // Run 2 - Growth results for 22C (NOMI COLONNE CORRETTI con suffisso _22_)
            $table->enum('growth_result_22_start_plate1_22_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate2_22_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate1_22_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate2_22_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate1_22_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate2_22_run2', ['rilevata', 'non_rilevata'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_b_results', function (Blueprint $table) {
            // Rimuovi tutte le colonne aggiunte nel metodo 'up' di questa migration
            $columnsToDrop = [
                'plate_id_start_plate1_35_run1', 'plate_id_start_plate2_35_run1',
                'plate_id_mid_plate1_35_run1', 'plate_id_mid_plate2_35_run1',
                'plate_id_end_plate1_35_run1', 'plate_id_end_plate2_35_run1',
                'plate_id_start_plate1_22_run1', 'plate_id_start_plate2_22_run1',
                'plate_id_mid_plate1_22_run1', 'plate_id_mid_plate2_22_run1',
                'plate_id_end_plate1_22_run1', 'plate_id_end_plate2_22_run1',
                'growth_result_35_start_plate1_35_run1', 'growth_result_35_start_plate2_35_run1',
                'growth_result_35_mid_plate1_35_run1', 'growth_result_35_mid_plate2_35_run1',
                'growth_result_35_end_plate1_35_run1', 'growth_result_35_end_plate2_35_run1',
                'growth_result_22_start_plate1_22_run1', 'growth_result_22_start_plate2_22_run1',
                'growth_result_22_mid_plate1_22_run1', 'growth_result_22_mid_plate2_22_run1',
                'growth_result_22_end_plate1_22_run1', 'growth_result_22_end_plate2_22_run1',
                'plate_id_start_plate1_35_run2', 'plate_id_start_plate2_35_run2',
                'plate_id_mid_plate1_35_run2', 'plate_id_mid_plate2_35_run2',
                'plate_id_end_plate1_35_run2', 'plate_id_end_plate2_35_run2',
                'plate_id_start_plate1_22_run2', 'plate_id_start_plate2_22_run2',
                'plate_id_mid_plate1_22_run2', 'plate_id_mid_plate2_22_run2',
                'plate_id_end_plate1_22_run2', 'plate_id_end_plate2_22_run2',
                'growth_result_35_start_plate1_35_run2', 'growth_result_35_start_plate2_35_run2',
                'growth_result_35_mid_plate1_35_run2', 'growth_result_35_mid_plate2_35_run2',
                'growth_result_35_end_plate1_35_run2', 'growth_result_35_end_plate2_35_run2',
                'growth_result_22_start_plate1_22_run2', 'growth_result_22_start_plate2_22_run2',
                'growth_result_22_mid_plate1_22_run2', 'growth_result_22_mid_plate2_22_run2',
                'growth_result_22_end_plate1_22_run2', 'growth_result_22_end_plate2_22_run2',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('test_b_results', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Re-add the columns that were dropped at the beginning of the 'up' method (from 2026_01_21_104133)
            // These are the columns with _plateX_runY but without _temp_ suffix.
            $table->string('plate_id_start_plate1_run1')->nullable();
            $table->string('plate_id_start_plate2_run1')->nullable();
            $table->string('plate_id_mid_plate1_run1')->nullable();
            $table->string('plate_id_mid_plate2_run1')->nullable();
            $table->string('plate_id_end_plate1_run1')->nullable();
            $table->string('plate_id_end_plate2_run1')->nullable();
            $table->enum('growth_result_35_start_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_start_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();

            $table->string('plate_id_start_plate1_run2')->nullable();
            $table->string('plate_id_start_plate2_run2')->nullable();
            $table->string('plate_id_mid_plate1_run2')->nullable();
            $table->string('plate_id_mid_plate2_run2')->nullable();
            $table->string('plate_id_end_plate1_run2')->nullable();
            $table->string('plate_id_end_plate2_run2')->nullable();
            $table->enum('growth_result_35_start_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_start_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
        });
    }
};
