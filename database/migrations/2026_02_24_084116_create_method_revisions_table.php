<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMethodRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('method_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('method_key')->unique(); // 'test_a', 'test_b', 'test_c'
            $table->string('method_name'); // 'MA_09_Misurazione del pH'
            $table->string('revision_string'); // 'MA09 Rev.5 del 20.10.2023'
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
        Schema::dropIfExists('method_revisions');
    }
}
