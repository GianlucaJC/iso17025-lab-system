<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('test_c_results', function (Blueprint $table) {
            // Common fields
            $table->dateTime('test_end_datetime')->nullable()->change();
            $table->string('outcome')->nullable()->change();
            $table->string('non_compliance_ref')->nullable()->change();
            $table->text('notes')->nullable()->change();
            $table->text('modification_reason')->nullable()->change();
            $table->text('productivity_result')->nullable()->change();

            // Signature fields
            $table->unsignedBigInteger('lab_signature_id')->nullable()->change();
            $table->timestamp('lab_signed_at')->nullable()->change();
            $table->unsignedBigInteger('rl_signature_id')->nullable()->change();
            $table->timestamp('rl_signed_at')->nullable()->change();

            // Run 1 - Completion fields
            $table->dateTime('incubation_end_datetime')->nullable()->change();
            $table->integer('tsa_growth_ufc')->nullable()->change();
            $table->string('tsa_growth_result')->nullable()->change();
            $table->string('growth_result_start_lotto')->nullable()->change();
            $table->integer('ufc_start_lotto')->nullable()->change();
            $table->boolean('ufc_50_percent_tsa_start_lotto')->nullable()->change();
            $table->string('growth_result_mid_lotto')->nullable()->change();
            $table->integer('ufc_mid_lotto')->nullable()->change();
            $table->boolean('ufc_50_percent_tsa_mid_lotto')->nullable()->change();
            $table->string('growth_result_end_lotto')->nullable()->change();
            $table->integer('ufc_end_lotto')->nullable()->change();
            $table->boolean('ufc_50_percent_tsa_end_lotto')->nullable()->change();
            $table->string('growth_result_control_blank')->nullable()->change();

            // Run 2 - All fields should be nullable as they are optional
            $table->string('pipette_dilution_1_run2')->nullable()->change();
            $table->string('pipette_dilution_2_run2')->nullable()->change();
            $table->string('pipette_inoculation_run2')->nullable()->change();
            $table->string('incubator_run2')->nullable()->change();
            $table->dateTime('incubation_start_datetime_run2')->nullable()->change();
            $table->dateTime('incubation_end_datetime_run2')->nullable()->change();
            $table->decimal('temperature_run2', 8, 2)->nullable()->change();
            $table->integer('tsa_growth_ufc_run2')->nullable()->change();
            $table->string('tsa_growth_result_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_start_lotto_run2')->nullable()->change();
            $table->string('growth_result_start_lotto_run2')->nullable()->change();
            $table->integer('ufc_start_lotto_run2')->nullable()->change();
            $table->boolean('ufc_50_percent_tsa_start_lotto_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_mid_lotto_run2')->nullable()->change();
            $table->string('growth_result_mid_lotto_run2')->nullable()->change();
            $table->integer('ufc_mid_lotto_run2')->nullable()->change();
            $table->boolean('ufc_50_percent_tsa_mid_lotto_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_end_lotto_run2')->nullable()->change();
            $table->string('growth_result_end_lotto_run2')->nullable()->change();
            $table->integer('ufc_end_lotto_run2')->nullable()->change();
            $table->boolean('ufc_50_percent_tsa_end_lotto_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_control_blank_run2')->nullable()->change();
            $table->string('growth_result_control_blank_run2')->nullable()->change();
            $table->unsignedBigInteger('tsa_sheep_blood_plate_id_run2')->nullable()->change();
            $table->string('tsa_sheep_blood_plate_lot_run2')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Il rollback è intenzionalmente lasciato vuoto per sicurezza.
    }
};