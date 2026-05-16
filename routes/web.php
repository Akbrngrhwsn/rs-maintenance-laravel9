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
use App\Http\Controllers\NewItemRequestController;
use App\Http\Controllers\UserRequestController;

// === PORTAL UTAMA (Akses Awal Tanpa Login) ===
Route::get('/', function () {
    // Jika user sudah login, langsung arahkan ke dashboard
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('dashboard');
    }
    // Jika belum login, tampilkan halaman pilihan portal
    return view('portal');
})->name('portal');

// === USER 1: PUBLIC IT MAINTENANCE (Tanpa Login) ===
Route::prefix('it-maintenance')->group(function () {
    Route::get('/', [PublicReportController::class, 'index'])->name('public.home');
    Route::post('/lapor', [PublicReportController::class, 'store'])->name('public.store');
    Route::get('/tracking', [PublicReportController::class, 'tracking'])->name('public.tracking');
});
// === GROUP AUTHENTICATED (Harus Login) ===
Route::middleware('auth')->group(function () {

    // Rute Export PDF Barang Baru (Bisa diakses semua role yang login)
    Route::get('/new-items/{id}/export', [NewItemRequestController::class, 'exportSingle'])->name('new_items.export.single');

    // Rute Export Riwayat Kerusakan (Bisa diakses semua role yang login)
    Route::get('/tracking/export-monthly', [PublicReportController::class, 'exportMonthlyReport'])->name('tracking.export.monthly');

    // Rute untuk Kepala Ruang
    Route::get('/kepala-ruang/new-item-request/create', [NewItemRequestController::class, 'create'])->name('kepala-ruang.new_items.create');
    Route::post('/kepala-ruang/new-item-request/store', [NewItemRequestController::class, 'store'])->name('kepala-ruang.new_items.store');

    // Rute Approval Admin IT
    Route::patch('/admin/new-items/{id}/approve', [NewItemRequestController::class, 'approve'])->defaults('role', 'admin')->name('admin.new_items.approve');
    Route::patch('/admin/new-items/{id}/reject', [NewItemRequestController::class, 'reject'])->name('admin.new_items.reject');

    // Rute Approval Management
    Route::patch('/management/new-items/{id}/approve', [NewItemRequestController::class, 'approve'])->defaults('role', 'management')->name('management.new_items.approve');
    Route::patch('/management/new-items/{id}/reject', [NewItemRequestController::class, 'reject'])->name('management.new_items.reject');

    // Rute Approval Bendahara
    Route::patch('/bendahara/new-items/{id}/approve', [NewItemRequestController::class, 'approve'])->defaults('role', 'bendahara')->name('bendahara.new_items.approve');
    Route::patch('/bendahara/new-items/{id}/reject', [NewItemRequestController::class, 'reject'])->name('bendahara.new_items.reject');

    // Rute Approval Direktur
    Route::patch('/direktur/new-items/{id}/approve', [NewItemRequestController::class, 'approve'])->defaults('role', 'direktur')->name('direktur.new_items.approve');
    Route::patch('/direktur/new-items/{id}/reject', [NewItemRequestController::class, 'reject'])->name('direktur.new_items.reject');

    // --- FITUR UMUM (Notifikasi & Profile) ---
    Route::get('/notifications/check', [NotificationController::class, 'check'])->name('notifications.check');
    Route::get('/notifications/latest-report', [NotificationController::class, 'getLatestReport'])->name('notifications.latest_report');

    Route::get('/tracking/export-monthly', [PublicReportController::class, 'exportTrackingMonthly'])->name('tracking.export.monthly');

    Route::get('/management/procurements', [ProcurementController::class, 'managementIndex'])->name('management.procurements');
    Route::patch('/management/procurements/{id}/approve', [ProcurementController::class, 'managementApprove'])->name('management.procurements.approve');
    Route::patch('/management/procurements/{id}/reject', [ProcurementController::class, 'managementReject'])->name('management.procurements.reject');
    Route::get('/management/procurement/{id}/export', [ProcurementController::class, 'downloadProcurementReportPdf'])->name('management.procurements.export.single');
    
    // Perubahan: Routes untuk management approval pengadaan di level AppRequest
    Route::patch('/management/app/{id}/procurement/approve', [AppRequestController::class, 'managementApproveProcurementForApp'])->name('management.app.procurement.approve');
    Route::patch('/management/app/{id}/procurement/reject', [AppRequestController::class, 'managementRejectProcurementForApp'])->name('management.app.procurement.reject');
    
    // Route untuk membuat pengadaan susulan oleh Admin
Route::post('/admin/apps/{id}/add-procurement', [\App\Http\Controllers\AppRequestController::class, 'addProcurement'])->name('admin.apps.add_procurement');

    Route::get('/management/reports', [AppRequestController::class, 'managementReports'])->name('management.reports');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- KHUSUS DIREKTUR: Monitoring Laporan ---
    Route::get('/director/reports', [AppRequestController::class, 'directorReports'])->name('director.reports');
    // Direktur: Daftar Pengadaan (ACC pengajuan dari Bendahara)
    Route::get('/director/procurements', [ProcurementController::class, 'directorIndex'])->name('director.procurements.index');
    Route::patch('/director/procurement/{id}/approve', [ProcurementController::class, 'directorApprove'])->name('director.procurements.approve');
    Route::patch('/director/procurement/{id}/reject', [ProcurementController::class, 'directorReject'])->name('director.procurements.reject');
    
    // Perubahan: Routes untuk direktur approval pengadaan di level AppRequest (setelah bendahara approve)
    Route::patch('/director/app/{id}/procurement/approve', [AppRequestController::class, 'directorApproveProcurementForApp'])->name('director.app.procurement.approve');
    Route::patch('/director/app/{id}/procurement/reject', [AppRequestController::class, 'directorRejectProcurementForApp'])->name('director.app.procurement.reject');

    // --- KHUSUS BENDAHARA: Monitoring Laporan & Pengadaan ---
    Route::get('/bendahara/reports', [AppRequestController::class, 'bendaharaReports'])->name('bendahara.reports');
    Route::get('/bendahara/procurements', [ProcurementController::class, 'bendaharaIndex'])->name('bendahara.procurements.index');
    
    // Perubahan: Routes untuk bendahara approval pengadaan di level AppRequest
    Route::patch('/bendahara/app/{id}/procurement/approve', [AppRequestController::class, 'bendaharaApproveProcurementForApp'])->name('bendahara.app.procurement.approve');
    Route::patch('/bendahara/app/{id}/procurement/reject', [AppRequestController::class, 'bendaharaRejectProcurementForApp'])->name('bendahara.app.procurement.reject');

    // Export routes for Director and Kepala Ruang (PDF) - use ProcurementController for procurements
    Route::get('/director/procurements/export-weekly', [\App\Http\Controllers\AdminReportController::class, 'exportProcurementsWeekly'])->name('director.procurements.export.weekly');
    Route::get('/director/procurement/{id}/export', [ProcurementController::class, 'downloadProcurementReportPdf'])->name('director.procurements.export.single');

    Route::get('/kepala-ruang/procurements/export-weekly', [\App\Http\Controllers\AdminReportController::class, 'exportProcurementsWeekly'])->name('kepala-ruang.procurements.export.weekly');
    Route::get('/kepala-ruang/procurement/{id}/export', [ProcurementController::class, 'downloadProcurementReportPdf'])->name('kepala-ruang.procurements.export.single');

    // --- KHUSUS KEPALA RUANG: Dashboard Sendiri ---
    Route::get('/kepala-ruang/apps', [AppRequestController::class, 'kepalaRuangIndex'])->name('kepala-ruang.apps.index');
    // Kepala Ruang: Daftar Pengadaan (lihat pengadaan yang diajukan ke departemennya)
    Route::get('/kepala-ruang/procurements', [ProcurementController::class, 'kepalaRuangIndex'])->name('kepala-ruang.procurements.index');
    // Kepala Ruang: ACC pengadaan (teruskan ke Management)
    Route::patch('/kepala-ruang/procurement/{id}/approve', [ProcurementController::class, 'kepalaRuangApprove'])->name('kepala-ruang.procurements.approve');
    Route::patch('/kepala-ruang/procurement/{id}/reject', [ProcurementController::class, 'kepalaRuangReject'])->name('kepala-ruang.procurements.reject');

    // Bendahara: ACC pengadaan (teruskan ke Direktur)
    Route::patch('/bendahara/procurement/{id}/approve', [ProcurementController::class, 'bendaharaApprove'])->name('bendahara.procurements.approve');
    Route::patch('/bendahara/procurement/{id}/reject', [ProcurementController::class, 'bendaharaReject'])->name('bendahara.procurements.reject');
    Route::get('/bendahara/procurement/{id}/export', [ProcurementController::class, 'downloadProcurementReportPdf'])->name('bendahara.procurements.export.single');

    // --- GROUP KHUSUS ADMIN IT (Dashboard & Pengadaan) ---
    // Diproteksi oleh Middleware EnsureUserIsAdmin
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {

    Route::middleware(['auth', 'admin'])->group(function () {
    // Maintenance Procurements
    Route::delete('/admin/procurements/{id}', [ProcurementController::class, 'destroy'])->name('admin.procurements.destroy');
    
    // Tambahkan ini di dalam Route::middleware(EnsureUserIsAdmin::class)->group(function () { ... })
    Route::patch('/admin/new-items/{id}/finish', [App\Http\Controllers\NewItemRequestController::class, 'finish'])->name('admin.new_items.finish');

    // New Item Procurements
    Route::get('/admin/new-items/{id}/edit', [NewItemRequestController::class, 'edit'])->name('admin.new_items.edit');
    Route::patch('/admin/new-items/{id}/update', [NewItemRequestController::class, 'update'])->name('admin.new_items.update');
    Route::delete('/admin/new-items/{id}/destroy', [NewItemRequestController::class, 'destroy'])->name('admin.new_items.destroy');

    // App Procurements (Gunakan route yang sudah dibuat sebelumnya di AppRequestController)
});

        // CRUD & Penugasan Teknisi IT
        Route::get('/admin/it-staff', [App\Http\Controllers\ItStaffController::class, 'index'])->name('admin.it_staff.index');
        Route::post('/admin/it-staff', [App\Http\Controllers\ItStaffController::class, 'store'])->name('admin.it_staff.store');
        Route::patch('/admin/it-staff/{id}', [App\Http\Controllers\ItStaffController::class, 'update'])->name('admin.it_staff.update');
        Route::delete('/admin/it-staff/{id}', [App\Http\Controllers\ItStaffController::class, 'destroy'])->name('admin.it_staff.destroy');
        Route::patch('/admin/it-staff/{id}/on-duty', [App\Http\Controllers\ItStaffController::class, 'setOnDuty'])->name('admin.it_staff.onduty');
        
        // Dashboard Admin
        Route::get('/admin/dashboard', [AdminReportController::class, 'index'])->name('dashboard');
        Route::patch('/admin/report/{id}/acc', [AdminReportController::class, 'acc'])->name('admin.acc');
        Route::patch('/admin/report/{id}/validate', [AdminReportController::class, 'validasi'])->name('admin.validate');
        Route::get('/admin/new-reports', [AdminReportController::class, 'checkNewReports'])->name('admin.new-reports');

        // --- TAMBAHAN: ROUTE EDIT & HAPUS LAPORAN KERUSAKAN ---
        Route::get('/admin/report/{id}/edit', [AdminReportController::class, 'edit'])->name('admin.report.edit');
        Route::patch('/admin/report/{id}/update', [AdminReportController::class, 'update'])->name('admin.report.update');
        Route::delete('/admin/report/{id}/delete', [AdminReportController::class, 'destroy'])->name('admin.report.destroy');
        
        // --- ROUTE EDIT & HAPUS REQUEST APLIKASI ---
        Route::get('/admin/apps/{id}/edit', [AppRequestController::class, 'edit'])->name('admin.apps.edit');
        Route::patch('/admin/apps/{id}/update', [AppRequestController::class, 'update'])->name('admin.apps.update');
        Route::delete('/admin/apps/{id}/delete', [AppRequestController::class, 'destroy'])->name('admin.apps.destroy');
        Route::get('/admin/apps/{id}/edit-procurement', [AppRequestController::class, 'editProcurement'])->name('admin.apps.edit-procurement');
        Route::patch('/admin/apps/{id}/update-procurement', [AppRequestController::class, 'updateProcurement'])->name('admin.apps.update-procurement');

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
        Route::patch('/admin/users/{id}/password', [UserManagementController::class, 'updatePassword'])->name('admin.users.update-password');
        Route::patch('/admin/users/{id}/assign-room', [UserManagementController::class, 'assignRoom'])->name('admin.users.assignRoom');
        Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
        
        // Di dalam group middleware EnsureUserIsAdmin
        Route::get('/admin/reports/export-daily', [AdminReportController::class, 'exportDailyPdf'])->name('admin.export.daily');
        Route::post('/admin/reports/mark-read', [AdminReportController::class, 'markAsRead'])->name('admin.reports.mark_read');
        // Admin: Manag Rooms and assign kepala ruang
        Route::get('/admin/rooms', [AdminRoomsController::class, 'index'])->name('admin.rooms.index');
        Route::post('/admin/rooms', [AdminRoomsController::class, 'store'])->name('admin.rooms.store');
        Route::patch('/admin/rooms/{id}', [AdminRoomsController::class, 'update'])->name('admin.rooms.update');

        // Di dalam group prefix('apps') atau Admin
        Route::get('/apps/export-completed', [AppRequestController::class, 'exportCompletedAppsPdf'])->name('apps.export.completed');

        // Export Pengadaan (PDF)
        Route::get('/admin/procurements/export-weekly', [AdminReportController::class, 'exportProcurementsWeekly'])->name('admin.procurements.export.weekly');
        Route::get('/admin/procurement/{id}/export', [ProcurementController::class, 'downloadProcurementReportPdf'])->name('admin.procurements.export.single');
        // Export Bulanan (PDF)
        Route::get('/admin/procurements/export-monthly', [AdminReportController::class, 'exportProcurementsMonthly'])->name('admin.procurements.export.monthly');

        Route::delete('/admin/rooms/{id}', [AdminRoomsController::class, 'destroy'])->name('admin.rooms.destroy');

        Route::get('/admin/reports/export-monthly', [AdminReportController::class, 'exportMonthlyPdf'])->name('admin.export.monthly');
    
        Route::get('/admin/apps/export-monthly', [AppRequestController::class, 'exportMonthlyAppsPdf'])->name('admin.apps.export.monthly');

        // Admin IT: Process App Requests (fill procurement estimate or accept/reject)
        Route::patch('/admin/apps/{id}/process', [AppRequestController::class, 'adminProcess'])->name('admin.apps.process');

        // Route untuk pengajuan ulang pengadaan aplikasi yang ditolak
        Route::patch('/admin/apps/{id}/reprocess-procurement', [AppRequestController::class, 'reprocessProcurement'])
            ->name('admin.apps.reprocess_procurement')
            ->middleware(['auth']);

        Route::patch('/admin/apps/{id}/finish-procurement', [AppRequestController::class, 'finishProcurement'])
    ->name('admin.apps.finish_procurement');

    });

    Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/it-notes', [ITNoteController::class, 'index'])->name('it-notes.index');
    Route::post('/admin/it-notes', [ITNoteController::class, 'store'])->name('it-notes.store');
    Route::get('/admin/it-notes/export', [ITNoteController::class, 'exportPdf'])->name('it-notes.export');
    Route::resource('user-requests', UserRequestController::class);
    Route::patch('user-requests/{id}/status', [UserRequestController::class, 'updateStatus'])->name('user-requests.update-status');
    Route::get('user-requests-export', [UserRequestController::class, 'exportPdf'])->name('user-requests.export');
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

        // Route untuk Admin menandai pengadaan telah selesai
        Route::patch('/admin/procurement/{id}/finish', [\App\Http\Controllers\ProcurementController::class, 'finish'])->name('admin.procurement.finish');
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