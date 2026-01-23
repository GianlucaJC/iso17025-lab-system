<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Pre-popola con i dati iniziali richiesti
        DB::table('instruments')->insert([
            ['name' => 'Incubatore'],
            ['name' => 'Pipetta'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};