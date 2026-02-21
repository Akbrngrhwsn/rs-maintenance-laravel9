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
        Schema::table('procurements', function (Blueprint $table) {
            // Menambahkan kolom persetujuan management
            $table->enum('management_status', ['pending', 'approved', 'rejected'])->default('pending')->after('kepala_ruang_status');
            $table->text('management_note')->nullable()->after('management_status');
            $table->timestamp('management_approved_at')->nullable()->after('management_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropColumn(['management_status', 'management_note', 'management_approved_at']);
        });
    }
};