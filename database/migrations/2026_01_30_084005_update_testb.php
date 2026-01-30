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
        Schema::table('test_b_results', function (Blueprint $table) {
            // Common fields that are filled on completion
            $table->dateTime('test_end_datetime')->nullable()->change();
            $table->string('outcome')->nullable()->change();
            $table->string('non_compliance_ref')->nullable()->change();
            $table->text('notes')->nullable()->change();
            $table->text('modification_reason')->nullable()->change();

            // Signature fields
            // Assuming these columns exist and might be NOT NULL
            $table->unsignedBigInteger('lab_signature_id')->nullable()->change();
            $table->timestamp('lab_signed_at')->nullable()->change();
            $table->unsignedBigInteger('rl_signature_id')->nullable()->change();
            $table->timestamp('rl_signed_at')->nullable()->change();

            // Run 1 - Completion fields
            $table->dateTime('incubation_end_datetime_35_run1')->nullable()->change();
            $table->string('growth_result_35_start_plate1_run1')->nullable()->change();
            $table->string('growth_result_35_start_plate2_run1')->nullable()->change();
            $table->string('growth_result_35_mid_plate1_run1')->nullable()->change();
            $table->string('growth_result_35_mid_plate2_run1')->nullable()->change();
            $table->string('growth_result_35_end_plate1_run1')->nullable()->change();
            $table->string('growth_result_35_end_plate2_run1')->nullable()->change();

            $table->dateTime('incubation_end_datetime_22_run1')->nullable()->change();
            $table->string('growth_result_22_start_plate1_run1')->nullable()->change();
            $table->string('growth_result_22_start_plate2_run1')->nullable()->change();
            $table->string('growth_result_22_mid_plate1_run1')->nullable()->change();
            $table->string('growth_result_22_mid_plate2_run1')->nullable()->change();
            $table->string('growth_result_22_end_plate1_run1')->nullable()->change();
            $table->string('growth_result_22_end_plate2_run1')->nullable()->change();

            // Run 2 - All fields should be nullable as they only apply to double tests
            $table->unsignedBigInteger('plate_id_start_plate1_35_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_start_plate2_35_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_mid_plate1_35_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_mid_plate2_35_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_end_plate1_35_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_end_plate2_35_run2')->nullable()->change();
            $table->string('incubator_35_run2')->nullable()->change();
            $table->dateTime('incubation_start_datetime_35_run2')->nullable()->change();
            $table->dateTime('incubation_end_datetime_35_run2')->nullable()->change();
            $table->decimal('temperature_35_run2', 4, 1)->nullable()->change();
            $table->string('growth_result_35_start_plate1_run2')->nullable()->change();
            $table->string('growth_result_35_start_plate2_run2')->nullable()->change();
            $table->string('growth_result_35_mid_plate1_run2')->nullable()->change();
            $table->string('growth_result_35_mid_plate2_run2')->nullable()->change();
            $table->string('growth_result_35_end_plate1_run2')->nullable()->change();
            $table->string('growth_result_35_end_plate2_run2')->nullable()->change();

            $table->unsignedBigInteger('plate_id_start_plate1_22_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_start_plate2_22_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_mid_plate1_22_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_mid_plate2_22_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_end_plate1_22_run2')->nullable()->change();
            $table->unsignedBigInteger('plate_id_end_plate2_22_run2')->nullable()->change();
            $table->string('incubator_22_run2')->nullable()->change();
            $table->dateTime('incubation_start_datetime_22_run2')->nullable()->change();
            $table->dateTime('incubation_end_datetime_22_run2')->nullable()->change();
            $table->decimal('temperature_22_run2', 4, 1)->nullable()->change();
            $table->string('growth_result_22_start_plate1_run2')->nullable()->change();
            $table->string('growth_result_22_start_plate2_run2')->nullable()->change();
            $table->string('growth_result_22_mid_plate1_run2')->nullable()->change();
            $table->string('growth_result_22_mid_plate2_run2')->nullable()->change();
            $table->string('growth_result_22_end_plate1_run2')->nullable()->change();
            $table->string('growth_result_22_end_plate2_run2')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ATTENZIONE: L'operazione di rollback (down) è intenzionalmente lasciata vuota.
        // Ripristinare i vincoli NOT NULL su colonne che ora contengono valori NULL
        // causerebbe un fallimento della migrazione.
        // Se fosse necessario un rollback, bisognerebbe prima gestire i dati NULL nel database.
        Schema::table('test_b_results', function (Blueprint $table) {
            // Non si esegue alcuna azione per sicurezza.
        });
    }
};

