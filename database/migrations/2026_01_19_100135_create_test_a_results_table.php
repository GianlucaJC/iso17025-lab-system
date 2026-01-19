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
        Schema::create('test_a_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acceptance_id')->constrained()->onDelete('cascade');
            $table->date('test_date');
            $table->unsignedBigInteger('operator_id');
            $table->decimal('ph_value', 4, 2);
            $table->string('outcome'); // 'idoneo' or 'non_idoneo'
            $table->string('non_compliance_ref')->nullable();
            $table->unsignedBigInteger('validator_id')->nullable();
            $table->date('validation_date')->nullable();
            $table->timestamps();

            // Aggiungiamo un vincolo di unicità per evitare di inserire più volte lo stesso test
            $table->unique('acceptance_id', 'test_a_results_acceptance_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_a_results');
    }
};
