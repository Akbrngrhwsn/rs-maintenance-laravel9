<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">{{ __('Daftar Pengadaan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Semua Pengajuan Pengadaan</h3>
                </div>

                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-md bg-blue-600 text-white">Semua</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="GET" action="{{ route('admin.procurements.index') }}" class="flex items-center gap-2">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tiket/ruangan/nama/merk" class="text-sm border-gray-300 rounded-md px-3 py-1">
                                <input type="date" name="date" value="{{ request('date') }}" class="text-sm border-gray-300 rounded-md px-2 py-1">
                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm">Cari</button>
                            </form>

                            <div class="flex items-center gap-2">
                                <button type="button" onclick="document.getElementById('export-modal').classList.remove('hidden')" class="px-3 py-1 bg-amber-600 text-white rounded-md text-sm">Unduh Bulanan (PDF)</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Tiket / Ruangan</th>
                                <th class="px-6 py-4 text-left">Detail Barang</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Total Biaya</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($procurements as $proc)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 align-top">
                                        <div class="font-bold text-gray-800">{{ $proc->report->ruangan ?? '-' }}</div>
                                        <div class="text-xs font-mono text-gray-500">{{ $proc->report->ticket_number ?? '-' }}</div>
                                    </td>

                                    <td class="px-6 py-4 align-top text-sm text-gray-600">
                                        @php 
                                            $total = 0; 
                                            foreach($proc->items as $item) {
                                                $qty = isset($item['jumlah']) ? (int)$item['jumlah'] : 1;
                                                $price = isset($item['harga_satuan']) ? (float)$item['harga_satuan'] : (isset($item['harga']) ? (float)$item['harga'] : (isset($item['biaya']) ? (float)$item['biaya'] : 0));
                                                $total += $price * $qty;
                                            }
                                        @endphp

                                        <div class="flex items-center gap-3">
                                            <button type="button" onclick="document.getElementById('modal-{{ $proc->id }}').classList.remove('hidden')" 
                                                class="bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1 rounded-md text-sm font-bold hover:bg-blue-100 transition">
                                                Lihat Detail
                                            </button>
                                            <span class="text-xs text-gray-400">({{ count($proc->items) }} item)</span>
                                        </div>

                                        <div id="modal-{{ $proc->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                                            <div class="absolute inset-0 bg-gray-900 opacity-60" onclick="document.getElementById('modal-{{ $proc->id }}').classList.add('hidden')"></div>
                                            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full relative z-10 overflow-hidden transform transition-all m-4">
                                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                                    <div>
                                                        <h3 class="font-bold text-lg text-gray-800">Detail Pengajuan Pengadaan</h3>
                                                        <p class="text-xs text-gray-500">Tiket: {{ $proc->report->ticket_number ?? '-' }}</p>
                                                    </div>
                                                    <button type="button" onclick="document.getElementById('modal-{{ $proc->id }}').classList.add('hidden')" 
                                                        class="text-gray-400 hover:text-red-500 transition">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    </button>
                                                </div>

                                                <div class="p-6 overflow-x-auto max-h-[70vh] overflow-y-auto">
                                                    <table class="min-w-full table-auto text-sm">
                                                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase">
                                                            <tr>
                                                                <th class="px-4 py-3 text-left">Nama Barang</th>
                                                                <th class="px-4 py-3 text-left">Merk / Tipe</th>
                                                                <th class="px-4 py-3 text-right">Jumlah</th>
                                                                <th class="px-4 py-3 text-right">Harga Satuan (estimasi)</th>
                                                                <th class="px-4 py-3 text-right">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            @foreach($proc->items as $item)
                                                                    @php
                                                                    $qty = isset($item['jumlah']) ? (int)$item['jumlah'] : 1;
                                                                    $price = isset($item['harga_satuan']) ? (float)$item['harga_satuan'] : (isset($item['harga']) ? (float)$item['harga'] : (isset($item['biaya']) ? (float)$item['biaya'] : 0));
                                                                    $subtotal = $price * $qty;
                                                                    
                                                                    // Safely extract values - convert arrays to string if needed
                                                                    $nama = is_array($item['nama'] ?? null) ? implode(', ', (array)$item['nama']) : ($item['nama'] ?? '-');
                                                                    $merk = is_array($item['merk'] ?? null) ? implode(', ', (array)$item['merk']) : ($item['merk'] ?? ($item['spek'] ?? ($item['tipe'] ?? '-')));
                                                                    $merk = is_array($merk) ? implode(', ', (array)$merk) : $merk;
                                                                @endphp
                                                                <tr>
                                                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $nama }}</td>
                                                                    <td class="px-4 py-3 text-gray-500">{{ $merk }}</td>
                                                                    <td class="px-4 py-3 text-right font-mono">{{ $qty }}</td>
                                                                    <td class="px-4 py-3 text-right font-mono">Rp {{ number_format($price, 0, ',', '.') }}</td>
                                                                    <td class="px-4 py-3 text-right font-mono font-bold text-gray-800">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="bg-blue-50">
                                                                <td colspan="4" class="px-4 py-3 text-right font-bold text-blue-800 uppercase text-xs">Total Pengajuan</td>
                                                                <td class="px-4 py-3 text-right font-bold text-blue-800 text-lg">Rp {{ number_format($total ?? 0, 0, ',', '.') }}</td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                    <div class="mt-4">
                                                        <a href="{{ route('admin.procurements.export.single', $proc->id) }}" target="_blank" class="inline-flex items-center gap-2 bg-green-600 text-white px-3 py-1 rounded-md text-sm font-semibold hover:bg-green-700">
                                                            Export Pengadaan (PDF)
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        </td>

                                    <td class="px-6 py-4 align-top text-center">
                                        @php
                                            $statusClass = match($proc->status) {
                                                'submitted_to_kepala_ruang' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'submitted_to_bendahara' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'approved_by_director' => 'bg-green-100 text-green-700 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                                default => 'bg-gray-100 text-gray-700 border-gray-200',
                                            };
                                            $statusLabel = match($proc->status) {
                                                'submitted_to_kepala_ruang' => 'Menunggu Konfirmasi Kepala Ruang',
                                                'submitted_to_bendahara' => 'Menunggu Konfirmasi Bendahara',
                                                'approved_by_director' => 'Disetujui',
                                                'rejected' => 'Ditolak',
                                                default => ucfirst(str_replace('_', ' ', $proc->status)),
                                            };
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 align-top text-right font-bold text-gray-800 font-mono">
                                        Rp {{ number_format($total ?? 0, 0, ',', '.') }}
                                    </td>

                                    <td class="px-6 py-4 align-top text-center">
                                        @if($proc->status === 'submitted_to_kepala_ruang')
                                            <a href="{{ route('procurement.edit', $proc->id) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                                Edit
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-sm italic">Tidak tersedia</span>
                                        @endif
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <span class="font-medium">Belum ada pengajuan pengadaan.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Export Bulanan Modal -->
                <div id="export-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-gray-900 opacity-60" onclick="document.getElementById('export-modal').classList.add('hidden')"></div>
                    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full relative z-10 overflow-hidden transform transition-all m-4">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h3 class="font-bold text-lg text-gray-800">Unduh Laporan Bulanan</h3>
                            <button type="button" onclick="document.getElementById('export-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500">✕</button>
                        </div>
                        <form action="{{ route('admin.procurements.export.monthly') }}" method="GET" target="_blank">
                            <div class="p-6">
                                <label class="block text-sm font-bold text-gray-600 mb-2">Pilih Bulan</label>
                                <input type="month" name="month" value="{{ request('month', date('Y-m')) }}" class="w-full border-gray-300 rounded-md px-3 py-2">
                                <p class="text-xs text-gray-400 mt-2">Contoh: 2026-01 untuk Januari 2026</p>
                            </div>
                            <div class="px-6 py-4 border-t bg-gray-50 text-right">
                                <button type="button" onclick="document.getElementById('export-modal').classList.add('hidden')" class="mr-2 px-4 py-2 bg-gray-100 rounded-md">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md">Unduh Bulanan (PDF)</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="p-6">
                    {{ $procurements->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
