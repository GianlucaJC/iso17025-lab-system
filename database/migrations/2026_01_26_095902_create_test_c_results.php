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

            // ID Piastre - Run 1
            $table->unsignedBigInteger('plate_id_1')->nullable();
            $table->unsignedBigInteger('plate_id_2')->nullable();
            $table->unsignedBigInteger('plate_id_3')->nullable();
            $table->unsignedBigInteger('plate_id_control_blank')->nullable();
            $table->unsignedBigInteger('plate_id_control_tsa')->nullable();

            // Risultati Crescita - Run 1
            $table->string('growth_result_plate_1');
            $table->string('growth_result_plate_2');
            $table->string('growth_result_plate_3');
            $table->string('growth_result_control_blank');
            $table->string('growth_result_control_tsa');

            // ID Piastre - Run 2 (Doppio)
            $table->unsignedBigInteger('plate_id_1_run2')->nullable();
            $table->unsignedBigInteger('plate_id_2_run2')->nullable();
            $table->unsignedBigInteger('plate_id_3_run2')->nullable();
            $table->unsignedBigInteger('plate_id_control_blank_run2')->nullable();
            $table->unsignedBigInteger('plate_id_control_tsa_run2')->nullable();

            // Risultati Crescita - Run 2 (Doppio)
            $table->string('growth_result_plate_1_run2')->nullable();
            $table->string('growth_result_plate_2_run2')->nullable();
            $table->string('growth_result_plate_3_run2')->nullable();
            $table->string('growth_result_control_blank_run2')->nullable();
            $table->string('growth_result_control_tsa_run2')->nullable();

            // Esito e note
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
