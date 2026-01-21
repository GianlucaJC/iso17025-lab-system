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
            // Colonne da rimuovere (se esistono)
            $oldColumns = [
                'incubator_35', 'incubation_start_datetime_35', 'incubation_end_datetime_35', 'temperature_35',
                'growth_result_35_start', 'growth_result_35_mid', 'growth_result_35_end',
                'incubator_22', 'incubation_start_datetime_22', 'incubation_end_datetime_22', 'temperature_22',
                'growth_result_22_start', 'growth_result_22_mid', 'growth_result_22_end'
            ];

            // Rimuove le vecchie colonne solo se esistono, per evitare errori
            foreach ($oldColumns as $column) {
                if (Schema::hasColumn('test_b_results', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Aggiunge le nuove colonne per Run 1
            $table->string('plate_id_start_run1')->nullable()->after('test_end_datetime');
            $table->string('plate_id_mid_run1')->nullable()->after('plate_id_start_run1');
            $table->string('plate_id_end_run1')->nullable()->after('plate_id_mid_run1');
            $table->string('incubator_35_run1')->nullable()->after('plate_id_end_run1');
            $table->dateTime('incubation_start_datetime_35_run1')->nullable()->after('incubator_35_run1');
            $table->dateTime('incubation_end_datetime_35_run1')->nullable()->after('incubation_start_datetime_35_run1');
            $table->decimal('temperature_35_run1', 4, 1)->nullable()->after('incubation_end_datetime_35_run1');
            $table->enum('growth_result_35_start_run1', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_35_run1');
            $table->enum('growth_result_35_mid_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_start_run1');
            $table->enum('growth_result_35_end_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_mid_run1');
            $table->string('incubator_22_run1')->nullable()->after('growth_result_35_end_run1');
            $table->dateTime('incubation_start_datetime_22_run1')->nullable()->after('incubator_22_run1');
            $table->dateTime('incubation_end_datetime_22_run1')->nullable()->after('incubation_start_datetime_22_run1');
            $table->decimal('temperature_22_run1', 4, 1)->nullable()->after('incubation_end_datetime_22_run1');
            $table->enum('growth_result_22_start_run1', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_22_run1');
            $table->enum('growth_result_22_mid_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_start_run1');
            $table->enum('growth_result_22_end_run1', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_mid_run1');

            // Aggiunge le nuove colonne per Run 2
            $table->string('plate_id_start_run2')->nullable()->after('growth_result_22_end_run1');
            $table->string('plate_id_mid_run2')->nullable()->after('plate_id_start_run2');
            $table->string('plate_id_end_run2')->nullable()->after('plate_id_mid_run2');
            $table->string('incubator_35_run2')->nullable()->after('plate_id_end_run2');
            $table->dateTime('incubation_start_datetime_35_run2')->nullable()->after('incubator_35_run2');
            $table->dateTime('incubation_end_datetime_35_run2')->nullable()->after('incubation_start_datetime_35_run2');
            $table->decimal('temperature_35_run2', 4, 1)->nullable()->after('incubation_end_datetime_35_run2');
            $table->enum('growth_result_35_start_run2', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_35_run2');
            $table->enum('growth_result_35_mid_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_start_run2');
            $table->enum('growth_result_35_end_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_35_mid_run2');
            $table->string('incubator_22_run2')->nullable()->after('growth_result_35_end_run2');
            $table->dateTime('incubation_start_datetime_22_run2')->nullable()->after('incubator_22_run2');
            $table->dateTime('incubation_end_datetime_22_run2')->nullable()->after('incubation_start_datetime_22_run2');
            $table->decimal('temperature_22_run2', 4, 1)->nullable()->after('incubation_end_datetime_22_run2');
            $table->enum('growth_result_22_start_run2', ['rilevata', 'non_rilevata'])->nullable()->after('temperature_22_run2');
            $table->enum('growth_result_22_mid_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_start_run2');
            $table->enum('growth_result_22_end_run2', ['rilevata', 'non_rilevata'])->nullable()->after('growth_result_22_mid_run2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_b_results', function (Blueprint $table) {
            // Rimuove le nuove colonne
            $newColumns = [
                'plate_id_start_run1', 'plate_id_mid_run1', 'plate_id_end_run1',
                'incubator_35_run1', 'incubation_start_datetime_35_run1', 'incubation_end_datetime_35_run1', 'temperature_35_run1',
                'growth_result_35_start_run1', 'growth_result_35_mid_run1', 'growth_result_35_end_run1',
                'incubator_22_run1', 'incubation_start_datetime_22_run1', 'incubation_end_datetime_22_run1', 'temperature_22_run1',
                'growth_result_22_start_run1', 'growth_result_22_mid_run1', 'growth_result_22_end_run1',
                'plate_id_start_run2', 'plate_id_mid_run2', 'plate_id_end_run2',
                'incubator_35_run2', 'incubation_start_datetime_35_run2', 'incubation_end_datetime_35_run2', 'temperature_35_run2',
                'growth_result_35_start_run2', 'growth_result_35_mid_run2', 'growth_result_35_end_run2',
                'incubator_22_run2', 'incubation_start_datetime_22_run2', 'incubation_end_datetime_22_run2', 'temperature_22_run2',
                'growth_result_22_start_run2', 'growth_result_22_mid_run2', 'growth_result_22_end_run2',
            ];
            $table->dropColumn($newColumns);

            // Ri-aggiunge le vecchie colonne
            $table->string('incubator_35')->nullable();
            $table->dateTime('incubation_start_datetime_35')->nullable();
            $table->dateTime('incubation_end_datetime_35')->nullable();
            $table->decimal('temperature_35', 4, 1)->nullable();
            $table->enum('growth_result_35_start', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end', ['rilevata', 'non_rilevata'])->nullable();
            $table->string('incubator_22')->nullable();
            $table->dateTime('incubation_start_datetime_22')->nullable();
            $table->dateTime('incubation_end_datetime_22')->nullable();
            $table->decimal('temperature_22', 4, 1)->nullable();
            $table->enum('growth_result_22_start', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end', ['rilevata', 'non_rilevata'])->nullable();
        });
    }
};
