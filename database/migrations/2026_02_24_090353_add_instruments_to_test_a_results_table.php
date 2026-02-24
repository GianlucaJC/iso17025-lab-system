<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstrumentsToTestAResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('test_a_results', function (Blueprint $table) {
            // Aggiungiamo i campi per gli identificativi degli strumenti dopo 'operator_id'
            $table->string('ph_meter')->after('operator_id');
            $table->string('ph_probe')->after('ph_meter');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('test_a_results', function (Blueprint $table) {
            $table->dropColumn(['ph_meter', 'ph_probe']);
        });
    }
}
