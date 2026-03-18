<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('acceptances', function (Blueprint $table) {
            $table->timestamp('annulled_at')->nullable()->after('non_conformity_reason');
            $table->text('annulment_reason')->nullable()->after('annulled_at');
        });
    }

    public function down()
    {
        Schema::table('acceptances', function (Blueprint $table) {
            $table->dropColumn(['annulled_at', 'annulment_reason']);
        });
    }
};