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
        
        // Ambil yang statusnya masih menunggu (termasuk yang perlu diproses Admin/Management/Direktur)
        $query = AppRequest::with('user')->whereIn('status', [
            'submitted_to_admin',
            'submitted_to_management',
            'submitted_to_bendahara',
            'submitted_to_director',
            'pending_director',
            'approved'
        ]);
        
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

        $needsProcurement = $request->has('needs_procurement');

        // If director creates directly, auto-approve as before. Otherwise start at Admin IT step.
        $statusAwal = Auth::user()->role === 'direktur' ? 'approved' : 'submitted_to_admin';
        $catatanDirektur = Auth::user()->role === 'direktur' ? 'Auto-Approve by Director.' : null;

        // Build requested items array if provided by Kepala Ruang
        $requestedItems = null;
        if ($needsProcurement) {
            $names = $request->input('item_names', []);
            $qtys = $request->input('item_qtys', []);
            $items = [];
            for ($i = 0; $i < count($names); $i++) {
                $n = trim($names[$i] ?? '');
                $q = intval($qtys[$i] ?? 0);
                if ($n !== '') {
                    $items[] = ['name' => $n, 'qty' => $q];
                }
            }
            $requestedItems = $items ?: null;
        }

            $createData = [
                'user_id' => Auth::id(),
                'nama_aplikasi' => $request->nama_aplikasi,
                'deskripsi' => $request->deskripsi,
                'needs_procurement' => $needsProcurement,
                'procurement_estimate' => null,
                'procurement_approval_status' => 'pending', // Selalu pending saat pertama kali dibuat
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'requested_items')) {
                $createData['requested_items'] = $requestedItems;
            }
            $createData['status'] = $statusAwal;
            $createData['catatan_direktur'] = $catatanDirektur;

            $appRequest = \App\Models\AppRequest::create($createData);
            
            // Generate QR codes for each role and store them
            $baseUrl = route('apps.show', $appRequest->id);
            $qrCodes = [
                'qr_kepala_ruang' => base64_encode(QrCode::format('png')->size(150)->generate($baseUrl . '?approver=kepala_ruang')),
                'qr_admin_it' => base64_encode(QrCode::format('png')->size(150)->generate($baseUrl . '?approver=admin_it')),
                'qr_management' => base64_encode(QrCode::format('png')->size(150)->generate($baseUrl . '?approver=management')),
                'qr_bendahara' => base64_encode(QrCode::format('png')->size(150)->generate($baseUrl . '?approver=bendahara')),
                'qr_direktur' => base64_encode(QrCode::format('png')->size(150)->generate($baseUrl . '?approver=direktur'))
            ];
            $appRequest->update($qrCodes);

        // === PERUBAHAN DI SINI ===
        // Jika Kepala Ruang, kembali ke Dashboard Kepala Ruang
        if (Auth::user()->role === 'kepala_ruang') {
            return redirect()->route('kepala-ruang.apps.index')
            ->with('success', 'Pengajuan berhasil dikirim ke Admin IT.');
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
        
        // Hanya approve aplikasi, tidak terpengaruh pengadaan
        if ($request->status == 'terima') {
            $app->status = 'approved';
            $message = 'Pengajuan aplikasi berhasil disetujui.';
        } else {
            $app->status = 'rejected';
            $message = 'Pengajuan aplikasi berhasil ditolak.';
        }
        
        if ($request->filled('catatan')) {
            $app->catatan_direktur = $request->catatan;
        }
        
        $app->save();

        return back()->with('success', $message);
    }

    // Director: Approve/Reject pengadaan (setelah bendahara approve)
    public function directorApproveProcurementForApp(Request $request, $id)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        $app = AppRequest::findOrFail($id);
        
        if(!$app->needs_procurement) {
            return back()->with('error', 'Aplikasi ini tidak membutuhkan pengadaan.');
        }

        if($app->procurement_approval_status !== 'submitted_to_director') {
            return back()->with('error', 'Status pengadaan tidak valid untuk persetujuan Direktur.');
        }

        // Direktur approve pengadaan
        $app->procurement_approval_status = 'approved';
        $app->save();

        return back()->with('success', 'Pengajuan pengadaan berhasil disetujui oleh Direktur.');
    }

    public function directorRejectProcurementForApp(Request $request, $id)
    {
        if(Auth::user()->role !== 'direktur') abort(403);

        $app = AppRequest::findOrFail($id);
        
        if(!$app->needs_procurement) {
            return back()->with('error', 'Aplikasi ini tidak membutuhkan pengadaan.');
        }

        if($app->procurement_approval_status !== 'submitted_to_director') {
            return back()->with('error', 'Status pengadaan tidak valid untuk penolakan Direktur.');
        }

        // Direktur reject pengadaan
        $app->procurement_approval_status = 'rejected';
        $app->save();

        return back()->with('success', 'Pengajuan pengadaan berhasil ditolak oleh Direktur.');
    }

    // Bendahara: Approve pengadaan di level AppRequest
    public function bendaharaApproveProcurementForApp(Request $request, $id)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $app = AppRequest::findOrFail($id);
        
        if(!$app->needs_procurement) {
            return back()->with('error', 'Aplikasi ini tidak membutuhkan pengadaan.');
        }

        if($app->procurement_approval_status !== 'submitted_to_bendahara') {
            return back()->with('error', 'Status pengadaan tidak valid untuk persetujuan Bendahara.');
        }

        // Bendahara approve - teruskan ke Direktur
        $app->procurement_approval_status = 'submitted_to_director';
        $app->save();

        return back()->with('success', 'Pengajuan pengadaan berhasil disetujui dan diteruskan ke Direktur.');
    }

    // Bendahara: Reject pengadaan di level AppRequest
    public function bendaharaRejectProcurementForApp(Request $request, $id)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $app = AppRequest::findOrFail($id);
        
        if(!$app->needs_procurement) {
            return back()->with('error', 'Aplikasi ini tidak membutuhkan pengadaan.');
        }

        if($app->procurement_approval_status !== 'submitted_to_bendahara') {
            return back()->with('error', 'Status pengadaan tidak valid untuk penolakan Bendahara.');
        }

        // Bendahara reject pengadaan
        $app->procurement_approval_status = 'rejected';
        $app->save();

        return back()->with('success', 'Pengajuan pengadaan berhasil ditolak oleh Bendahara.');
    }

    // Management: Approve or forward app request
    // PERUBAHAN: Memisahkan persetujuan aplikasi dan pengadaan
    public function managementApprove(Request $request, $id)
    {
        if(Auth::user()->role !== 'management') abort(403);

        $app = AppRequest::findOrFail($id);

        // Save management note if provided and column exists
        if ($request->filled('catatan_management') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_management')) {
            $app->catatan_management = $request->catatan_management;
        }

        // PERUBAHAN: Aplikasi approval terlepas dari procurement
        // Saat Management menekan "Setujui Aplikasi" aplikasi harus diteruskan
        // ke Direktur untuk persetujuan akhir. Pengadaan diproses terpisah.
        $app->status = 'submitted_to_director';

        $app->save();

        return back()->with('success', 'Pengajuan aplikasi berhasil diteruskan ke Direktur untuk persetujuan.');
    }

    public function managementReject(Request $request, $id)
    {
        if(Auth::user()->role !== 'management') abort(403);

        $app = AppRequest::findOrFail($id);
        // Save optional note
        if ($request->filled('catatan_management') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_management')) {
            $app->catatan_management = $request->catatan_management;
        }

        $app->status = 'rejected';
        $app->save();

        return back()->with('success', 'Pengajuan berhasil ditolak oleh Management.');
    }
    
    // Management: Approve procurement separately
    // PERUBAHAN: Pengadaan langsung diteruskan ke Bendahara (tidak tertahan di management)
    public function managementApproveProcurementForApp(Request $request, $id)
    {
        if(Auth::user()->role !== 'management') abort(403);

        $app = AppRequest::findOrFail($id);
        
        if(!$app->needs_procurement) {
            return back()->with('error', 'Aplikasi ini tidak membutuhkan pengadaan.');
        }

        // Update procurement approval status langsung ke Bendahara
        $app->procurement_approval_status = 'submitted_to_bendahara';
        
        if ($request->filled('catatan_management_procurement') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_management_procurement')) {
            $app->catatan_management_procurement = $request->catatan_management_procurement;
        }

        $app->save();

        return back()->with('success', 'Pengajuan pengadaan berhasil diteruskan ke Bendahara untuk validasi.');
    }

    // Management: Reject procurement separately
    public function managementRejectProcurementForApp(Request $request, $id)
    {
        if(Auth::user()->role !== 'management') abort(403);

        $app = AppRequest::findOrFail($id);
        
        if(!$app->needs_procurement) {
            return back()->with('error', 'Aplikasi ini tidak membutuhkan pengadaan.');
        }

        // Hanya reject procurement, aplikasi tetap approved
        $app->procurement_approval_status = 'rejected';
        
        if ($request->filled('catatan_management_procurement') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_management_procurement')) {
            $app->catatan_management_procurement = $request->catatan_management_procurement;
        }

        $app->save();

        return back()->with('success', 'Pengajuan pengadaan berhasil ditolak oleh Management.');
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

    // Admin IT processing step: input procurement estimate (if needed) and forward to Management or reject
    public function adminProcess(Request $request, $id)
    {
        if(Auth::user()->role !== 'admin') abort(403);

        $app = AppRequest::findOrFail($id);

        if($request->action === 'reject') {
            $app->status = 'rejected';
            $app->catatan_admin = $request->catatan_admin ?? null;
            $app->save();
            return back()->with('success', 'Pengajuan ditolak oleh Admin IT.');
        }

        // action == forward

        // If needs_procurement, allow admin to provide procurement_estimate and edit items
        if($app->needs_procurement) {
            // Accept structured requested_items from admin OR procurement-style `items`:
            // - `requested_items` => array of numeric-indexed items with english keys (name, brand, qty, unit_price, description)
            // - `items` => procurement-style items indexed by number with Indonesian keys (nama, merk, jumlah, harga_satuan, deskripsi)
            $raw = $request->input('requested_items', []);
            $items = [];

            // If the admin submitted procurement-style `items[...]` (from procurement form), normalize it
            if ($request->has('items') && is_array($request->input('items'))) {
                $procItems = $request->input('items');
                $raw = [];
                foreach ($procItems as $pi) {
                    // Move Indonesian keys into a normalized shape compatible with later logic
                    $raw[] = [
                        'nama' => $pi['nama'] ?? ($pi['name'] ?? ''),
                        'merk' => $pi['merk'] ?? ($pi['brand'] ?? ''),
                        'jumlah' => $pi['jumlah'] ?? ($pi['qty'] ?? 0),
                        'harga_satuan' => $pi['harga_satuan'] ?? ($pi['unit_price'] ?? ($pi['harga'] ?? 0)),
                        'keterangan' => $pi['deskripsi'] ?? ($pi['description'] ?? ''),
                    ];
                }
            }
            $total = 0;
            if (is_array($raw)) {
                foreach ($raw as $it) {
                    $name = trim($it['name'] ?? $it['nama'] ?? '');
                    if ($name === '') continue;

                    // Normalize quantity (accept 'qty' or 'jumlah', various formats)
                    $qtyRaw = $it['qty'] ?? $it['jumlah'] ?? 0;
                    $qty = 0;
                    if (is_numeric($qtyRaw)) {
                        $qty = intval($qtyRaw);
                    } elseif (is_string($qtyRaw)) {
                        $clean = preg_replace('/[^0-9,.-]/', '', $qtyRaw);
                        $clean = str_replace(',', '.', $clean);
                        $qty = intval(floatval($clean));
                    }

                    // Normalize unit price (accept 'harga_satuan', 'unit_price', 'harga')
                    $hargaRaw = $it['harga_satuan'] ?? $it['unit_price'] ?? $it['harga'] ?? 0;
                    $harga = 0.0;
                    if ($hargaRaw !== null && $hargaRaw !== '') {
                        if (is_numeric($hargaRaw)) {
                            $harga = floatval($hargaRaw);
                        } elseif (is_string($hargaRaw)) {
                            $clean = preg_replace('/[^0-9,.-]/', '', $hargaRaw);
                            $clean = str_replace(',', '.', $clean);
                            $harga = floatval($clean);
                        }
                    }

                    $lineTotal = $qty * $harga;
                    if ($lineTotal > 0) {
                        $total += $lineTotal;
                    }

                    // Store both English and Indonesian keys for compatibility with other parts of the app
                    $items[] = [
                        // Indonesian keys (primary for procurement views)
                        'nama' => $name,
                        'merk' => trim($it['brand'] ?? $it['merk'] ?? ''),
                        'jumlah' => $qty,
                        'harga_satuan' => $harga,
                        'keterangan' => trim($it['description'] ?? $it['keterangan'] ?? ''),
                        // English keys (keep for older records / backward compatibility)
                        'name' => $name,
                        'brand' => trim($it['brand'] ?? $it['merk'] ?? ''),
                        'qty' => $qty,
                        'unit_price' => $harga,
                        'description' => trim($it['description'] ?? $it['keterangan'] ?? '')
                    ];
                }
            }

            // Only set requested_items if the column exists in DB to avoid SQL errors
            if (\Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'requested_items')) {
                $app->requested_items = $items ?: null;
            }

            // Use computed total if available, otherwise fall back to provided estimate
            if ($total > 0) {
                $app->procurement_estimate = $total;
            } else {
                if ($request->filled('procurement_estimate')) {
                    $app->procurement_estimate = $request->procurement_estimate;
                }
            }
        } else {
            if ($request->filled('procurement_estimate')) {
                $app->procurement_estimate = $request->procurement_estimate;
            }
        }

        // Forward to Management for approval
        $app->status = 'submitted_to_management';
        $app->save();

        return back()->with('success', 'Pengajuan berhasil diproses dan diteruskan ke Management.');
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

    // Direktur: Lihat daftar pengadaan yang diajukan dari level AppRequest (setelah management approve)
    // Catatan: Pengadaan biasa (dari Report model) sudah dipindahkan ke ProcurementController
    
    // Management: Lihat daftar laporan kerusakan
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

    // Bendahara: Approve/Reject directly on AppRequest when no Procurement record exists
    public function bendaharaApproveAppRequest(Request $request, $id)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $app = AppRequest::findOrFail($id);

        // Save optional note to a bendahara-specific column if present, otherwise to catatan_management if available
        if ($request->filled('catatan') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_bendahara')) {
            $app->catatan_bendahara = $request->catatan;
        } elseif ($request->filled('catatan') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_management')) {
            $app->catatan_management = $request->catatan;
        }

        $app->status = 'submitted_to_director';
        $app->save();

        return back()->with('success', 'Pengajuan berhasil diteruskan ke Direktur.');
    }

    public function bendaharaRejectAppRequest(Request $request, $id)
    {
        if(Auth::user()->role !== 'bendahara') abort(403);

        $app = AppRequest::findOrFail($id);

        if ($request->filled('catatan') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_bendahara')) {
            $app->catatan_bendahara = $request->catatan;
        } elseif ($request->filled('catatan') && \Illuminate\Support\Facades\Schema::hasColumn('app_requests', 'catatan_management')) {
            $app->catatan_management = $request->catatan;
        }

        $app->status = 'rejected';
        $app->save();

        return back()->with('success', 'Pengajuan berhasil ditolak oleh Bendahara.');
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

// app/Http/Controllers/AppRequestController.php

public function downloadProcurementReport($id)
{
    // 1. Ambil data project beserta relasinya
    $project = AppRequest::with(['user'])->findOrFail($id);
    
    // 2. Gunakan kolom status yang benar sesuai database Anda
    $s = $project->procurement_approval_status; 

    $generateQr = function($text) {
        return base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(100)->margin(0)->generate($text));
    };

    // 3. Inisialisasi variabel QR dengan string kosong (estafet)
    $qrAdmin = $generateQr("Diajukan Admin IT. Proyek: " . $project->nama_aplikasi);
    $qrKapro = $generateQr("Diajukan oleh Kepala Ruang: " . ($project->user->name ?? '-'));
    $qrManagement = '';
    $qrBendahara = '';
    $qrDirektur = '';

    // 4. LOGIKA ESTAFET: QR tetap ada jika status sudah melewati atau berada di tahap tersebut
    
    // Management muncul jika status sudah masuk ke Bendahara, Direktur, atau Final
    $afterManagement = ['submitted_to_bendahara', 'submitted_to_director', 'approved', 'completed'];
    if (in_array($s, $afterManagement)) {
        $qrManagement = $generateQr("Divalidasi Management. Tanggal: " . now()->format('d/m/Y'));
    }

    // Bendahara muncul jika status sudah masuk ke Direktur atau Final
    $afterBendahara = ['submitted_to_director', 'approved', 'completed'];
    if (in_array($s, $afterBendahara)) {
        $qrBendahara = $generateQr("Diverifikasi Bendahara. Anggaran Tersedia.");
    }

    // Direktur muncul jika status sudah Final (Approved/Completed)
    $afterDirector = ['approved', 'completed'];
    if (in_array($s, $afterDirector)) {
        $qrDirektur = $generateQr("Disetujui Direktur Utama. " . now()->format('d/m/Y'));
    }

    // 5. Bungkus dalam array $qrCodes agar sesuai dengan variabel di blade (procurement-report.blade.php)
    $qrCodes = [
        'kepala_ruang' => $qrKapro,
        'admin_it'     => $qrAdmin,
        'management'   => $qrManagement,
        'bendahara'    => $qrBendahara,
        'direktur'     => $qrDirektur
    ];

    // 6. Kirim variabel yang dibutuhkan oleh blade
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.procurement-report', [
        'project' => $project,
        'items'   => $project->requested_items ?? [],
        'qrCodes' => $qrCodes,
    ]);

    // Set Paper A4
    $pdf->setPaper('A4');

    return $pdf->download('laporan-pengadaan-' . \Illuminate\Support\Str::slug($project->nama_aplikasi) . '.pdf');
}
}