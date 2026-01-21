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
        Schema::create('test_b_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acceptance_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('operator_id');

            // Dati generali prova
            $table->dateTime('test_start_datetime');
            $table->dateTime('test_end_datetime');

            // Incubazione 35°C
            $table->string('incubator_35')->nullable();
            $table->dateTime('incubation_start_datetime_35')->nullable();
            $table->dateTime('incubation_end_datetime_35')->nullable();
            $table->decimal('temperature_35', 4, 1)->nullable();
            $table->string('growth_result_35_start')->nullable(); // 'rilevata' o 'non_rilevata'
            $table->string('growth_result_35_mid')->nullable();
            $table->string('growth_result_35_end')->nullable();

            // Incubazione 22°C
            $table->string('incubator_22')->nullable();
            $table->dateTime('incubation_start_datetime_22')->nullable();
            $table->dateTime('incubation_end_datetime_22')->nullable();
            $table->decimal('temperature_22', 4, 1)->nullable();
            $table->string('growth_result_22_start')->nullable();
            $table->string('growth_result_22_mid')->nullable();
            $table->string('growth_result_22_end')->nullable();

            // Esito
            $table->string('outcome'); // 'idoneo' o 'non_idoneo'
            $table->string('non_compliance_ref')->nullable();
            $table->text('notes')->nullable();

            // Validazione (futura)
            $table->unsignedBigInteger('validator_id')->nullable();
            $table->timestamp('validation_date')->nullable();

            // Auditing
            $table->text('modification_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_b_results');
    }
};