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
        // Elimina la tabella esistente per ricrearla da zero con lo schema corretto
        Schema::dropIfExists('test_b_results');

        Schema::create('test_b_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acceptance_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('operator_id');

            // Dati generali prova
            $table->dateTime('test_start_datetime');
            $table->dateTime('test_end_datetime');

            // Run 1 - 35C Incubation Plates
            $table->string('plate_id_start_plate1_35_run1')->nullable();
            $table->string('plate_id_start_plate2_35_run1')->nullable();
            $table->string('plate_id_mid_plate1_35_run1')->nullable();
            $table->string('plate_id_mid_plate2_35_run1')->nullable();
            $table->string('plate_id_end_plate1_35_run1')->nullable();
            $table->string('plate_id_end_plate2_35_run1')->nullable();
            $table->string('incubator_35_run1')->nullable();
            $table->dateTime('incubation_start_datetime_35_run1')->nullable();
            $table->dateTime('incubation_end_datetime_35_run1')->nullable();
            $table->decimal('temperature_35_run1', 4, 1)->nullable();
            $table->enum('growth_result_35_start_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_start_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();

            // Run 1 - 22C Incubation Plates
            $table->string('plate_id_start_plate1_22_run1')->nullable();
            $table->string('plate_id_start_plate2_22_run1')->nullable();
            $table->string('plate_id_mid_plate1_22_run1')->nullable();
            $table->string('plate_id_mid_plate2_22_run1')->nullable();
            $table->string('plate_id_end_plate1_22_run1')->nullable();
            $table->string('plate_id_end_plate2_22_run1')->nullable();
            $table->string('incubator_22_run1')->nullable();
            $table->dateTime('incubation_start_datetime_22_run1')->nullable();
            $table->dateTime('incubation_end_datetime_22_run1')->nullable();
            $table->decimal('temperature_22_run1', 4, 1)->nullable();
            $table->enum('growth_result_22_start_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate1_run1', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate2_run1', ['rilevata', 'non_rilevata'])->nullable();

            // Run 2 - 35C Incubation Plates (For double test)
            $table->string('plate_id_start_plate1_35_run2')->nullable();
            $table->string('plate_id_start_plate2_35_run2')->nullable();
            $table->string('plate_id_mid_plate1_35_run2')->nullable();
            $table->string('plate_id_mid_plate2_35_run2')->nullable();
            $table->string('plate_id_end_plate1_35_run2')->nullable();
            $table->string('plate_id_end_plate2_35_run2')->nullable();
            $table->string('incubator_35_run2')->nullable();
            $table->dateTime('incubation_start_datetime_35_run2')->nullable();
            $table->dateTime('incubation_end_datetime_35_run2')->nullable();
            $table->decimal('temperature_35_run2', 4, 1)->nullable();
            $table->enum('growth_result_35_start_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_start_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_mid_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_35_end_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();

            // Run 2 - 22C Incubation Plates (For double test)
            $table->string('plate_id_start_plate1_22_run2')->nullable();
            $table->string('plate_id_start_plate2_22_run2')->nullable();
            $table->string('plate_id_mid_plate1_22_run2')->nullable();
            $table->string('plate_id_mid_plate2_22_run2')->nullable();
            $table->string('plate_id_end_plate1_22_run2')->nullable();
            $table->string('plate_id_end_plate2_22_run2')->nullable();
            $table->string('incubator_22_run2')->nullable();
            $table->dateTime('incubation_start_datetime_22_run2')->nullable();
            $table->dateTime('incubation_end_datetime_22_run2')->nullable();
            $table->decimal('temperature_22_run2', 4, 1)->nullable();
            $table->enum('growth_result_22_start_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_start_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_mid_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate1_run2', ['rilevata', 'non_rilevata'])->nullable();
            $table->enum('growth_result_22_end_plate2_run2', ['rilevata', 'non_rilevata'])->nullable();

            // Esito
            $table->string('outcome'); // 'idoneo' or 'non_idoneo'
            $table->string('non_compliance_ref')->nullable();
            $table->text('notes')->nullable();

            // Validazione (futura)
            $table->unsignedBigInteger('validator_id')->nullable();
            $table->timestamp('validation_date')->nullable();

            // Auditing
            $table->text('modification_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_b_results');
    }
};