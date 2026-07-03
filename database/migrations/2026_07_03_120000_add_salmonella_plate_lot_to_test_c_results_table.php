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
            // Aggiungi i campi per il lotto piastra Salmonella typhimurium ATCC 14028
            $table->string('salmonella_typhimurium_plate_lot')->nullable()->after('pipette_inoculation');
            $table->string('salmonella_typhimurium_plate_lot_run2')->nullable()->after('pipette_inoculation_run2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_c_results', function (Blueprint $table) {
            $table->dropColumn([
                'salmonella_typhimurium_plate_lot',
                'salmonella_typhimurium_plate_lot_run2',
            ]);
        });
    }
};
