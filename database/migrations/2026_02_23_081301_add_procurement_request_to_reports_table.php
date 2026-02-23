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
    Schema::table('reports', function (Blueprint $table) {
        $table->boolean('needs_procurement')->default(false); // Apakah user minta pengadaan
        $table->json('procurement_items_request')->nullable();
        $table->string('procurement_status')->nullable(); // 'pending_admin', 'approved', 'rejected'
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            //
        });
    }
};
