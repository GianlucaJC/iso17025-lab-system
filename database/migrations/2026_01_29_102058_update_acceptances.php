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
		// Esempio di migrazione
		Schema::table('acceptances', function (Blueprint $table) {
			$table->string('sample_conformity')->default('conforme')->after('acceptance_date'); // 'conforme' o 'non_conforme'
			$table->text('non_conformity_reason')->nullable()->after('sample_conformity');
		});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
