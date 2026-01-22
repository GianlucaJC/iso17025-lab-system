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
            // Colonne per la firma del tecnico di laboratorio
            $table->unsignedBigInteger('lab_signature_id')->nullable()->after('operator_id');
            $table->timestamp('lab_signed_at')->nullable()->after('lab_signature_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_b_results', function (Blueprint $table) {
            $table->dropColumn('lab_signature_id');
            $table->dropColumn('lab_signed_at');
        });
    }
};