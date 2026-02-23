<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Procurement;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    public function create($id)
    {
        $report = Report::findOrFail($id);
        return view('procurement.form', compact('report'));
    }

    public function convert($id)
    {
        $report = Report::findOrFail($id);
        // Redirect to the procurement creation form
        return redirect()->route('procurement.create', $id)->with('success', 'Siap untuk diproses sebagai pengadaan.');
    }

    public function edit($id)
    {
        $proc = Procurement::with('report')->findOrFail($id);
        return view('procurement.edit', compact('proc'));
    }

    /**
     * Display a listing of procurements for admin.
     */
    public function index(Request $request)
    {
        $query = Procurement::with('report')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('report', function($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                  ->orWhere('ruangan', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $procurements = $query->paginate(20)->withQueryString();

        return view('admin.procurements.index', compact('procurements'));
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'items.*.nama' => 'required',
            'items.*.jumlah' => 'required|numeric',
        ]);

        // If a procurement already exists for this report, update it instead of creating a new one.
        // Use the latest procurement (if multiple exist) to keep dashboard references consistent.
        $existing = Procurement::where('report_id', $id)->latest()->first();
        if ($existing) {
            $existing->items = $request->items;
            $existing->status = 'submitted_to_kepala_ruang';
            $existing->director_note = null; // clear previous rejection note
            $existing->save();
        } else {
            Procurement::create([
                'report_id' => $id,
                'items' => $request->items, // Data array barang disimpan ke JSON
                'status' => 'submitted_to_kepala_ruang'
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Pengadaan diajukan.');
    }

    public function update(Request $request, $id)
    {
        $proc = Procurement::findOrFail($id);

        $request->validate([
            'items.*.nama' => 'required',
            'items.*.jumlah' => 'required|numeric',
        ]);

        $proc->items = $request->items;
        // when admin edits, reset status to submitted_to_director if it was rejected
        if($proc->status === 'rejected') {
            $proc->status = 'submitted_to_kepala_ruang';
            $proc->director_note = null;
        }
        $proc->save();

        return redirect()->route('dashboard')->with('success', 'Pengajuan pengadaan diperbarui.');
    }
}