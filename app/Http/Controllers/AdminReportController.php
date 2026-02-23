<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Report;
use Illuminate\Http\Request;
use Carbon\Carbon; 
use App\Models\Procurement;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth; // Tambahan: Import Auth untuk ambil nama user

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Pending (Urutkan Urgency via SQL agar bisa paginate)
        $pendingReports = Report::where('status', 'Belum Diproses')
            ->orderByRaw("CASE 
                WHEN urgency = 'tinggi' THEN 3 
                WHEN urgency = 'sedang' THEN 2 
                ELSE 1 END DESC")
            ->orderBy('created_at', 'asc')
            ->paginate(10, ['*'], 'pending_page'); 

        // 2. Sedang Dikerjakan
        $processedReports = Report::where('status', 'Diproses')
            ->orderBy('created_at', 'asc')
            ->paginate(10, ['*'], 'process_page');

        // 3. Riwayat (History)
        $historyQuery = Report::whereIn('status', ['Selesai', 'Tidak Selesai', 'Ditolak']);

        if ($request->has('date') && $request->date != '') {
            $historyQuery->whereDate('created_at', $request->date);
        }

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $historyQuery->where(function($q) use ($searchTerm) {
                $q->where('ticket_number', 'LIKE', "%{$searchTerm}%")
                ->orWhere('ruangan', 'LIKE', "%{$searchTerm}%");
            });
        }

        $historyReports = $historyQuery->latest()
            ->paginate(10, ['*'], 'history_page');

        return view('dashboard', compact('pendingReports', 'processedReports', 'historyReports'));
    }

    public function convertToProcurement($id)
    {
        $report = Report::findOrFail($id);

        // 1. Buat Pengadaan Baru dengan data barang dari user
        $procurement = Procurement::create([
            'report_id' => $report->id,
            'status' => 'submitted_to_kepala_ruang', // Langsung masuk alur estafet
            'items' => $report->procurement_items_request, // Data barang otomatis terisi
        ]);

        // 2. Tandai laporan selesai diproses admin
        $report->update(['procurement_status' => 'approved']);

        return back()->with('success', 'Data pengadaan berhasil dibuat dan diteruskan ke Kepala Ruang.');
    }

    public function acc($id)
    {
        $report = Report::findOrFail($id);
        if ($report->status !== 'Belum Diproses') {
            return back()->with('warning', 'Laporan ini sudah diproses sebelumnya.');
        }
        $report->status = 'Diproses';
        $report->save();
        return back()->with('success', 'Laporan berhasil di-ACC. Segera lakukan perbaikan!');
    }

    public function validasi(Request $request, $id)
    {
        $request->validate([
            'status_akhir' => 'required|in:Selesai,Tidak Selesai'
        ]);

        if ($request->status_akhir === 'Selesai') {
            $request->validate([
                'tindakan_teknisi' => 'required|string|min:5',
            ]);
        } else {
            $request->validate([
                'tindakan_teknisi' => 'required|string',
            ]);
        }

        $report = Report::findOrFail($id);
        $report->status = $request->status_akhir;
        $report->tindakan_teknisi = $request->tindakan_teknisi;
        $report->save();
        $msg = $report->status == 'Selesai' ? 'Laporan ditandai SELESAI.' : 'Laporan ditandai TIDAK SELESAI (Masuk Pengadaan).';
        return back()->with('success', $msg);
    }

    public function checkNewReports()
    {
        $count = Report::where('status', 'Belum Diproses')->count();
        return response()->json(['new_reports' => $count]);
    }

    // --- FITUR EXPORT PDF DENGAN QR CODE ---
    public function exportDailyPdf(Request $request)
    {
        // 1. Ambil data
        $date = $request->input('date', date('Y-m-d'));
        $reports = Report::whereDate('created_at', $date)->get();

        // 2. LOGIKA QR CODE (Ini yang kurang di kode Anda)
        // Pastikan Auth di-import di paling atas: use Illuminate\Support\Facades\Auth;
        $validator = \Illuminate\Support\Facades\Auth::user()->name ?? 'Administrator';
        $waktuValidasi = \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
        
        $qrString = "Telah divalidasi oleh {$validator} pada tanggal {$waktuValidasi}";

        // Generate QR ke Base64 (gunakan SVG agar tidak butuh ekstensi imagick)
        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(100)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($qrString)
        );
        $qrMime = 'image/svg+xml';
        $qrIsSvg = true;

        // 3. Kirim variabel $qrCode ke View
        $pdf = Pdf::loadView('pdf.daily_report', compact('reports', 'date', 'qrCode', 'validator', 'waktuValidasi', 'qrMime', 'qrIsSvg'));

        return $pdf->download("laporan-harian-{$date}.pdf");
    }

    public function exportProcurementsWeekly(Request $request)
{
    // 1. Cek Library PDF
    if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        return response("PDF package not installed.", 500);
    }

    // --- LOGIKA TANGGAL ANDA (SUDAH BENAR) ---
    $month = $request->input('month'); 
    $weekNumber = $request->input('week');
    $date = $request->input('date');

    if ($month && $weekNumber) {
        try {
            $carbonMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $carbonMonth = \Carbon\Carbon::now()->startOfMonth();
        }

        $monthStart = $carbonMonth->copy()->startOfMonth();
        $monthEnd = $carbonMonth->copy()->endOfMonth();

        // Hitung minggu ke-X
        $approx = $monthStart->copy()->addDays((max(1, (int)$weekNumber) - 1) * 7);
        $start = $approx->copy()->startOfWeek()->startOfDay();
        $end = $start->copy()->endOfWeek()->endOfDay();

        // Clamp (jaga agar tidak keluar bulan)
        if ($start->lessThan($monthStart)) $start = $monthStart->copy()->startOfDay();
        if ($end->greaterThan($monthEnd)) $end = $monthEnd->copy()->endOfDay();

        $fileSuffix = $monthStart->format('Ym') . '-w' . (int)$weekNumber;
        $weekLabel = 'Minggu ke ' . (int)$weekNumber . ' (' . $start->locale('id')->isoFormat('D MMMM Y') . ' - ' . $end->locale('id')->isoFormat('D MMMM Y') . ')';
    } else {
        try {
            $carbon = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::now();
        } catch (\Exception $e) {
            $carbon = \Carbon\Carbon::now();
        }

        $start = $carbon->startOfWeek()->startOfDay();
        $end = $carbon->endOfWeek()->endOfDay();
        $fileSuffix = $start->format('Ymd');
        $weekLabel = $start->locale('id')->isoFormat('D MMMM Y') . ' - ' . $end->locale('id')->isoFormat('D MMMM Y');
    }

    // 2. Ambil Data
    $reports = \App\Models\Report::with(['procurement'])
        ->whereBetween('created_at', [$start, $end])
        ->orderBy('created_at', 'asc')
        ->get();

    // 3. LOGIKA QR CODE (INI YANG KITA TAMBAHKAN)
    // Pastikan use Auth dan QrCode di atas controller
    $validator = \Illuminate\Support\Facades\Auth::user()->name ?? 'Administrator';
    $waktuValidasi = \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
    
    // Isi QR Code
    $qrString = "Laporan Mingguan Maintenance. Periode: {$weekLabel}. Valid: {$validator}";

    // Generate Gambar (SVG to avoid imagick dependency)
    $qrCode = base64_encode(
        \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(100)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($qrString)
    );
    $qrMime = 'image/svg+xml';
    $qrIsSvg = true;

    // 4. Load View
    // JANGAN LUPA masukkan 'qrCode', 'validator', 'waktuValidasi' ke compact
    // Variabel 'dateLabel' kita isi dengan $weekLabel agar kompatibel dengan view sebelumnya
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.weekly_report', compact(
        'reports', 
        'weekLabel', 
        'start', 
        'end',
        'qrCode',           // <--- PENTING
        'validator',        // <--- PENTING
        'waktuValidasi',    // <--- PENTING
        'fileSuffix',       // Opsional
        'qrMime',
        'qrIsSvg'
    ));

    // Agar variabel dateLabel di view tetap jalan (jika pakai view versi sebelumnya)
    $pdf->getDomPDF()->getCanvas()->page_script('$pdf->set_opacity(1);'); 

    return $pdf->download('laporan-mingguan-maintenance-' . $fileSuffix . '.pdf');
}

    public function exportProcurementsMonthly(Request $request)
    {
        // 1. Cek Library PDF
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return response("PDF package not installed.", 500);
        }

        // 2. Logika Tanggal & Data
        $month = $request->input('month', date('Y-m'));
        try {
            $carbon = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $carbon = \Carbon\Carbon::now()->startOfMonth();
        }

        $start = $carbon->copy()->startOfMonth()->startOfDay();
        $end = $carbon->copy()->endOfMonth()->endOfDay();

        $procurements = Procurement::with(['report'])->whereBetween('created_at', [$start, $end])->get();
        $monthLabel = $carbon->locale('id')->isoFormat('MMMM Y');

        // 3. LOGIKA QR CODE (Baru)
        $validator = \Illuminate\Support\Facades\Auth::user()->name ?? 'Administrator';
        $waktuValidasi = \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
        
        $qrString = "Laporan Pengadaan Bulanan periode {$monthLabel}. Divalidasi oleh {$validator} pada {$waktuValidasi}";

        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(100)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($qrString)
        );
        $qrMime = 'image/svg+xml';
        $qrIsSvg = true;

        // 4. Kirim ke View (Tambahkan qrCode, validator, waktuValidasi)
        $pdf = Pdf::loadView('pdf.procurements_monthly', compact('procurements', 'monthLabel', 'qrCode', 'validator', 'waktuValidasi', 'qrMime', 'qrIsSvg'));
        
        return $pdf->download('laporan-pengadaan-bulanan-' . $carbon->format('Ym') . '.pdf');
    }

    // app/Http/Controllers/AdminReportController.php

