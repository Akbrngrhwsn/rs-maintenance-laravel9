<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Room;
use Illuminate\Http\Request;

class PublicReportController extends Controller
{
    // Halaman Form Input
    public function index()
    {
        // Ambil daftar ruangan dari DB
        $rooms = Room::orderBy('name')->get();
        return view('public.form', compact('rooms'));
    }

    // Proses Simpan Laporan
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'keluhan' => 'required',
            'urgency' => 'required|in:rendah,sedang,tinggi',
            'urgency_reason' => 'nullable|required_if:urgency,sedang,tinggi',
        ]);

        $room = Room::find($request->room_id);

        $needsProcurement = $request->has('needs_procurement');

        $items = [];
        if ($request->has('needs_procurement')) {
            foreach ($request->item_names as $key => $name) {
                if ($name) {
                    $items[] = [
                        'name' => $name,
                        'quantity' => $request->item_qtys[$key] ?? 1
                    ];
                }
            }
        }

        Report::create([
            'room_id' => $request->room_id,
            'ruangan' => Room::find($request->room_id)?->name,
            'keluhan' => $request->keluhan,
            'urgency' => $request->urgency,
            'urgency_reason' => $request->urgency_reason,
            'status' => 'Belum Diproses',
            'needs_procurement' => $request->has('needs_procurement'),
            'procurement_items_request' => $items, // Simpan array ke JSON
            'procurement_status' => $request->has('needs_procurement') ? 'pending_admin' : null,
        ]);

        return redirect()->route('public.tracking')->with('success', 'Laporan dan Permintaan Pengadaan dikirim!');
    }

    // Halaman Tracking
    public function tracking(Request $request)
    {
        $completedStatus = ['Selesai', 'Ditolak', 'Tidak Selesai'];

        // 1. Query Dasar
        $query = Report::query();
        if ($request->has('ticket') && $request->ticket != '') {
            $query->where('ticket_number', 'LIKE', '%' . $request->ticket . '%');
        }

        // 2. Ambil Pending (Belum Selesai)
        // Clone query agar filter tiket tetap terbawa
        $pendingReports = (clone $query)->whereNotIn('status', $completedStatus)
            ->orderByRaw("CASE 
                WHEN urgency = 'tinggi' THEN 3 
                WHEN urgency = 'sedang' THEN 2 
                ELSE 1 END DESC")
            ->orderBy('created_at', 'asc')
            ->paginate(10, ['*'], 'pending_page');

        // 3. Ambil Completed (Riwayat)
        $completedReports = (clone $query)->whereIn('status', $completedStatus)
            ->latest()
            ->paginate(10, ['*'], 'history_page');

        return view('public.tracking', compact('pendingReports', 'completedReports'));
    }
}