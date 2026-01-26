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
        Schema::create('test_c_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acceptance_id')->constrained('acceptances')->onDelete('cascade');
            $table->unsignedBigInteger('operator_id'); // ID utente dall'API esterna

            // Dati generali del test
            $table->dateTime('test_start_datetime');
            $table->dateTime('test_end_datetime');

            // Sezione Preparazione/Diluizione - Run 1
            $table->string('tsa_sheep_blood_plate_id')->nullable();
            $table->string('pipette_dilution_1')->nullable();
            $table->string('pipette_dilution_2')->nullable();
            $table->string('pipette_inoculation')->nullable();

            // Sezione Incubazione - Run 1
            $table->string('incubator')->nullable();
            $table->dateTime('incubation_start_datetime')->nullable();
            $table->dateTime('incubation_end_datetime')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('tsa_growth_result')->nullable();

            // Risultati Campioni - Run 1
            $table->unsignedBigInteger('plate_id_start_lotto')->nullable();
            $table->string('growth_result_start_lotto')->nullable();
            $table->unsignedBigInteger('plate_id_mid_lotto')->nullable();
            $table->string('growth_result_mid_lotto')->nullable();
            $table->unsignedBigInteger('plate_id_end_lotto')->nullable();
            $table->string('growth_result_end_lotto')->nullable();
            $table->unsignedBigInteger('plate_id_control_blank')->nullable();
            $table->string('growth_result_control_blank')->nullable();

            // Sezione Preparazione/Diluizione - Run 2
            $table->string('tsa_sheep_blood_plate_id_run2')->nullable();
            $table->string('pipette_dilution_1_run2')->nullable();
            $table->string('pipette_dilution_2_run2')->nullable();
            $table->string('pipette_inoculation_run2')->nullable();

            // Sezione Incubazione - Run 2
            $table->string('incubator_run2')->nullable();
            $table->dateTime('incubation_start_datetime_run2')->nullable();
            $table->dateTime('incubation_end_datetime_run2')->nullable();
            $table->decimal('temperature_run2', 4, 1)->nullable();
            $table->string('tsa_growth_result_run2')->nullable();

            // Risultati Campioni - Run 2
            $table->unsignedBigInteger('plate_id_start_lotto_run2')->nullable();
            $table->string('growth_result_start_lotto_run2')->nullable();
            $table->unsignedBigInteger('plate_id_mid_lotto_run2')->nullable();
            $table->string('growth_result_mid_lotto_run2')->nullable();
            $table->unsignedBigInteger('plate_id_end_lotto_run2')->nullable();
            $table->string('growth_result_end_lotto_run2')->nullable();
            $table->unsignedBigInteger('plate_id_control_blank_run2')->nullable();
            $table->string('growth_result_control_blank_run2')->nullable();

            // Esito e note
            $table->text('productivity_result')->nullable();
            $table->string('outcome'); // idoneo, non_idoneo
            $table->string('non_compliance_ref')->nullable();
            $table->text('notes')->nullable();
            $table->text('modification_reason')->nullable();

            // Firme
            $table->unsignedBigInteger('lab_signature_id')->nullable();
            $table->timestamp('lab_signed_at')->nullable();
            $table->unsignedBigInteger('rl_signature_id')->nullable();
            $table->timestamp('rl_signed_at')->nullable();

            $table->timestamps();

            // Indici
            $table->unique('acceptance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_c_results');
    }
};