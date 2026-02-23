<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">{{ __('Daftar Pengadaan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Pengajuan dari Admin IT</h3>
                </div>

                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('director.procurements.index', array_merge(request()->all(), ['tab' => 'pending'])) }}" class="px-3 py-1 rounded-md {{ (isset($tab) && $tab==='pending') || !isset($tab) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' }}">Belum Disetujui</a>
                            <a href="{{ route('director.procurements.index', array_merge(request()->all(), ['tab' => 'history'])) }}" class="px-3 py-1 rounded-md {{ isset($tab) && $tab==='history' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' }}">Riwayat</a>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="GET" action="{{ route('director.procurements.index') }}" class="flex items-center gap-2">
                                <input type="hidden" name="tab" value="{{ $tab ?? 'pending' }}">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tiket/ruangan/nama/merk" class="text-sm border-gray-300 rounded-md px-3 py-1">
                                <input type="date" name="date" value="{{ request('date') }}" class="text-sm border-gray-300 rounded-md px-2 py-1">
                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm">Cari</button>
                            </form>

                            <!-- <form action="{{ route('director.procurements.export.weekly') }}" method="GET" class="flex items-center gap-2">
                                <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="text-sm border-gray-300 rounded-md px-2 py-1">
                                <button type="submit" class="px-3 py-1 bg-amber-600 text-white rounded-md text-sm">Export Mingguan (PDF)</button>
                            </form> -->
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Tiket / Ruangan</th>
                                <th class="px-6 py-4 text-left">Detail Barang</th>
                                <th class="px-6 py-4 text-center">Status</th> <th class="px-6 py-4 text-right">Total Biaya</th> <th class="px-6 py-4 text-center">Aksi</th>
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
                                            // Hitung total dulu di sini agar variabel $total bisa dipakai di modal & kolom harga
                                            foreach($proc->items as $item) {
                                                $qty = isset($item['jumlah']) ? (int)$item['jumlah'] : 1;
                                                // Prefer new key 'harga_satuan', fallback to older keys 'harga' or 'biaya'
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
                                            <div class="absolute inset-0 bg-gray-900 opacity-60 backdrop-blur-sm" 
                                                 onclick="document.getElementById('modal-{{ $proc->id }}').classList.add('hidden')"></div>
                                            
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
                                                    @php
                                                        $descs = [];
                                                        foreach($proc->items as $it) {
                                                            if(!empty($it['deskripsi'])) {
                                                                $descs[] = ['nama' => $it['nama'] ?? '-', 'deskripsi' => $it['deskripsi']];
                                                            }
                                                        }
                                                    @endphp

                                                    @if(count($descs))
                                                        <div class="mt-4 px-4">
                                                            <h4 class="text-sm font-bold text-red-700">Deskripsi Tim IT</h4>
                                                            @foreach($descs as $d)
                                                                <div class="mt-2">
                                                                    <div class="text-xs font-mono text-gray-500">{{ $d['nama'] }}</div>
                                                                    <p class="text-sm text-gray-700">{{ $d['deskripsi'] }}</p>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if(!empty($proc->director_note))
                                                        <div class="mt-4 px-4">
                                                            <h4 class="text-sm font-bold text-red-700">Catatan Direktur</h4>
                                                            <p class="text-sm text-gray-700">{{ $proc->director_note }}</p>
                                                        </div>
                                                    @endif
                                                    <div class="mt-4">
                                                        <a href="{{ route('director.procurements.export.single', $proc->id) }}" target="_blank" class="inline-flex items-center gap-2 bg-green-600 text-white px-3 py-1 rounded-md text-sm font-semibold hover:bg-green-700">
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
                                                'submitted_to_director' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'approved_by_director' => 'bg-green-100 text-green-700 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                                default => 'bg-gray-100 text-gray-700 border-gray-200',
                                            };
                                            $statusLabel = match($proc->status) {
                                                'submitted_to_director' => 'Menunggu ACC',
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
                                        @if($proc->status == 'submitted_to_director')
                                            <div class="flex items-center justify-center gap-2">
                                                <form action="{{ route('director.procurements.approve', $proc->id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none transition ease-in-out duration-150">
                                                        ACC
                                                    </button>
                                                </form>

                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $proc->id }}').classList.remove('hidden')" 
                                                    class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none transition ease-in-out duration-150">
                                                    Tolak
                                                </button>

                                                <div id="reject-modal-{{ $proc->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center">
                                                    <div class="absolute inset-0 bg-gray-900 opacity-60" onclick="document.getElementById('reject-modal-{{ $proc->id }}').classList.add('hidden')"></div>
                                                    <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full relative z-10 overflow-hidden transform transition-all m-4">
                                                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                                            <h3 class="font-bold text-lg text-gray-800">Alasan Penolakan</h3>
                                                            <button type="button" onclick="document.getElementById('reject-modal-{{ $proc->id }}').classList.add('hidden')" class="text-gray-400 hover:text-red-500">✕</button>
                                                        </div>
                                                        <form action="{{ route('director.procurements.reject', $proc->id) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="p-6">
                                                                <label class="block text-sm font-bold text-gray-600 mb-2">Deskripsi Penolakan</label>
                                                                <textarea name="catatan" rows="4" required class="w-full border-gray-300 rounded-lg p-2 text-sm" placeholder="Jelaskan alasan penolakan..."></textarea>
                                                            </div>
                                                            <div class="px-6 py-4 border-t bg-gray-50 text-right">
                                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $proc->id }}').classList.add('hidden')" class="mr-2 px-4 py-2 bg-gray-100 rounded-md">Batal</button>
                                                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md">Kirim & Tolak</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-sm italic">Selesai</span>
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
            </div>
        </div>
    </div>
</x-app-layout>