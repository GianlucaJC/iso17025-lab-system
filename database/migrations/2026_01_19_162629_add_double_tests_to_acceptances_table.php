<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('acceptances', function (Blueprint $table) {
            $table->text('double_tests')->nullable()->after('tests');
        });
    }

    public function down()
    {
        Schema::table('acceptances', function (Blueprint $table) {
            $table->dropColumn('double_tests');
        });
    }
};
