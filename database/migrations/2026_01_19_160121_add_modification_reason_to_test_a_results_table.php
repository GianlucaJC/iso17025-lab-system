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
        Schema::table('test_a_results', function (Blueprint $table) {
            $table->text('modification_reason')->nullable()->after('non_compliance_ref');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('test_a_results', function (Blueprint $table) {
            $table->dropColumn('modification_reason');
        });
    }
};
