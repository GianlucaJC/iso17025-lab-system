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
        Schema::table('test_c_results', function (Blueprint $table) {
            // New field for TSA Sheep Blood plate
            $table->integer('tsa_growth_ufc')->nullable()->after('temperature');

            // New fields for Run 1 inoculum results
            $table->integer('ufc_start_lotto')->nullable()->after('growth_result_start_lotto');
            $table->boolean('ufc_50_percent_tsa_start_lotto')->nullable()->after('ufc_start_lotto');
            $table->integer('ufc_mid_lotto')->nullable()->after('growth_result_mid_lotto');
            $table->boolean('ufc_50_percent_tsa_mid_lotto')->nullable()->after('ufc_mid_lotto');
            $table->integer('ufc_end_lotto')->nullable()->after('growth_result_end_lotto');
            $table->boolean('ufc_50_percent_tsa_end_lotto')->nullable()->after('ufc_end_lotto');

            // New fields for Run 2 (if double test)
            $table->integer('tsa_growth_ufc_run2')->nullable()->after('temperature_run2');
            $table->integer('ufc_start_lotto_run2')->nullable()->after('growth_result_start_lotto_run2');
            $table->boolean('ufc_50_percent_tsa_start_lotto_run2')->nullable()->after('ufc_start_lotto_run2');
            $table->integer('ufc_mid_lotto_run2')->nullable()->after('growth_result_mid_lotto_run2');
            $table->boolean('ufc_50_percent_tsa_mid_lotto_run2')->nullable()->after('ufc_mid_lotto_run2');
            $table->integer('ufc_end_lotto_run2')->nullable()->after('growth_result_end_lotto_run2');
            $table->boolean('ufc_50_percent_tsa_end_lotto_run2')->nullable()->after('ufc_end_lotto_run2');

            // Add lot number field for TSA plate in Test C, stored in TestCResult
            $table->string('tsa_sheep_blood_plate_lot')->nullable()->after('tsa_sheep_blood_plate_id');

            // Add lot number field for TSA plate in Test C Run 2, stored in TestCResult
            $table->string('tsa_sheep_blood_plate_lot_run2')->nullable()->after('tsa_sheep_blood_plate_id_run2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_c_results', function (Blueprint $table) {
            $table->dropColumn([
                'tsa_growth_ufc', 'ufc_start_lotto', 'ufc_50_percent_tsa_start_lotto', 'ufc_mid_lotto', 'ufc_50_percent_tsa_mid_lotto', 'ufc_end_lotto', 'ufc_50_percent_tsa_end_lotto',
                'tsa_growth_ufc_run2', 'ufc_start_lotto_run2', 'ufc_50_percent_tsa_start_lotto_run2', 'ufc_mid_lotto_run2', 'ufc_50_percent_tsa_mid_lotto_run2', 'ufc_end_lotto_run2', 'ufc_50_percent_tsa_end_lotto_run2', 'tsa_sheep_blood_plate_lot', 'tsa_sheep_blood_plate_lot_run2',
            ]);
        });
    }
};