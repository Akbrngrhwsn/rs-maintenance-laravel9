<?php

namespace App\Http\Controllers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AppRequest;
use App\Models\AppFeature;
use App\Models\Report;
use App\Models\Procurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppRequestController extends Controller
{
    // === 1. REDIRECT INDEX (Agar tidak bingung) ===
    public function index()
    {
        // Secara default arahkan ke halaman Ongoing (Proyek Berjalan)
        return redirect()->route('apps.ongoing');
    }

    // === 2. HALAMAN DAFTAR REQUEST PENDING ===
    public function pending()
    {
        $role = Auth::user()->role;
        
        // Ambil yang statusnya masih menunggu (Pending Direktur atau Approved/Menunggu Admin)
        $query = AppRequest::with('user')->whereIn('status', ['pending_director', 'approved']);
        
        //  hanya melihat miliknya
        if($role == 'kepala_ruang') {
            $query->where('user_id', Auth::id());
        }

        $projects = $query->latest()->get();
        return view('apps.pending', compact('projects'));
    }

    // === 3. HALAMAN DAFTAR PROYEK BERJALAN ===
    public function ongoing()
    {
        $role = Auth::user()->role;
        
        // Ambil yang statusnya 'in_progress' atau 'completed'
        $query = AppRequest::with('user')->whereIn('status', ['in_progress', 'completed']);
        
        if($role == 'kepala_ruang') {
            $query->where('user_id', Auth::id());
        }

        $projects = $query->latest()->get();
        return view('apps.ongoing', compact('projects'));
    }

    // === 4. HALAMAN DETAIL PROYEK (SINGLE PAGE) ===
    public function show($id)
    {
        // Eager load features dan user
        $project = AppRequest::with(['features', 'user'])->findOrFail($id);
        
        // Proteksitidak boleh intip proyeklain
        if(Auth::user()->role == 'kepala_ruang' && $project->user_id != Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke proyek ini.');
        }

        // Logic Dropdown Search (Requirement No. 6)
        // Ambil daftar proyek ringkas untuk navigasi cepat
        $query = AppRequest::select('id', 'nama_aplikasi', 'ticket_number');
        
        if(Auth::user()->role == 'kepala_ruang') {
            $query->where('user_id', Auth::id());
        }
        
        $allProjects = $query->latest()->get();

        return view('apps.show', compact('project', 'allProjects'));
    }

    // === KEPALA RUANG: Form Input Request ===
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['kepala_ruang', 'direktur'])) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate(['nama_aplikasi' => 'required', 'deskripsi' => 'required']);

        $statusAwal = Auth::user()->role === 'direktur' ? 'approved' : 'pending_director';
        $catatanDirektur = Auth::user()->role === 'direktur' ? 'Auto-Approve by Director.' : null;

        \App\Models\AppRequest::create([
            'user_id' => Auth::id(),
            'nama_aplikasi' => $request->nama_aplikasi,
            'deskripsi' => $request->deskripsi,
            'status' => $statusAwal,
            'catatan_direktur' => $catatanDirektur
        ]);

        // === PERUBAHAN DI SINI ===
        // Jika Kepala Ruang, kembali ke Dashboard Kepala Ruang
        if (Auth::user()->role === 'kepala_ruang') {
            return redirect()->route('kepala-ruang.apps.index')
                ->with('success', 'Pengajuan berhasil dikirim ke Direktur.');
        }

        // Jika Direktur, kembali ke List Pending (atau halaman lain sesuai selera)
        return redirect()->route('apps.pending')
                ->with('success', 'Permintaan terkirim langsung ke Admin IT.');
    }

    // === ADMIN: Update Checklist Fitur (Requirement No. 1 - Waktu Selesai) ===
    public function toggleFeature($id)
    {
        if(Auth::user()->role !== 'admin') abort(403);

        $feature = AppFeature::findOrFail($id);
        
        // Toggle status
        $feature->is_done = !$feature->is_done;
        
        // Set Waktu Selesai jika done, null jika undone
        $feature->completed_at = $feature->is_done ? now() : null;
        
        $feature->save();

        return back()->with('success', 'Status fitur diperbarui.');
    }

    // === LOGIKA LAIN (Existing) ===

    public function kepalaRuangIndex(Request $request)
    {
        if(Auth::user()->role !== 'kepala_ruang') abort(403);
        
        $projects = AppRequest::where('user_id', Auth::id())->latest()->get(); // Project biarkan get() atau paginate juga boleh
        
        // Pagination Laporan untuk Kepala Ruang
        $query = Report::orderByRaw("CASE 
            WHEN status = 'Belum Diproses' THEN 1 
            WHEN status = 'Diproses' THEN 2 
            ELSE 3 END")
        ->orderBy('created_at', 'desc');
        
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }
        
        // Ganti get() dengan paginate
        $reports = $query->paginate(10, ['*'], 'reports_page');

        return view('kepala_ruang.index', compact('projects', 'reports'));
    }

    public function approve(Request $request, $id)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        $app = AppRequest::findOrFail($id);
        $app->update([
            'status' => $request->status == 'terima' ? 'approved' : 'rejected',
            'catatan_direktur' => $request->catatan
        ]);

        return back()->with('success', 'Status pengajuan diperbarui.');
    }

    public function adminReview(Request $request, $id)
    {
        if(Auth::user()->role !== 'admin') abort(403);

        $app = AppRequest::findOrFail($id);
        if($app->status !== 'approved') return back()->with('error', 'Status invalid.');

        $newStatus = $request->action === 'terima' ? 'in_progress' : 'rejected';
        $app->update([
            'status' => $newStatus,
            'catatan_admin' => $request->catatan_admin
        ]);

        return back()->with('success', 'Status review admin diperbarui.');
    }

    public function addFeature(Request $request, $id)
    {
        if(Auth::user()->role !== 'admin') abort(403);

        AppFeature::create([
            'app_request_id' => $id,
            'nama_fitur' => $request->nama_fitur
        ]);

        $app = AppRequest::find($id);
        if($app->status == 'approved') {
            $app->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Fitur ditambahkan.');
    }

    public function markComplete($id)
    {
         if(Auth::user()->role !== 'admin') abort(403);
         AppRequest::where('id', $id)->update(['status' => 'completed']);
         return back()->with('success', 'Project selesai!');
    }

    // === DIREKTUR: Halaman Monitoring Laporan ===
    public function directorReports(Request $request)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        // Laporan Aktif (Pending & Proses)
        $activeReports = Report::whereIn('status', ['Belum Diproses', 'Diproses'])
            ->orderByRaw("CASE WHEN status = 'Belum Diproses' THEN 1 ELSE 2 END")
            ->orderBy('urgency', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate(10, ['*'], 'active_page');

        // Laporan Riwayat
        $historyQuery = Report::whereIn('status', ['Selesai', 'Tidak Selesai', 'Ditolak']);
        // ... logic filter date/search sama ...
        if ($request->filled('date')) {
            $historyQuery->whereDate('created_at', $request->date);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $historyQuery->where(function($q) use ($term) {
                $q->where('ticket_number', 'LIKE', "%{$term}%")
                ->orWhere('ruangan', 'LIKE', "%{$term}%");
            });
        }

        $historyReports = $historyQuery->latest()->paginate(10, ['*'], 'history_page');

        return view('director.reports', compact('activeReports', 'historyReports'));
    }

    // Bendahara: Halaman Monitoring Laporan (mirip direktur tanpa menu projek aplikasi)
    public function bendaharaReports(Request $request)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        // Laporan Aktif (Pending & Proses)
        $activeReports = Report::whereIn('status', ['Belum Diproses', 'Diproses'])
            ->orderByRaw("CASE WHEN status = 'Belum Diproses' THEN 1 ELSE 2 END")
            ->orderBy('urgency', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate(10, ['*'], 'active_page');

        // Laporan Riwayat
        $historyQuery = Report::whereIn('status', ['Selesai', 'Tidak Selesai', 'Ditolak']);
        if ($request->filled('date')) {
            $historyQuery->whereDate('created_at', $request->date);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $historyQuery->where(function($q) use ($term) {
                $q->where('ticket_number', 'LIKE', "%{$term}%")
                ->orWhere('ruangan', 'LIKE', "%{$term}%");
            });
        }

        $historyReports = $historyQuery->latest()->paginate(10, ['*'], 'history_page');

        return view('bendahara.reports', compact('activeReports', 'historyReports'));
    }

    // Direktur: Lihat daftar pengadaan yang diajukan oleh Admin IT
    public function directorProcurements(Request $request)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        $tab = $request->get('tab', 'pending'); // 'pending' or 'history'

        $query = Procurement::with('report');

        if($tab === 'history') {
            $query->whereIn('status', ['approved_by_director', 'rejected']);
        } else {
            $query->where('status', 'submitted_to_director');
        }

        // If a single exact date is provided, filter to that date only
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('items', 'LIKE', "%{$term}%")
                  ->orWhereHas('report', function($r) use ($term) {
                      $r->where('ticket_number', 'LIKE', "%{$term}%")
                        ->orWhere('ruangan', 'LIKE', "%{$term}%");
                  });
            });
        }

        $procurements = $query->latest()->get();

        return view('director.procurements', compact('procurements', 'tab'));
    }

    // Bendahara: Lihat daftar pengadaan yang diajukan oleh Admin IT (untuk validasi bendahara)
    public function bendaharaProcurements(Request $request)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $tab = $request->get('tab', 'pending'); // 'pending' or 'history'

        $query = Procurement::with('report');

        if($tab === 'history') {
            $query->whereIn('status', ['approved_by_director', 'rejected']);
        } else {
            $query->where('status', 'submitted_to_bendahara');
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('items', 'LIKE', "%{$term}%")
                  ->orWhereHas('report', function($r) use ($term) {
                      $r->where('ticket_number', 'LIKE', "%{$term}%")
                        ->orWhere('ruangan', 'LIKE', "%{$term}%");
                  });
            });
        }

        $procurements = $query->latest()->get();

        return view('bendahara.procurements', compact('procurements', 'tab'));
    }

    // kepala_ruang: Lihat daftar pengadaan yang diajukan ke 
    public function kepalaRuangProcurements(Request $request)
    {
        if(Auth::user()->role !== 'kepala_ruang') abort(403);

        $tab = $request->get('tab', 'pending'); // 'pending' or 'history'

        $query = Procurement::with('report');

        // Hanya pengadaan untuk ruangan yang dikelola  ini
        $room = Auth::user()->room; // hasOne Room
        if ($room) {
            $query->whereHas('report', function($q) use ($room) {
                $q->where('room_id', $room->id);
            });
        } else {
            // Jika belum punya ruangan, kembalikan kosong
            $procurements = collect();
            return view('kepala_ruang.procurements', compact('procurements', 'tab'));
        }

        if($tab === 'history') {
    // Tambahkan ' ke dalam array
    $query->whereIn('status', [
        'submitted_to_management', // <--- Tambahkan ini
        'submitted_to_bendahara', 
        'submitted_to_director', 
        'approved_by_director', 
        'rejected'
    ]);
} else {
    // Pending tetap sama karena ini tahap awal Kepala Ruang
    $query->where('status', 'submitted_to_kepala_ruang');
}

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('items', 'LIKE', "%{$term}%")
                  ->orWhereHas('report', function($r) use ($term) {
                      $r->where('ticket_number', 'LIKE', "%{$term}%")
                        ->orWhere('ruangan', 'LIKE', "%{$term}%");
                  });
            });
        }

        $procurements = $query->latest()->get();

        return view('kepala_ruang.procurements', compact('procurements', 'tab'));
    }

    // Direktur: ACC sebuah pengadaan
    public function directorApproveProcurement($id)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        $proc = Procurement::findOrFail($id);
        $proc->status = 'approved_by_director';
        $proc->save();

        return back()->with('success', 'Pengadaan berhasil di-ACC.');
    }

    public function directorRejectProcurement(Request $request, $id)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        $proc = Procurement::findOrFail($id);
        $proc->status = 'rejected';
        // save optional director note (catatan)
        $proc->director_note = $request->catatan ?? null;
        $proc->save();

        return back()->with('success', 'Pengadaan berhasil ditolak.');
    }

    // : ACC sebuah pengadaan (teruskan ke Bendahara)
    public function kepalaRuangApproveProcurement($id)
    {
        if(Auth::user()->role !== 'kepala_ruang') abort(403);

        $proc = Procurement::with('report')->findOrFail($id);

        // Validasi: pastikan procurement untuk r
        $room = Auth::user()->room;
        if (!$room || $proc->report->room_id != $room->id) {
            abort(403, 'Anda tidak berwenang memproses pengadaan untuk ruangan ini.');
        }

        $proc->status = 'submitted_to_management';
        $proc->save();

        return back()->with('success', 'Pengadaan berhasil diteruskan ke Management.');
    }

    public function kepalaRuangRejectProcurement(Request $request, $id)
    {
        if(Auth::user()->role !== 'kepala_ruang') abort(403);

        $proc = Procurement::with('report')->findOrFail($id);

        // Validasi: pastikan procurement untuk ruangan ini
        $room = Auth::user()->room;
        if (!$room || $proc->report->room_id != $room->id) {
            abort(403, 'Anda tidak berwenang memproses pengadaan untuk ruangan ini.');
        }

        $proc->status = 'rejected';
        $proc->director_note = $request->catatan ?? null;
        $proc->save();

        return back()->with('success', 'Pengadaan berhasil ditolak oleh kepala ruang.');
    }

    // Management: Lihat daftar pengadaan yang diajukan oleh Kepala Ruang
