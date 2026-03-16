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
        Schema::table('test_a_results', function (Blueprint $table) {
            $table->string('ph_meter_double')->nullable()->after('ph_value');
            $table->string('ph_probe_double')->nullable()->after('ph_meter_double');
            $table->decimal('ph_value_double', 4, 2)->nullable()->after('ph_probe_double');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_a_results', function (Blueprint $table) {
            $table->dropColumn(['ph_meter_double', 'ph_probe_double', 'ph_value_double']);
        });
    }
};



