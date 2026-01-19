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
        Schema::create('acceptances', function (Blueprint $table) {
            $table->id();
            $table->string('acceptance_number')->unique();
            $table->string('lotto');
            $table->date('sampling_date');
            $table->date('acceptance_date');
            $table->text('plates'); // Usa TEXT per compatibilità con versioni MySQL più vecchie
            $table->text('tests');  // Usa TEXT per compatibilità con versioni MySQL più vecchie
            $table->unsignedBigInteger('user_id'); // Chi ha creato il record
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acceptances');
    }
};
