<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\AppRequestController;
use App\Http\Controllers\PublicReportController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminRoomsController;
use App\Http\Controllers\ProcurementController;
use App\Http\Middleware\EnsureUserIsAdmin; 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ITNoteController;

// === USER 1: PUBLIC (Tanpa Login) ===
Route::get('/', [PublicReportController::class, 'index'])->name('public.home');
Route::post('/lapor', [PublicReportController::class, 'store'])->name('public.store');
Route::get('/tracking', [PublicReportController::class, 'tracking'])->name('public.tracking');

// === GROUP AUTHENTICATED (Harus Login) ===
Route::middleware('auth')->group(function () {

    // --- FITUR UMUM (Notifikasi & Profile) ---
    Route::get('/notifications/check', [NotificationController::class, 'check'])->name('notifications.check');

    Route::get('/management/procurements', [AppRequestController::class, 'managementProcurements'])->name('management.procurements');
    Route::patch('/management/procurements/{id}/approve', [AppRequestController::class, 'managementApproveProcurement'])->name('management.procurements.approve');
    Route::patch('/management/procurements/{id}/reject', [AppRequestController::class, 'managementRejectProcurement'])->name('management.procurements.reject');
    
    // Perubahan: Routes untuk management approval pengadaan di level AppRequest
    Route::patch('/management/app/{id}/procurement/approve', [AppRequestController::class, 'managementApproveProcurementForApp'])->name('management.app.procurement.approve');
    Route::patch('/management/app/{id}/procurement/reject', [AppRequestController::class, 'managementRejectProcurementForApp'])->name('management.app.procurement.reject');
    
    Route::get('/management/reports', [AppRequestController::class, 'managementReports'])->name('management.reports');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- KHUSUS DIREKTUR: Monitoring Laporan ---
    Route::get('/director/reports', [AppRequestController::class, 'directorReports'])->name('director.reports');
    // Direktur: Daftar Pengadaan (ACC pengajuan dari Admin IT)
    Route::get('/director/procurements', [AppRequestController::class, 'directorProcurements'])->name('director.procurements.index');
    Route::patch('/director/procurement/{id}/approve', [AppRequestController::class, 'directorApproveProcurement'])->name('director.procurements.approve');
    Route::patch('/director/procurement/{id}/reject', [AppRequestController::class, 'directorRejectProcurement'])->name('director.procurements.reject');
    
    // Perubahan: Routes untuk direktur approval pengadaan di level AppRequest (setelah bendahara approve)
    Route::patch('/director/app/{id}/procurement/approve', [AppRequestController::class, 'directorApproveProcurementForApp'])->name('director.app.procurement.approve');
    Route::patch('/director/app/{id}/procurement/reject', [AppRequestController::class, 'directorRejectProcurementForApp'])->name('director.app.procurement.reject');

    // --- KHUSUS BENDAHARA: Monitoring Laporan & Pengadaan ---
    Route::get('/bendahara/reports', [AppRequestController::class, 'bendaharaReports'])->name('bendahara.reports');
    Route::get('/bendahara/procurements', [AppRequestController::class, 'bendaharaProcurements'])->name('bendahara.procurements.index');
    
    // Perubahan: Routes untuk bendahara approval pengadaan di level AppRequest
    Route::patch('/bendahara/app/{id}/procurement/approve', [AppRequestController::class, 'bendaharaApproveProcurementForApp'])->name('bendahara.app.procurement.approve');
    Route::patch('/bendahara/app/{id}/procurement/reject', [AppRequestController::class, 'bendaharaRejectProcurementForApp'])->name('bendahara.app.procurement.reject');

    // Export routes for Director and Kepala Ruang (PDF) - reuse AdminReportController methods
    Route::get('/director/procurements/export-weekly', [\App\Http\Controllers\AdminReportController::class, 'exportProcurementsWeekly'])->name('director.procurements.export.weekly');
    Route::get('/director/procurement/{id}/export', [\App\Http\Controllers\AdminReportController::class, 'exportSingleProcurement'])->name('director.procurements.export.single');

    Route::get('/kepala-ruang/procurements/export-weekly', [\App\Http\Controllers\AdminReportController::class, 'exportProcurementsWeekly'])->name('kepala-ruang.procurements.export.weekly');
    Route::get('/kepala-ruang/procurement/{id}/export', [\App\Http\Controllers\AdminReportController::class, 'exportSingleProcurement'])->name('kepala-ruang.procurements.export.single');

    // --- KHUSUS : Dashboard Sendiri ---
    Route::get('/kepala-ruang/apps', [AppRequestController::class, 'kepalaRuangIndex'])->name('kepala-ruang.apps.index');
    // Mana: Daftar Pengadaan (lihat pengadaan yang diajukan ke Manar)
    Route::get('/kepala-ruang/procurements', [AppRequestController::class, 'kepalaRuangProcurements'])->name('kepala-ruang.procurements.index');
    // Mana: ACC pengadaan (teruskan ke Bendahara)
    Route::patch('/kepala-ruang/procurement/{id}/approve', [AppRequestController::class, 'kepalaRuangApproveProcurement'])->name('kepala-ruang.procurements.approve');
    Route::patch('/kepala-ruang/procurement/{id}/reject', [AppRequestController::class, 'kepalaRuangRejectProcurement'])->name('kepala-ruang.procurements.reject');

    // Bendahara: ACC pengadaan (teruskan ke Direktur)
    Route::patch('/bendahara/procurement/{id}/approve', [AppRequestController::class, 'bendaharaApproveProcurement'])->name('bendahara.procurements.approve');
    Route::patch('/bendahara/procurement/{id}/reject', [AppRequestController::class, 'bendaharaRejectProcurement'])->name('bendahara.procurements.reject');

    // --- GROUP KHUSUS ADMIN IT (Dashboard & Pengadaan) ---
    // Diproteksi oleh Middleware EnsureUserIsAdmin
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {
        
        // Dashboard Admin
        Route::get('/admin/dashboard', [AdminReportController::class, 'index'])->name('dashboard');
        Route::patch('/admin/report/{id}/acc', [AdminReportController::class, 'acc'])->name('admin.acc');
        Route::patch('/admin/report/{id}/validate', [AdminReportController::class, 'validasi'])->name('admin.validate');
        Route::get('/admin/new-reports', [AdminReportController::class, 'checkNewReports'])->name('admin.new-reports');

        // Pengadaan (Procurement)
        Route::get('/admin/report/{id}/procurement', [ProcurementController::class, 'create'])->name('procurement.create');
        Route::post('/admin/report/{id}/procurement', [ProcurementController::class, 'store'])->name('procurement.store');
        Route::post('/admin/procurement/{id}/convert', [ProcurementController::class, 'convert'])->name('admin.procurement.convert');
        // Admin: Daftar Pengadaan (lihat semua pengadaan)
        Route::get('/admin/procurements', [ProcurementController::class, 'index'])->name('admin.procurements.index');
        Route::get('/admin/procurement/{id}/edit', [ProcurementController::class, 'edit'])->name('procurement.edit');
        Route::patch('/admin/procurement/{id}', [ProcurementController::class, 'update'])->name('procurement.update');

        //User Manament
        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::patch('/admin/users/{id}/role', [UserManagementController::class, 'updateRole'])->name('admin.users.update');
        Route::patch('/admin/users/{id}/assign-room', [UserManagementController::class, 'assignRoom'])->name('admin.users.assignRoom');
        Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
        
        // Di dalam group middleware EnsureUserIsAdmin
        Route::get('/admin/reports/export-daily', [AdminReportController::class, 'exportDailyPdf'])->name('admin.export.daily');

        // Admin: Manag Rooms and assign kepala ruang
        Route::get('/admin/rooms', [AdminRoomsController::class, 'index'])->name('admin.rooms.index');
        Route::post('/admin/rooms', [AdminRoomsController::class, 'store'])->name('admin.rooms.store');
        Route::patch('/admin/rooms/{id}', [AdminRoomsController::class, 'update'])->name('admin.rooms.update');

        // Di dalam group prefix('apps') atau Admin
        Route::get('/apps/export-completed', [AppRequestController::class, 'exportCompletedAppsPdf'])->name('apps.export.completed');

        // Export Pengadaan (PDF)
        Route::get('/admin/procurements/export-weekly', [AdminReportController::class, 'exportProcurementsWeekly'])->name('admin.procurements.export.weekly');
        Route::get('/admin/procurement/{id}/export', [AdminReportController::class, 'exportSingleProcurement'])->name('admin.procurements.export.single');
        // Export Bulanan (PDF)
        Route::get('/admin/procurements/export-monthly', [AdminReportController::class, 'exportProcurementsMonthly'])->name('admin.procurements.export.monthly');

        Route::delete('/admin/rooms/{id}', [AdminRoomsController::class, 'destroy'])->name('admin.rooms.destroy');

        Route::get('/admin/reports/export-monthly', [AdminReportController::class, 'exportMonthlyPdf'])->name('admin.export.monthly');
    
        Route::get('/admin/apps/export-monthly', [AppRequestController::class, 'exportMonthlyAppsPdf'])->name('admin.apps.export.monthly');

        // Admin IT: Process App Requests (fill procurement estimate or accept/reject)
        Route::patch('/admin/apps/{id}/process', [AppRequestController::class, 'adminProcess'])->name('admin.apps.process');

    });

    Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/it-notes', [ITNoteController::class, 'index'])->name('it-notes.index');
    Route::post('/admin/it-notes', [ITNoteController::class, 'store'])->name('it-notes.store');
    Route::get('/admin/it-notes/export', [ITNoteController::class, 'exportPdf'])->name('it-notes.export');
    });

    // --- GROUP APP REQUEST (Project Aplikasi) - UPDATED ---
    Route::prefix('apps')->group(function () {
        
        // 1. Route Redirect Index (Agar /apps mengarah ke ongoing/pending)
        Route::get('/', [AppRequestController::class, 'index'])->name('apps.index');
        
        // 2. Halaman LIST (Baru: Pending & Ongoing)
        Route::get('/list/pending', [AppRequestController::class, 'pending'])->name('apps.pending');
        Route::get('/list/ongoing', [AppRequestController::class, 'ongoing'])->name('apps.ongoing');

        // 3. Halaman SINGLE PROJECT (Detail)
        Route::get('/detail/{id}', [AppRequestController::class, 'show'])->name('apps.show');

        // TAMBAHKAN INI: Route untuk download PDF per aplikasi
        Route::get('/detail/{id}/export', [AppRequestController::class, 'exportSingleAppPdf'])->name('apps.export.single');
        
        // Route untuk download laporan pengadaan
        Route::get('/detail/{id}/procurement/export', [AppRequestController::class, 'downloadProcurementReport'])->name('apps.procurement.export');
        
        // --- ACTION ROUTES (Form Submit & Process) ---
        
        // Khusus Kepala Ruang: Buat Request
        Route::post('/create', [AppRequestController::class, 'store'])->name('apps.store');
        
        // Khusus Direktur: Approve
        Route::patch('/{id}/approve', [AppRequestController::class, 'approve'])->name('apps.approve');

        // Khusus Management: Approve / Reject (forwarding logic based on needs_procurement)
        Route::patch('/{id}/management-approve', [AppRequestController::class, 'managementApprove'])->name('apps.management_approve');
        Route::patch('/{id}/management-reject', [AppRequestController::class, 'managementReject'])->name('apps.management_reject');
        
        // Khusus Admin: Kelola Fitur & Review
        Route::post('/{id}/feature', [AppRequestController::class, 'addFeature'])->name('apps.add_feature');
        Route::patch('/feature/{id}/toggle', [AppRequestController::class, 'toggleFeature'])->name('apps.toggle_feature');
        Route::patch('/{id}/complete', [AppRequestController::class, 'markComplete'])->name('apps.complete');
        Route::patch('/{id}/admin-review', [AppRequestController::class, 'adminReview'])->name('apps.admin_review');

        // Bendahara: Approve/Reject directly on AppRequest when no Procurement record exists
        Route::patch('/{id}/bendahara-approve', [AppRequestController::class, 'bendaharaApproveAppRequest'])->name('apps.bendahara_approve');
        Route::patch('/{id}/bendahara-reject', [AppRequestController::class, 'bendaharaRejectAppRequest'])->name('apps.bendahara_reject');

        Route::delete('/apps/features/{id}/delete', [AppRequestController::class, 'deleteFeature'])->name('apps.delete_feature');
    });

    // --- MEETINGS (RAPAT) ---
    Route::get('/meetings', [\App\Http\Controllers\MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/meetings', [\App\Http\Controllers\MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{id}', [\App\Http\Controllers\MeetingController::class, 'show'])->name('meetings.show');
    Route::get('/meetings/{id}/edit', [\App\Http\Controllers\MeetingController::class, 'edit'])->name('meetings.edit');
    Route::patch('/meetings/{id}', [\App\Http\Controllers\MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/meetings/{id}', [\App\Http\Controllers\MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('/meetings/{id}/export', [\App\Http\Controllers\MeetingController::class, 'exportPdf'])->name('meetings.export');
});

require __DIR__.'/auth.php';