public function exportSingleProcurement($id)
{
    // 1. Cek Library PDF
    if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
        return response("PDF package not installed.", 500);
    }

    // 2. Load Data Procurement
    $procurement = Procurement::with(['report'])->findOrFail($id);

    // 3. Helper Generate QR
    $generateQr = function($text) {
        return base64_encode(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(100)->margin(0)->errorCorrection('M')->generate($text)
        );
    };

    // 4. LOGIKA ESTAFET QR CODE BARU
    // Urutan Status: submitted_to_kepala_ruang -> submitted_to_management -> submitted_to_bendahara -> submitted_to_director -> approved_by_director
    
    $s = $procurement->status; 

    // A. QR Admin (Selalu Ada)
    $infoAdmin = "Diajukan oleh Admin IT. Tiket: " . ($procurement->report->ticket_number ?? '-') . ". Tgl: " . $procurement->created_at->format('d/m/Y');
    $qrAdmin = $generateQr($infoAdmin);

    // B. QR Kepala Ruang
    // Muncul jika status SUDAH melewati kepala ruang
    $qrkepala_ruang = null;
    $statusSetelahKapro = ['submitted_to_management', 'submitted_to_bendahara', 'submitted_to_director', 'approved_by_director'];
    if (in_array($s, $statusSetelahKapro)) {
        $qrkepala_ruang = $generateQr("Divalidasi Kepala ruang Unit. ID: " . $procurement->id);
    }

    // C. QR Management (BARU)
    // Muncul jika status SUDAH melewati Management (sudah sampai di Bendahara atau lebih jauh)
    $qrManagement = null;
    $statusSetelahManagement = ['submitted_to_bendahara', 'submitted_to_director', 'approved_by_director'];
    if (in_array($s, $statusSetelahManagement)) {
        $qrManagement = $generateQr("Divalidasi oleh Management. Tanggal: " . ($procurement->management_approved_at ?? date('d/m/Y')));
    }

    // D. QR Bendahara
    $qrBendahara = null;
    $statusSetelahBendahara = ['submitted_to_director', 'approved_by_director'];
    if (in_array($s, $statusSetelahBendahara)) {
        $qrBendahara = $generateQr("Diverifikasi Bendahara. Anggaran Tersedia.");
    }

    // E. QR Direktur
    $qrDirektur = null;
    if ($s === 'approved_by_director') {
        $qrDirektur = $generateQr("Disetujui Direktur Utama. " . date('d/m/Y'));
    }

    // 5. Load View (Pastikan qrManagement dikirim ke compact)
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.procurement_single', compact(
        'procurement', 
        'qrAdmin', 
        'qrkepala_ruang', 
        'qrManagement', // Tambahkan ini
        'qrBendahara', 
        'qrDirektur'
    ));
    
    return $pdf->download('laporan-pengadaan-' . $procurement->id . '.pdf');
}

    private function calculateTotal($items) {
        $total = 0;
        $list = is_array($items) ? $items : [];
        foreach($list as $it) {
             $qty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['jumlah']) ? (int)$it['jumlah'] : 1);
             $price = isset($it['unit_price']) ? (float)$it['unit_price'] : (isset($it['harga_satuan']) ? (float)$it['harga_satuan'] : 0);
             $total += ($qty * $price);
        }
        return $total;
    }

    public function exportMonthlyPdf(Request $request) // Tambahkan Request $request di sini
    {
        // Ambil input 'month' dari form modal yang dikirim (format YYYY-MM)
        $monthInput = $request->input('month', date('Y-m'));
        
        $startDate = \Carbon\Carbon::parse($monthInput)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($monthInput)->endOfMonth();

        $reports = \App\Models\Report::with('room')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get();

        $validator = \Illuminate\Support\Facades\Auth::user()->name;
        
        // Perbaikan: gunakan $startDate, bukan $startOfMonth yang tidak didefinisikan
        $dateString = $startDate->locale('id')->translatedFormat('F Y');

        // Generate isi QR Code
        $qrData = "Validated by: " . $validator . "\n" .
                "Report: Monthly Maintenance\n" .
                "Period: " . $dateString . "\n" .
                "Total Reports: " . $reports->count();

        $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(200)
            ->margin(1)
            ->generate($qrData));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly_report', [
            'reports' => $reports,
            'startDate' => $startDate,
            'validator' => $validator,
            'qrCode' => $qrCode
        ]);

        return $pdf->download('Laporan_Bulanan_' . $startDate->format('M_Y') . '.pdf');
    }
}