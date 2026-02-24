<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('app_requests', function (Blueprint $table) {
            $table->decimal('procurement_estimate', 12, 2)->nullable()->after('needs_procurement');
        });
    }

    public function down()
    {
        Schema::table('app_requests', function (Blueprint $table) {
            $table->dropColumn('procurement_estimate');
        });
    }
};
