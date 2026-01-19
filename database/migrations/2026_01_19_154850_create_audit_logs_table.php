<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('event'); // es. 'created', 'updated'
            $table->morphs('auditable'); // Crea auditable_id (BIGINT) e auditable_type (VARCHAR)
            $table->text('old_values')->nullable(); // Modificato da json a text
            $table->text('new_values')->nullable(); // Modificato da json a text
            $table->text('modification_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
