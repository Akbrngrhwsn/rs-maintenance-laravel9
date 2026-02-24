<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('app_requests', function (Blueprint $table) {
            $table->json('requested_items')->nullable()->after('procurement_estimate');
        });
    }

    public function down()
    {
        Schema::table('app_requests', function (Blueprint $table) {
            $table->dropColumn('requested_items');
        });
    }
};
