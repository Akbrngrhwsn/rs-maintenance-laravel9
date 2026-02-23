<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <h2 class="font-bold text-2xl text-blue-900 leading-tight">
                {{ __('Dashboard Pemeliharaan') }}
            </h2>
            <span class="text-sm text-gray-500 mt-2 md:mt-0 bg-white px-3 py-1 rounded-full shadow-sm border border-gray-100">
                📅 {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- Notifikasi --}}
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- KOLOM 1: MENUNGGU PERSETUJUAN --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[600px]">
                    {{-- Header --}}
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            </span>
                            <h3 class="text-lg font-bold text-gray-800">Menunggu Persetujuan</h3>
                        </div>
                        <span class="text-xs font-semibold bg-amber-100 text-amber-800 px-2 py-1 rounded-full">{{ $pendingReports->total() }} Baru</span>
                    </div>
                    
                    {{-- List Scrollable --}}
                    <div class="p-6 space-y-4 flex-1 overflow-y-auto">
                        @forelse($pendingReports as $report)
                            <div class="group border border-gray-100 rounded-xl p-4 hover:border-amber-300 hover:shadow-md transition-all duration-200 bg-white relative">
                                <div class="absolute left-0 top-4 bottom-4 w-1 bg-amber-400 rounded-r opacity-0 group-hover:opacity-100 transition"></div>
                                
                                <div class="flex justify-between items-start mb-2">
                                    <div class="w-full">
                                        @if($report->ticket_number)
                                            <div class="mb-1">
                                                <span class="text-[10px] font-mono font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200 select-all">
                                                    {{ $report->ticket_number }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-bold text-gray-800 text-base">{{ $report->ruangan }}</h4>
                                            @if(isset($report->urgency))
                                                @php $map = ['rendah' => 'bg-gray-100 text-gray-700', 'sedang' => 'bg-amber-100 text-amber-800', 'tinggi' => 'bg-red-100 text-red-800']; @endphp
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $map[$report->urgency] ?? '' }}">{{ ucfirst($report->urgency) }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ $report->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <span class="bg-amber-50 text-amber-700 text-[10px] uppercase font-bold px-2 py-1 rounded shrink-0">Pending</span>
                                </div>

                                {{-- Keluhan --}}
                                <p class="text-sm text-gray-600 mb-3 bg-gray-50 p-2 rounded border border-gray-100">"{{ $report->keluhan }}"</p>

                                {{-- FITUR BARU: Info Pengadaan Langsung --}}
                                @if($report->needs_procurement && $report->procurement_items_request)
                                <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                    <div class="flex items-center gap-1 mb-2 text-blue-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                        <span class="text-xs font-bold uppercase tracking-wider">Permintaan Pengadaan</span>
                                    </div>
                                    <ul class="space-y-1">
                                        @foreach($report->procurement_items_request as $item)
                                            @php
                                                // Safely extract values and convert to string
                                                $itemName = '';
                                                $itemQty = '';
                                                
                                                if (is_array($item)) {
                                                    // Try different possible field names
                                                    $itemName = $item['nama'] ?? ($item['name'] ?? '');
                                                    $itemQty = $item['jumlah'] ?? ($item['quantity'] ?? '');
                                                } else {
                                                    $itemName = $item;
                                                }
                                                
                                                // Convert arrays to strings
                                                if (is_array($itemName)) {
                                                    $itemName = implode(', ', array_filter((array)$itemName));
                                                }
                                                if (is_array($itemQty)) {
                                                    $itemQty = implode(', ', array_filter((array)$itemQty));
                                                }
                                                
                                                $itemName = trim((string)$itemName);
                                                $itemQty = trim((string)$itemQty);
                                            @endphp
                                            @if($itemName)
                                            <li class="text-xs text-blue-700 flex justify-between">
                                                <span>• {{ $itemName }}</span>
                                                @if($itemQty)<span class="font-bold">x{{ $itemQty }}</span>@endif
                                            </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <div class="flex justify-end gap-2">
                                    {{-- Tombol Pengadaan (Muncul hanya jika ada label needs_procurement) --}}
                                    @if($report->needs_procurement && $report->procurement_status == 'pending_admin')
                                        <form action="{{ route('admin.procurement.convert', $report->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 bg-emerald-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                                Proses Pengadaan
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Tombol ACC Biasa --}}
                                    <form action="{{ route('admin.acc', $report->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 bg-blue-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 transition shadow-sm hover:shadow">
                                            ACC & Proses
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10"><p class="text-gray-500 text-sm font-medium">Semua aman! Tidak ada laporan pending.</p></div>
                        @endforelse
                    </div>

                    {{-- Footer Pagination --}}
                    <div class="p-4 border-t border-gray-100 bg-gray-50/30 flex-shrink-0">
                        {{ $pendingReports->appends(request()->query())->links() }}
                    </div>
                </div>

                {{-- KOLOM 2: SEDANG DIKERJAKAN --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[600px]">
                    {{-- Header --}}
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </span>
                            <h3 class="text-lg font-bold text-gray-800">Sedang Dikerjakan</h3>
                        </div>
                        {{-- PERBAIKAN: Gunakan ->total() --}}
                        <span class="text-xs font-semibold bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $processedReports->total() }} Unit</span>
                    </div>

                    {{-- List Scrollable --}}
                    <div class="p-6 space-y-6 flex-1 overflow-y-auto">
                        @forelse($processedReports as $report)
                            <div class="bg-white rounded-xl p-5 border border-blue-100 shadow-sm relative">
                                <div class="relative z-10">
                                    <div class="flex flex-col gap-1 mb-2">
                                        @if($report->ticket_number)
                                            <span class="text-[10px] font-mono font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100 w-fit">{{ $report->ticket_number }}</span>
                                        @endif
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-bold text-blue-900 text-lg">{{ $report->ruangan }}</h4>
                                            @if(isset($report->urgency))
                                                @php $map = ['rendah' => 'bg-gray-100 text-gray-700', 'sedang' => 'bg-amber-100 text-amber-800', 'tinggi' => 'bg-red-100 text-red-800']; @endphp
                                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $map[$report->urgency] ?? '' }}">{{ ucfirst($report->urgency) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-4 border-l-2 border-blue-300 pl-2 italic">"{{ $report->keluhan }}"</p>
                                    <form action="{{ route('admin.validate', $report->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <div class="mb-3">
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Laporan Tindakan Teknisi</label>
                                            <textarea name="tindakan_teknisi" required class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" rows="2" placeholder="Jelaskan perbaikan..."></textarea>
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="submit" name="status_akhir" value="Selesai" class="flex-1 bg-green-600 text-white py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-green-700 transition">✅ Selesai</button>
                                            <button type="submit" name="status_akhir" value="Tidak Selesai" class="flex-1 bg-white border border-red-200 text-red-600 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-red-50 transition">❌ Gagal/Sparepart</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                             <div class="text-center py-10"><p class="text-gray-500 text-sm font-medium">Tidak ada tugas perbaikan aktif.</p></div>
                        @endforelse
                    </div>

                    {{-- Footer Pagination --}}
                    <div class="p-4 border-t border-gray-100 bg-gray-50/30 flex-shrink-0">
                        {{ $processedReports->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

            {{-- TABEL RIWAYAT DENGAN FILTER & SEARCH --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100" id="riwayat">
                <div class="p-6 border-b border-gray-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-gray-50/30">
                    <h3 class="text-lg font-bold text-gray-800">
                        @if(request('date'))
                            Riwayat Tanggal: <span class="text-blue-600">{{ \Carbon\Carbon::parse(request('date'))->locale('id')->isoFormat('D MMMM Y') }}</span>
                        @else
                            Riwayat Laporan & Pengadaan
                        @endif
                    </h3>
                    
                    {{-- Form Filter & Search --}}
                    <form action="{{ route('dashboard') }}#riwayat" method="GET" class="flex flex-col md:flex-row items-center gap-3 w-full lg:w-auto">
                        <div class="relative w-full md:w-64">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Tiket / Ruangan..." 
                                class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full pl-10">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <label for="date" class="text-sm text-gray-600 font-medium whitespace-nowrap">Tanggal:</label>
                            <input type="date" name="date" id="date" value="{{ request('date') }}" 
                                class="text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full md:w-auto">
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <button type="submit" class="bg-blue-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 transition shadow-sm w-full md:w-auto">
                                Cari
                            </button>
                            @if(request('date') || request('search'))
                                <a href="{{ route('dashboard') }}#riwayat" class="text-sm text-red-500 hover:text-red-700 underline px-2 whitespace-nowrap">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                
                <div class="px-6 pt-4 pb-0">
    <div class="flex items-center justify-end gap-3">
        
        {{-- Form Ekspor PDF Harian --}}
        <form action="{{ route('admin.export.daily') }}" method="GET" class="flex gap-2 items-center">
            <input type="hidden" name="date" value="{{ request('date', date('Y-m-d')) }}">
            {{-- UPDATED: Tombol disamakan stylenya dengan yang lain (Solid Button) --}}
            <button type="submit" class="text-sm bg-blue-600 text-white px-3 py-1 rounded-lg font-bold hover:bg-blue-700">
                Export Laporan Harian (PDF)
            </button>
        </form>

        {{-- Button opens modal to select Month + Week for weekly report export --}}
        <div class="flex items-center gap-2">
            <button id="open-weekly-export" type="button" class="text-sm bg-amber-600 text-white px-3 py-1 rounded-lg font-bold hover:bg-amber-700">
                Export Laporan Mingguan (PDF)
            </button>
        </div>

        <div id="weekly-export-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900 opacity-60"></div>
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full relative z-10 overflow-hidden m-4">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-lg text-gray-800">Unduh Laporan Mingguan</h3>
                    <button type="button" onclick="closeWeeklyModal()" class="text-gray-400 hover:text-red-500">✕</button>
                </div>
                <form action="{{ route('admin.procurements.export.weekly') }}" method="GET">
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pilih Bulan</label>
                            <input id="export-month" type="month" name="month" value="{{ date('Y-m') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                            <p class="text-xs text-gray-400 mt-2">Contoh: 2026-01 untuk Januari 2026</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Minggu ke</label>
                            <select id="export-week" name="week" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </select>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
                        <button type="button" onclick="closeWeeklyModal()" class="bg-white border px-3 py-2 rounded text-sm">Batal</button>
                        <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded-lg font-bold">Unduh Mingguan (PDF)</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol untuk membuka Modal Bulanan --}}
        <div class="flex items-center gap-2">
            <button id="open-monthly-export" type="button" class="text-sm bg-green-600 text-white px-3 py-1 rounded-lg font-bold hover:bg-green-700">
                Export Laporan Bulanan (PDF)
            </button>
        </div>

        <div id="monthly-export-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900 opacity-60"></div>
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full relative z-10 overflow-hidden m-4">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-lg text-gray-800">Unduh Laporan Bulanan</h3>
                    <button type="button" onclick="closeMonthlyModal()" class="text-gray-400 hover:text-red-500">✕</button>
                </div>
                <form action="{{ route('admin.export.monthly') }}" method="GET">
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pilih Bulan & Tahun</label>
                            <input type="month" name="month" value="{{ date('Y-m') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required />
                            <p class="text-xs text-gray-400 mt-2">Data laporan akan difilter berdasarkan bulan yang dipilih.</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
                        <button type="button" onclick="closeMonthlyModal()" class="bg-white border px-3 py-2 rounded text-sm">Batal</button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold">Unduh Bulanan (PDF)</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>
                </div>

                <div class="overflow-x-auto p-6 pt-2">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Waktu</th>
                                <th class="px-6 py-4 text-left">Ruangan / Tiket</th>
                                <th class="px-6 py-4 text-left">Masalah</th>
                                <th class="px-6 py-4 text-left">Tindakan</th>
                                <th class="px-6 py-4 text-left">Detail</th>
                                <th class="px-6 py-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                // Grouping hanya untuk tampilan, pagination tetap jalan di $historyReports
                                $groupedHistory = $historyReports->groupBy(function($item) {
                                    return $item->created_at->format('Y-m-d');
                                });
                            @endphp

                            @forelse($groupedHistory as $date => $reports)
                                {{-- Sub-Header Tanggal --}}
                                <tr class="bg-blue-50/50">
                                    <td colspan="6" class="px-6 py-3 text-sm font-bold text-blue-800 border-l-4 border-blue-500">
                                        📅 {{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                        <span class="ml-2 text-xs font-normal text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200">
                                            {{ count($reports) }} Laporan
                                        </span>
                                    </td>
                                </tr>

                                @foreach($reports as $report)
                                <tr class="transition-colors duration-150 {{ ($report->procurement && $report->procurement->status === 'approved_by_director') ? 'bg-blue-50 hover:bg-blue-100' : ($report->status == 'Tidak Selesai' ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50') }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $report->status == 'Tidak Selesai' ? 'text-red-600' : 'text-gray-500' }} pl-10">
                                        {{ $report->created_at->format('H:i') }} WIB
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold {{ $report->status == 'Tidak Selesai' ? 'text-red-900' : 'text-gray-900' }}">{{ $report->ruangan }}</div>
                                        @if($report->ticket_number)
                                            <div class="text-xs font-mono {{ $report->status == 'Tidak Selesai' ? 'text-red-400' : 'text-gray-400' }} mt-0.5 select-all">{{ $report->ticket_number }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm {{ $report->status == 'Tidak Selesai' ? 'text-red-800' : 'text-gray-600' }} max-w-xs truncate" title="{{ $report->keluhan }}">
                                        {{ Str::limit($report->keluhan, 40) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm {{ $report->status == 'Tidak Selesai' ? 'text-red-700' : 'text-gray-500' }} italic max-w-xs truncate">
                                        {{ $report->tindakan_teknisi ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <button type="button" onclick="document.getElementById('detail-modal-{{ $report->id }}').classList.remove('hidden')" class="bg-blue-900 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-800 transition shadow-sm w-full md:w-auto">Lihat Detail</button>

                                        <div id="detail-modal-{{ $report->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                                            <div class="absolute inset-0 bg-gray-900 opacity-60" onclick="document.getElementById('detail-modal-{{ $report->id }}').classList.add('hidden')"></div>
                                            <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full relative z-10 overflow-hidden transform transition-all m-4">
                                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                                    <h3 class="font-bold text-lg text-gray-800">Detail Laporan</h3>
                                                    <button type="button" onclick="document.getElementById('detail-modal-{{ $report->id }}').classList.add('hidden')" class="text-gray-400 hover:text-red-500">✕</button>
                                                </div>
                                                <div class="p-6 space-y-4">
                                                    <div>
                                                        <div class="text-sm font-bold text-gray-800">{{ $report->ruangan }} @if($report->ticket_number) <span class="text-xs font-mono text-gray-500">{{ $report->ticket_number }}</span> @endif</div>
                                                        <p class="text-sm text-gray-600 mt-2">{{ $report->keluhan }}</p>
                                                        <p class="text-xs text-gray-400 mt-1">Dibuat: {{ $report->created_at->locale('id')->isoFormat('D MMMM Y H:mm') }}</p>
                                                    </div>

                                                    <div>
                                                        <h4 class="font-semibold text-gray-700">Tindakan Teknisi</h4>
                                                        <p class="text-sm text-gray-600">{{ $report->tindakan_teknisi ?? '-' }}</p>
                                                    </div>

                                                    @if($report->procurement)
                                                        <div>
                                                            <h4 class="font-semibold text-gray-700">Informasi Pengadaan</h4>
                                                            <table class="min-w-full table-auto text-sm mt-2">
                                                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                                                    <tr>
                                                                        <th class="px-3 py-2 text-left">Nama</th>
                                                                        <th class="px-3 py-2 text-left">Merk/Tipe</th>
                                                                        <th class="px-3 py-2 text-right">Jml</th>
                                                                        <th class="px-3 py-2 text-right">Harga Satuan</th>
                                                                        <th class="px-3 py-2 text-right">Subtotal</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php $pTotal = 0; @endphp
                                                                    @foreach($report->procurement->items as $it)
                                                                        @php
                                                                            $qty = isset($it['jumlah']) ? (int)$it['jumlah'] : 1;
                                                                            $price = isset($it['harga_satuan']) ? (float)$it['harga_satuan'] : (isset($it['harga']) ? (float)$it['harga'] : (isset($it['biaya']) ? (float)$it['biaya'] : 0));
                                                                            $subtotal = $price * $qty;
                                                                            $pTotal += $subtotal;
                                                                        @endphp
                                                                        <tr>
                                                                            <td class="px-3 py-2">{{ $it['nama'] ?? '-' }}</td>
                                                                            <td class="px-3 py-2">{{ $it['merk'] ?? ($it['spek'] ?? ($it['tipe'] ?? '-')) }}</td>
                                                                            <td class="px-3 py-2 text-right font-mono">{{ $qty }}</td>
                                                                            <td class="px-3 py-2 text-right font-mono">Rp {{ number_format($price, 0, ',', '.') }}</td>
                                                                            <td class="px-3 py-2 text-right font-mono">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr class="bg-blue-50">
                                                                        <td colspan="4" class="px-3 py-2 text-right font-bold text-blue-800">Total</td>
                                                                        <td class="px-3 py-2 text-right font-bold text-blue-800">Rp {{ number_format($pTotal, 0, ',', '.') }}</td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                            @if($report->procurement->director_note)
                                                                <div class="mt-3 text-sm text-red-600">
                                                                    <strong>Catatan :</strong> {{ $report->procurement->director_note }}
                                                                </div>
                                                            @endif
                                                            @if($report->procurement->status === 'rejected')
                                                                <div class="mt-3">
                                                                    <a href="{{ route('procurement.create', $report->id) }}" class="inline-flex items-center gap-2 bg-blue-700 text-white px-3 py-2 rounded-md text-sm font-semibold hover:bg-blue-800">
                                                                        Ajukan Ulang Pengadaan
                                                                    </a>
                                                                </div>
                                                            @elseif($report->procurement->status === 'submitted_to_director')
                                                                <div class="mt-3">
                                                                    <a href="{{ route('procurement.edit', $report->procurement->id) }}" class="inline-flex items-center gap-2 bg-yellow-600 text-white px-3 py-2 rounded-md text-sm font-semibold hover:bg-yellow-700">
                                                                        Edit Pengajuan
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            <div class="mt-3">
                                                                <a href="{{ route('admin.procurements.export.single', $report->procurement->id) }}" target="_blank" class="inline-flex items-center gap-2 bg-green-600 text-white px-3 py-2 rounded-md text-sm font-semibold hover:bg-green-700">
                                                                    Export Pengadaan (PDF)
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($report->status == 'Tidak Selesai')
                                            @if($report->procurement)
                                                <span class="px-3 py-1 text-[10px] font-bold rounded-full bg-purple-100 text-purple-700 border border-purple-200 uppercase">
                                                    Pengadaan: {{ $report->procurement->status_label }}
                                                </span>
                                            @else
                                                <a href="{{ route('procurement.create', $report->id) }}" 
                                                   class="inline-flex items-center gap-1 bg-red-600 text-white px-3 py-1 rounded-full text-[10px] font-bold hover:bg-red-700 shadow-sm transition">
                                                    BUAT PENGADAAN
                                                </a>
                                            @endif
                                        @else
                                            <span class="px-3 py-1 text-[10px] font-bold rounded-full border {{ $report->status == 'Selesai' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-gray-50 text-gray-700 border-gray-100' }}">
                                                {{ $report->status }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Footer Pagination Riwayat --}}
                <div class="p-4 border-t border-gray-100 bg-gray-50/30">
                     {{ $historyReports->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        // PERBAIKAN: Gunakan total() agar notifikasi bekerja untuk semua data baru di DB, bukan cuma yang ada di page 1
        let lastReportCount = {{ $pendingReports->total() }};

        function checkNewReports() {
            fetch('{{ route("admin.new-reports") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.new_reports > lastReportCount) {
                        // Jika ingin refresh otomatis saat ada laporan baru:
                        // window.location.reload();
                        // Atau tampilkan notifikasi browser/toast disini
                        console.log("Ada laporan baru!");
                    }
                    lastReportCount = data.new_reports;
                })
                .catch(error => console.error('Error checking reports:', error));
        }

        // Cek setiap 10 detik
        setInterval(checkNewReports, 10000);

        // Modal and week-select initialization after DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Weekly export modal handlers
            const openBtn = document.getElementById('open-weekly-export');
            const modal = document.getElementById('weekly-export-modal');
            const monthInput = document.getElementById('export-month');
            const weekSelect = document.getElementById('export-week');

            function closeWeeklyModal() {
                if (modal) modal.classList.add('hidden');
            }

            function openWeeklyModal() {
                if (modal) modal.classList.remove('hidden');
            }

            if (openBtn) openBtn.addEventListener('click', openWeeklyModal);

            if (monthInput && weekSelect) {
                function updateWeekOptions() {
                    const val = monthInput.value; // YYYY-MM
                    if (!val) return;
                    const parts = val.split('-');
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1; // JS months 0-11
                    const firstDay = new Date(y, m, 1);
                    const lastDay = new Date(y, m + 1, 0);
                    const days = lastDay.getDate();

                    // count weeks by placing days into week slots starting from the month's first day weekday
                    const weeks = Math.ceil((firstDay.getDay() + days) / 7);

                    // rebuild options
                    weekSelect.innerHTML = '';
                    for (let i = 1; i <= Math.max(1, weeks); i++) {
                        const opt = document.createElement('option');
                        opt.value = i; opt.text = i;
                        weekSelect.appendChild(opt);
                    }
                }

                monthInput.addEventListener('change', updateWeekOptions);
                // initialize
                updateWeekOptions();
            }

            // Close modal when clicking overlay
            const overlay = modal?.querySelector('.absolute.inset-0');
            if (overlay) overlay.addEventListener('click', closeWeeklyModal);

            // Attach close buttons inside modal
            modal?.querySelectorAll('button').forEach(btn => {
                if (btn.textContent.trim() === 'Batal' || btn.textContent.trim() === '✕') {
                    btn.addEventListener('click', closeWeeklyModal);
                }
            });
        });
    </script>

    <script>
    const monthlyModal = document.getElementById('monthly-export-modal');
    const btnOpenMonthly = document.getElementById('open-monthly-export');

    // Buka Modal
    btnOpenMonthly.addEventListener('click', () => {
        monthlyModal.classList.remove('hidden');
    });

    // Tutup Modal
    function closeMonthlyModal() {
        monthlyModal.classList.add('hidden');
    }

    // Tutup jika klik di luar modal area
    window.addEventListener('click', (e) => {
        if (e.target === monthlyModal) {
            closeMonthlyModal();
        }
    });
</script>
</x-app-layout>