public function managementProcurements(Request $request)
{
    if(Auth::user()->role !== 'management') abort(403);

    $tab = $request->get('tab', 'pending'); // 'pending' or 'history'
    $query = Procurement::with('report');

    if($tab === 'history') {
        // History: Sudah disetujui management (lanjut ke bendahara/direktur) atau ditolak
        $query->whereIn('status', ['submitted_to_bendahara', 'submitted_to_director', 'approved_by_director', 'rejected']);
    } else {
        // Pending: Menunggu persetujuan Management
        $query->where('status', 'submitted_to_management');
    }

    // Filter Tanggal
    if ($request->filled('date')) {
        $query->whereDate('created_at', $request->date);
    } else {
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    }

    // Filter Pencarian
    if ($request->filled('search')) {
        $term = $request->search;
        $query->where(function($q) use ($term) {
            $q->where('items', 'LIKE', "%{$term}%")
              ->orWhereHas('report', function($r) use ($term) {
                  $r->where('ticket_number', 'LIKE', "%{$term}%")
                    ->orWhere('ruangan', 'LIKE', "%{$term}%");
              });
        });
    }

    $procurements = $query->latest()->get();

    return view('management.procurements', compact('procurements', 'tab'));
}

// Management: ACC sebuah pengadaan (teruskan ke Bendahara)
public function managementApproveProcurement($id)
{
    if(Auth::user()->role !== 'management') abort(403);

    $proc = Procurement::findOrFail($id);
    $proc->status = 'submitted_to_bendahara';
    $proc->save();

    return back()->with('success', 'Pengadaan disetujui oleh Management dan diteruskan ke Bendahara.');
}

