<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\AppRequest;
use App\Models\Procurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function check()
    {
        $user = Auth::user();
        $role = $user->role;
        $response = [
            'role' => $role,
            'has_notification' => false,
            'counts' => [] 
        ];

        // --- ADMIN (Tetap) ---
        if ($role === 'admin') {
            $reportCount = Report::where('status', 'Belum Diproses')->count();
            // Hitung AppRequest yang baru masuk ke alur Admin IT
            $appCount = AppRequest::where('status', 'submitted_to_admin')->count();
            // Hitung semua request apps yang pending
            $requestAppsCount = AppRequest::whereIn('status', ['submitted_to_admin', 'submitted_to_management', 'submitted_to_bendahara', 'submitted_to_director'])->count();

            $response['counts'] = [
                'reports' => $reportCount,
                'apps' => $appCount,
                'request_apps' => $requestAppsCount
            ];

            if($reportCount > 0 || $appCount > 0 || $requestAppsCount > 0) $response['has_notification'] = true;
        } 
        // --- DIREKTUR (TAMBAHAN: tangani submitted_to_director dan pending_director) ---
        elseif ($role === 'direktur') {
            // Direktur perlu diberitahu jika ada app request yang dikirim ke direktur
            $pendingApps = AppRequest::whereIn('status', ['submitted_to_director', 'pending_director'])->count();
            $pendingProcurements = Procurement::where('status', 'submitted_to_director')->count();
            // Hitung request apps untuk direktur
            $requestAppsCount = AppRequest::whereIn('status', ['submitted_to_director', 'pending_director'])->count();

            $response['counts'] = [
                'pending_apps' => $pendingApps,
                'pending_procurements' => $pendingProcurements,
                'request_apps' => $requestAppsCount
            ];

            if($pendingApps > 0 || $pendingProcurements > 0 || $requestAppsCount > 0) $response['has_notification'] = true;
        }

        // --- MANAGEMENT (BARU) ---
        elseif ($role === 'management') {
            // Management harus melihat AppRequest yang diteruskan dari Admin
            $appsForManagement = AppRequest::where('status', 'submitted_to_management')->count();
            // dan juga pengadaan yang dialihkan ke management (jika ada)
            $procurementsForManagement = Procurement::where('status', 'submitted_to_management')->count();

            $response['counts'] = [
                'submitted_apps' => $appsForManagement,
                'submitted_procurements' => $procurementsForManagement,
                'request_apps' => $appsForManagement
            ];

            if($appsForManagement > 0 || $procurementsForManagement > 0) $response['has_notification'] = true;
        }
        // --- MANA(BARU) ---
        elseif ($role === 'kepala_ruang') {
            // Manmemantau pengadaan dari Admin IT, tapi hanya untuk ruangan yang dikelolanya
            $room = $user->room; // hasOne(Room::class)

            if ($room) {
                $pendingProcurements = Procurement::where('status', 'submitted_to_kepala_ruang')
                    ->whereHas('report', function ($q) use ($room) {
                        $q->where('room_id', $room->id);
                    })->count();
            } else {
                $pendingProcurements = 0;
            }

            $response['counts'] = [
                'pending_procurements' => $pendingProcurements,
            ];

            if ($pendingProcurements > 0) $response['has_notification'] = true;
        }
        // --- BENDAHARA (UPDATE) ---
        elseif ($role === 'bendahara') {
            // Bendahara memvalidasi pengadaan dari Procurement model
            $pendingProcurements = Procurement::where('status', 'submitted_to_bendahara')->count();
            
            // Hitung AppRequest yang submitted ke bendahara untuk validasi anggaran
            $appsCount = AppRequest::where('procurement_approval_status', 'submitted_to_bendahara')->count();
            
            // Hitung request apps untuk bendahara
            $requestAppsCount = AppRequest::whereIn('status', ['submitted_to_bendahara', 'pending_bendahara'])->count();

            $response['counts'] = [
                'apps' => $appsCount,
                'pending_procurements' => $pendingProcurements,
                'request_apps' => $requestAppsCount
            ];

            if($pendingProcurements > 0 || $appsCount > 0 || $requestAppsCount > 0) $response['has_notification'] = true;
        }

        return response()->json($response);
    }
}