// Management: Reject sebuah pengadaan
public function managementRejectProcurement(Request $request, $id)
{
    if(Auth::user()->role !== 'management') abort(403);

    $proc = Procurement::findOrFail($id);
    $proc->status = 'rejected';
    $proc->director_note = $request->catatan ?? null; // Menggunakan kolom yang sama untuk alasan penolakan
    $proc->save();

    return back()->with('success', 'Pengadaan berhasil ditolak oleh Management.');
}

// Management: Lihat daftar laporan kerusakan (sama dengan bendaharaReports)
public function managementReports(Request $request)
{
    if(Auth::user()->role !== 'management') abort(403);

    // Laporan Aktif (Pending & Proses)
    $activeReports = Report::whereIn('status', ['Belum Diproses', 'Diproses'])
        ->orderByRaw("CASE WHEN status = 'Belum Diproses' THEN 1 ELSE 2 END")
        ->orderBy('urgency', 'desc')
        ->orderBy('created_at', 'asc')
        ->paginate(10, ['*'], 'active_page');

    // Laporan Riwayat
    $historyQuery = Report::whereIn('status', ['Selesai', 'Tidak Selesai', 'Ditolak']);
    if ($request->filled('date')) {
        $historyQuery->whereDate('created_at', $request->date);
    }
    if ($request->filled('search')) {
        $term = $request->search;
        $historyQuery->where(function($q) use ($term) {
            $q->where('ticket_number', 'LIKE', "%{$term}%")
            ->orWhere('ruangan', 'LIKE', "%{$term}%");
        });
    }

    $historyReports = $historyQuery->latest()->paginate(10, ['*'], 'history_page');

    return view('management.reports', compact('activeReports', 'historyReports'));
}

    // Bendahara: ACC sebuah pengadaan (teruskan ke Direktur)
    public function bendaharaApproveProcurement($id)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $proc = Procurement::findOrFail($id);
        $proc->status = 'submitted_to_director';
        $proc->save();

        return back()->with('success', 'Pengadaan berhasil diteruskan ke Direktur.');
    }

    public function bendaharaRejectProcurement(Request $request, $id)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $proc = Procurement::findOrFail($id);
        $proc->status = 'rejected';
        $proc->director_note = $request->catatan ?? null;
        $proc->save();

        return back()->with('success', 'Pengadaan berhasil ditolak oleh Bendahara.');
    }

    public function exportSingleAppPdf($id)
    {
        // 1. Cari aplikasi berdasarkan ID
        $app = AppRequest::with(['features', 'user'])->findOrFail($id);

        // 2. Pastikan hanya aplikasi 'completed' yang bisa dicetak
        // (Sesuaikan string status dengan database Anda, kadang 'complete' atau 'completed')
        if (!in_array($app->status, ['completed', 'complete'])) {
            return redirect()->back()->with('error', 'Laporan hanya tersedia untuk aplikasi yang sudah selesai.');
        }

        // 3. LOGIKA QR CODE VALIDASI
        $validator = Auth::user()->name ?? 'Administrator';
        $waktuValidasi = \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
        
        $qrString = "Berita Acara Penyelesaian Aplikasi: {$app->nama_aplikasi}. Divalidasi oleh {$validator} pada {$waktuValidasi}";

        // Generate QR ke Base64 (PNG)
        $qrCode = base64_encode(
            QrCode::format('png')
                ->size(100)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($qrString)
        );

        // 4. Load view dengan variabel tambahan
        $pdf = Pdf::loadView('pdf.single_app_report', compact('app', 'qrCode', 'validator', 'waktuValidasi'));

        // Download
        return $pdf->download('laporan-aplikasi-' . \Illuminate\Support\Str::slug($app->nama_aplikasi) . '.pdf');
    }

    public function exportCompletedAppsPdf()
    {
        // 1. Ambil Data Aplikasi yang Statusnya Completed/Selesai
        // Pastikan nama kolom status sesuai database (misal: 'completed' atau 'Selesai')
        $apps = \App\Models\AppRequest::where('status', 'completed')->get();

        // 2. LOGIKA QR CODE
        $validator = Auth::user()->name ?? 'Administrator';
        $waktuValidasi = now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
        
        $qrString = "Dokumen ini divalidasi oleh {$validator} pada {$waktuValidasi}";

        $qrCode = base64_encode(
            QrCode::format('png')
                ->size(100)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($qrString)
        );

        // 3. Load View dengan variabel lengkap
        $pdf = Pdf::loadView('pdf.completed_apps', compact('apps', 'qrCode', 'validator'));

        return $pdf->download('laporan-aplikasi-selesai.pdf');
    }

    public function deleteFeature($id)
    {
        // 1. Cek Hak Akses (Hanya Admin)
        if(Auth::user()->role !== 'admin') abort(403);

        // 2. Cari Fitur
        $feature = AppFeature::findOrFail($id);

        // 3. Cek Status Project (DIPERBAIKI)
        // Kita gunakan optional chaining (?->) atau pengecekan if
        // Artinya: Jika appRequest ADA, dan statusnya completed, maka tolak.
        // Jika appRequest HILANG (null), kode ini akan dilewati (fitur boleh dihapus).
        
        if($feature->appRequest && $feature->appRequest->status === 'completed') {
             return back()->with('error', 'Tidak bisa menghapus fitur pada proyek yang sudah selesai.');
        }

        // 4. Hapus
        $feature->delete();

        return back()->with('success', 'Fitur berhasil dihapus.');
    }

    public function exportMonthlyAppsPdf(\Illuminate\Http\Request $request)
{
    $monthInput = $request->input('month', date('Y-m'));
    $startDate = \Carbon\Carbon::parse($monthInput)->startOfMonth();
    $endDate = \Carbon\Carbon::parse($monthInput)->endOfMonth();

    // Perbaikan: Gunakan created_at atau updated_at karena completed_at tidak ada di tabel app_requests
    $apps = \App\Models\AppRequest::with(['user', 'features'])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->orWhereBetween('updated_at', [$startDate, $endDate])
        ->get();

    $validator = \Illuminate\Support\Facades\Auth::user()->name;
    
    $qrData = "Validated by: " . $validator . "\nPeriod: " . $startDate->format('F Y');
    $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
        ->size(200)
        ->generate($qrData));

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly_apps_report', [
        'apps' => $apps,
        'startDate' => $startDate,
        'validator' => $validator,
        'qrCode' => $qrCode
    ]);

    return $pdf->download('Laporan_Aplikasi_' . $startDate->format('M_Y') . '.pdf');
}
}