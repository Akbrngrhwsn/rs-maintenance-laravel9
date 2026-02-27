<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ in_array($project->status, ['in_progress', 'completed']) ? route('apps.ongoing') : route('apps.pending') }}" 
                   class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="font-bold text-xl text-gray-800 leading-tight">
                    {{ $project->nama_aplikasi }} 
                    <span class="text-sm font-mono text-gray-500">#{{ $project->ticket_number ?? 'No-Ticket' }}</span>
                </h2>
            </div>

            <div class="w-full md:w-64">
                <select onchange="window.location.href='/apps/detail/' + this.value" class="w-full border-gray-300 rounded-lg text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                    <option value="">-- Pindah ke Proyek Lain --</option>
                    @foreach($allProjects as $p)
                        <option value="{{ $p->id }}" {{ $p->id == $project->id ? 'selected' : '' }}>
                            {{ $p->ticket_number ?? 'APP' }} - {{ Str::limit($p->nama_aplikasi, 20) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @php
                // Tentukan apakah ada penolakan dan dari mana catatannya
                $rejectionNote = null;
                $rejectionSource = null;

                // Cek project-level notes (management/director/admin)
                if ($project->status === 'rejected') {
                    if (!empty($project->catatan_management)) {
                        $rejectionNote = $project->catatan_management;
                        $rejectionSource = 'Management';
                    } elseif (!empty($project->catatan_direktur)) {
                        $rejectionNote = $project->catatan_direktur;
                        $rejectionSource = 'Direktur';
                    } elseif (!empty($project->catatan_admin)) {
                        $rejectionNote = $project->catatan_admin;
                        $rejectionSource = 'Admin';
                    }
                }

                // Jika belum ditemukan, cek apakah ada record pengadaan yang menolak
                $procUniversal = \App\Models\Procurement::where('app_request_id', $project->id)->first();
                if (empty($rejectionNote) && $procUniversal) {
                    // Beberapa nama kolom mungkin berbeda; coba beberapa kemungkinan
                    if (isset($procUniversal->management_note) && !empty($procUniversal->management_note)) {
                        $rejectionNote = $procUniversal->management_note;
                        $rejectionSource = 'Management (Pengadaan)';
                    } elseif (isset($procUniversal->director_note) && !empty($procUniversal->director_note)) {
                        $rejectionNote = $procUniversal->director_note;
                        $rejectionSource = 'Direktur (Pengadaan)';
                    } elseif (isset($project->catatan_management_procurement) && !empty($project->catatan_management_procurement)) {
                        $rejectionNote = $project->catatan_management_procurement;
                        $rejectionSource = 'Management (Pengadaan)';
                    }
                }
            @endphp

            @if(!empty($rejectionNote))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded shadow-sm mb-4">
                    <div class="font-bold">Pengajuan ditolak{{ $rejectionSource ? ' oleh ' . $rejectionSource : '' }}.</div>
                    <div class="text-sm mt-1">Alasan: {{ $rejectionNote }}</div>
                </div>
            @endif

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800">Deskripsi</h3>
                        <p class="text-gray-600 mt-1 whitespace-pre-line">{{ $project->deskripsi }}</p>
                        
                        <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100 inline-flex">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                {{ $project->user->name ?? 'User Tidak Dikenal' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                {{ $project->created_at->translatedFormat('d F Y') }}
                            </span>
                        </div>
                        
                        @if($project->catatan_direktur || $project->catatan_admin || $project->catatan_management)
                        <div class="mt-4 space-y-2">
                             @if($project->catatan_direktur)
                                <div class="text-xs bg-yellow-50 p-2 border border-yellow-200 rounded text-yellow-800 flex items-start gap-2">
                                    <span class="font-bold">Catatan Direktur:</span> 
                                    <span>{{ $project->catatan_direktur }}</span>
                                </div>
                             @endif
                             @if($project->catatan_admin)
                                <div class="text-xs bg-indigo-50 p-2 border border-indigo-200 rounded text-indigo-800 flex items-start gap-2">
                                    <span class="font-bold">Catatan Admin:</span>
                                    <span>{{ $project->catatan_admin }}</span>
                                </div>
                             @endif
                             @if($project->catatan_management)
                                <div class="text-xs bg-blue-50 p-2 border border-blue-200 rounded text-blue-800 flex items-start gap-2">
                                    <span class="font-bold">Catatan Management:</span>
                                    <span>{{ $project->catatan_management }}</span>
                                </div>
                             @endif
                        </div>
                        @endif

                        @if($project->status === 'completed')
                            <div class="mt-6">
                                <a href="{{ route('apps.export.single', $project->id) }}" target="_blank" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow transition gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Unduh Laporan PDF
                                </a>
                            </div>
                        @endif
                    </div>
                    
                    <div class="w-full md:w-auto text-right md:text-left min-w-[200px]">
                        <div class="flex flex-col items-end md:items-end">
                            @php
                                $colors = [
                                    'pending_director' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'submitted_to_director' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'submitted_to_management' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'approved' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                    'in_progress' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'completed' => 'bg-green-100 text-green-800 border-green-200',
                                ];
                                $label = str_replace('_', ' ', $project->status);
                            @endphp
                            <span class="px-4 py-1.5 text-sm font-bold rounded-full border {{ $colors[$project->status] ?? 'bg-gray-100 border-gray-200' }}">
                                {{ strtoupper($label) }}
                            </span>
                            
                            <div class="mt-4 w-full">
                                <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $project->progress }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $project->progress }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TOMBOL AKSI (Approve Direktur / Review Admin) --}}
                @if(Auth::user()->role === 'direktur' && in_array($project->status, ['pending_director', 'submitted_to_director']))
                    <div class="mt-6 border-t pt-6">
                        {{-- Untuk role direktur: hanya tampilkan kolom catatan tanpa tombol ACC/Tolak --}}
                        <div class="flex items-center w-full">
                            <input type="text" name="catatan" placeholder="Catatan persetujuan (opsional)..." class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 flex-1" disabled>
                        </div>
                    </div>
                @endif
                
                @if(Auth::user()->role === 'admin')
                    @if($project->status === 'approved')
                     <div class="mt-6 border-t pt-6">
                        <form action="{{ route('apps.admin_review', $project->id) }}" method="POST" class="flex gap-3 items-center w-full">
                            @csrf @method('PATCH')
                            <input type="text" name="catatan_admin" placeholder="Catatan teknis untuk memulai proyek..." class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 flex-1">
                            <button type="submit" name="action" value="terima" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow transition">Terima & Kerjakan</button>
                            <button type="button" onclick="openRejectModal('{{ route('apps.admin_review', $project->id) }}', 'catatan_admin', {action: 'tolak'}, 'Tolak Aplikasi')" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow transition">Tolak</button>
                        </form>
                    </div>
                    @endif

                    @if($project->status === 'submitted_to_admin')
                    <div class="mt-6 border-t pt-6">
                        <form action="{{ route('admin.apps.process', $project->id) }}" method="POST" class="flex flex-col gap-3">
                            @csrf @method('PATCH')

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-4">Pengajuan Barang</label>

                                @php
                                    $existingItems = [];
                                    if (is_array($project->requested_items)) {
                                        $existingItems = $project->requested_items;
                                    } elseif (is_string($project->requested_items) && $project->requested_items !== '') {
                                        $decoded_req = @json_decode($project->requested_items, true);
                                        if (is_array($decoded_req)) $existingItems = $decoded_req;
                                    }
                                    $count = max(1, count($existingItems));
                                @endphp

                                <div id="item-list" class="space-y-6">
                                    @for($i = 0; $i < $count; $i++)
                                        @php $it = $existingItems[$i] ?? []; @endphp
                                        <div class="item-card bg-white p-4 rounded-lg border" id="item-{{ $i }}">
                                            <div class="flex justify-between items-center mb-3">
                                                <h3 class="item-number font-bold text-blue-700">Pengajuan Barang #{{ $i + 1 }}</h3>
                                                <button type="button" onclick="removeRow({{ $i }})" class="text-red-600 px-3 py-1 rounded bg-red-50">Hapus</button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                                <div class="md:col-span-4">
                                                    <label class="text-xs text-gray-500 mb-1 block">Nama/Jenis Barang</label>
                                                    <input type="text" name="items[{{ $i }}][nama]" value="{{ $it['nama'] ?? $it['name'] ?? '' }}" placeholder="Contoh: SSD 512GB" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                                <div class="md:col-span-4">
                                                    <label class="text-xs text-gray-500 mb-1 block">Merk/Tipe</label>
                                                    <input type="text" name="items[{{ $i }}][merk]" value="{{ $it['merk'] ?? $it['brand'] ?? '' }}" placeholder="Contoh: ASUS" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                                <div class="md:col-span-1">
                                                    <label class="text-xs text-gray-500 mb-1 block">Jml</label>
                                                    <input type="number" name="items[{{ $i }}][jumlah]" value="{{ $it['jumlah'] ?? $it['qty'] ?? 1 }}" class="w-20 border-gray-200 rounded px-3 py-2">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label class="text-xs text-gray-500 mb-1 block">Estimasi Harga Satuan (RP)</label>
                                                    <input type="number" step="0.01" name="items[{{ $i }}][harga_satuan]" value="{{ $it['harga_satuan'] ?? $it['unit_price'] ?? $it['harga'] ?? 0 }}" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                                <div class="md:col-span-12 mt-2">
                                                    <label class="text-xs text-gray-500 mb-1 block">Deskripsi</label>
                                                    <input type="text" name="items[{{ $i }}][deskripsi]" value="{{ $it['keterangan'] ?? $it['description'] ?? '' }}" placeholder="Keterangan tambahan (opsional)" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>

                                <div class="mt-3">
                                    <button type="button" onclick="addRow()" class="text-sm text-blue-600 font-medium">+ Tambah Barang Lain</button>
                                </div>

                                <div class="flex gap-3 mt-3">
                                    <input type="number" step="0.01" name="procurement_estimate" id="procurementEstimate" placeholder="Estimasi Total (otomatis)" readonly class="text-sm border-gray-300 rounded-lg shadow-sm bg-gray-50 flex-1">
                                    <input type="text" name="catatan_admin" placeholder="Catatan singkat (opsional)" class="text-sm border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 w-64">
                                </div>

                                <div class="flex gap-3 justify-end mt-3">
                                    <button type="button" onclick="openRejectModal('{{ route('admin.apps.process', $project->id) }}', 'catatan_admin', {action: 'reject'}, 'Tolak Aplikasi')" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow">Tolak</button>
                                    <button type="submit" name="action" value="forward" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow">Proses & Teruskan ke Management</button>
                                </div>

                                <script>
                                    let counter = {{ $count }};
                                    function addRow(){
                                        const container = document.getElementById('item-list');
                                        const idx = counter;
                                        const el = document.createElement('div');
                                        el.className = 'item-card bg-white p-4 rounded-lg border mt-6';
                                        el.id = 'item-' + idx;
                                        el.innerHTML = `
                                            <div class="flex justify-between items-center mb-3">
                                                <h3 class="item-number font-bold text-blue-700">Pengajuan Barang #${idx + 1}</h3>
                                                <button type="button" onclick="removeRow(${idx})" class="text-red-600 px-3 py-1 rounded bg-red-50">Hapus</button>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                                <div class="md:col-span-4">
                                                    <label class="text-xs text-gray-500 mb-1 block">Nama/Jenis Barang</label>
                                                    <input type="text" name="items[${idx}][nama]" placeholder="Contoh: SSD 512GB" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                                <div class="md:col-span-4">
                                                    <label class="text-xs text-gray-500 mb-1 block">Merk/Tipe</label>
                                                    <input type="text" name="items[${idx}][merk]" placeholder="Contoh: ASUS" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                                <div class="md:col-span-1">
                                                    <label class="text-xs text-gray-500 mb-1 block">Jml</label>
                                                    <input type="number" name="items[${idx}][jumlah]" value="1" class="w-20 border-gray-200 rounded px-3 py-2 qty-input">
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label class="text-xs text-gray-500 mb-1 block">Estimasi Harga Satuan (RP)</label>
                                                    <input type="number" step="0.01" name="items[${idx}][harga_satuan]" value="0" class="w-full border-gray-200 rounded px-3 py-2 unit-input">
                                                </div>
                                                <div class="md:col-span-12 mt-2">
                                                    <label class="text-xs text-gray-500 mb-1 block">Deskripsi</label>
                                                    <input type="text" name="items[${idx}][deskripsi]" placeholder="Keterangan tambahan (opsional)" class="w-full border-gray-200 rounded px-3 py-2">
                                                </div>
                                            </div>`;
                                        container.appendChild(el);
                                        counter++;
                                        computeEstimatedTotal();
                                    }
                                    function removeRow(id){
                                        const el = document.getElementById('item-' + id);
                                        if(el) el.remove();
                                        reorderNumbers();
                                        computeEstimatedTotal();
                                    }
                                    function reorderNumbers(){
                                        const cards = document.querySelectorAll('.item-card');
                                        cards.forEach((card, idx) => {
                                            const title = card.querySelector('.item-number');
                                            if(title) title.innerText = 'Pengajuan Barang #' + (idx + 1);
                                        });
                                        counter = cards.length;
                                    }
                                    function computeEstimatedTotal(){
                                        let total = 0;
                                        document.querySelectorAll('[name$="[jumlah]"]').forEach(function(qEl){
                                            const attr = qEl.getAttribute('name');
                                            const idxMatch = attr.match(/items\[(\d+)\]\[jumlah\]/);
                                            if(!idxMatch) return;
                                            const idx = idxMatch[1];
                                            const unitEl = document.querySelector(`[name="items[${idx}][harga_satuan]"]`);
                                            const qty = parseFloat(qEl.value) || 0;
                                            const unit = unitEl ? parseFloat(unitEl.value) || 0 : 0;
                                            total += qty * unit;
                                        });
                                        const totalInput = document.getElementById('procurementEstimate');
                                        if(totalInput){
                                            totalInput.value = total > 0 ? total.toFixed(2) : '';
                                        }
                                    }
                                    document.addEventListener('input', function(e){
                                        if(!e.target) return;
                                        const name = e.target.getAttribute('name') || '';
                                        if(name.endsWith('[jumlah]') || name.endsWith('[harga_satuan]')){
                                            computeEstimatedTotal();
                                        }
                                    });
                                    reorderNumbers();
                                    computeEstimatedTotal();
                                </script>
                            </div>
                        </form>
                    </div>
                    @endif
                @endif
            </div>

            {{-- Universal: Rincian Pengajuan & Pengadaan --}}
            @php
                $procUniversal = \App\Models\Procurement::where('app_request_id', $project->id)->first();
                if($procUniversal) {
                    $displayItems = is_array($procUniversal->items) ? $procUniversal->items : [];
                    $displayTotal = $procUniversal->total ?? null;
                    $noteForDisplay = $procUniversal->management_note ?? $procUniversal->director_note ?? null;
                    $procDate = $procUniversal->created_at ?? null;
                    $procId = $procUniversal->id;
                } else {
                    $displayItems = [];
                    if (is_array($project->requested_items)) {
                        $displayItems = $project->requested_items;
                    } elseif (is_string($project->requested_items) && $project->requested_items !== '') {
                        $decoded_univ = @json_decode($project->requested_items, true);
                        if (is_array($decoded_univ)) $displayItems = $decoded_univ;
                    }
                    $displayTotal = $project->procurement_estimate ?? null;
                    $noteForDisplay = $project->catatan_admin ?? $project->catatan_management ?? $project->catatan_direktur ?? null;
                    $procDate = $project->created_at ?? null;
                    $procId = null;
                }
            @endphp

            <div class="mt-6 border-t pt-6">
                <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-4">
                    <h4 class="font-bold mb-0">Rincian Pengajuan & Pengadaan</h4>
                    @if($project->needs_procurement)
                        <a href="{{ route('apps.procurement.export', $project->id) }}" target="_blank" 
                           class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-lg shadow transition gap-2 whitespace-nowrap">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Unduh Laporan Pengadaan
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="md:col-span-2 bg-white border rounded-lg p-4">
                        <div class="text-sm text-gray-600">Detail Proyek</div>
                        <div class="mt-2">
                            <div class="text-sm"><strong>Tiket:</strong> {{ $project->ticket_number ?? '-' }}</div>
                            <div class="text-sm"><strong>Pemohon:</strong> {{ $project->user->name ?? '-' }}</div>
                            <div class="text-sm"><strong>Tanggal:</strong> {{ $project->created_at->translatedFormat('d F Y') }}</div>
                            <div class="text-sm mt-2"><strong>Deskripsi:</strong>
                                <div class="text-gray-600">{{ Str::limit($project->deskripsi, 1000) }}</div>
                            </div>
                            @if($procId)
                                <div class="text-sm mt-3"><strong>ID Pengadaan:</strong> {{ $procId }}</div>
                                @if($procDate)
                                    <div class="text-sm"><strong>Tanggal Pengadaan:</strong> {{ $procDate->translatedFormat('d F Y') }}</div>
                                @endif
                            @endif
                            @if($noteForDisplay)
                                <div class="text-sm mt-3"><strong>Catatan:</strong>
                                    <div class="text-gray-600">{{ $noteForDisplay }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white border rounded-lg p-4">
                        <div class="text-sm text-gray-600">Estimasi Total Pengadaan</div>
                        @php
                            $computed = 0;
                            if (!empty($displayItems) && is_array($displayItems)) {
                                foreach ($displayItems as $it) {
                                    $qty = $it['jumlah'] ?? $it['qty'] ?? 0;
                                    $unit = $it['harga_satuan'] ?? $it['unit_price'] ?? $it['harga'] ?? 0;
                                    $computed += ($qty * $unit);
                                }
                            }
                            $finalTotal = $displayTotal ?? ($computed > 0 ? $computed : 0);
                        @endphp
                        <div class="text-xl font-bold mt-2">Rp {{ number_format($finalTotal ?? 0, 2, ',', '.') }}</div>
                        <div class="text-xs text-gray-500 mt-2">(Total dihitung dari daftar barang)</div>
                    </div>
                </div>

                @if(!empty($displayItems))
                    <div class="bg-white border rounded-lg p-4 mb-4">
                        <ul class="space-y-3">
                            @foreach($displayItems as $it)
                                <li class="flex justify-between items-start gap-4">
                                    <div class="flex-1">
                                        <div class="font-semibold">{{ $it['nama'] ?? $it['name'] ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $it['merk'] ?? $it['brand'] ?? '' }} — {{ $it['keterangan'] ?? $it['description'] ?? '' }}</div>
                                    </div>
                                    <div class="text-right w-48 text-sm text-gray-700">
                                        <div>Jml: <strong>{{ $it['jumlah'] ?? $it['qty'] ?? 0 }}</strong></div>
                                        <div>Harga Satuan: <strong>Rp {{ number_format(($it['harga_satuan'] ?? $it['harga'] ?? $it['unit_price'] ?? 0), 2, ',', '.') }}</strong></div>
                                        <div>Total: <strong>Rp {{ number_format((($it['jumlah'] ?? $it['qty'] ?? 0) * ($it['harga_satuan'] ?? $it['harga'] ?? $it['unit_price'] ?? 0)), 2, ',', '.') }}</strong></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Tindakan Management --}}
            @if(Auth::user()->role === 'management' && ($project->status === 'submitted_to_management' || ($project->status === 'submitted_to_management' && $project->needs_procurement && ($project->procurement_approval_status === 'pending' || is_null($project->procurement_approval_status)))))
                <div class="mt-6 border-t pt-6">
                    <h4 class="font-bold mb-4">Tindakan Management</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($project->status === 'submitted_to_management')
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <h5 class="font-bold text-blue-800 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7-1a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Persetujuan Aplikasi
                                </h5>
                                <form action="{{ route('apps.management_approve', $project->id) }}" method="POST" class="space-y-3">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold transition">
                                        ✓ Setujui Aplikasi
                                    </button>
                                </form>

                                <button type="button" onclick="openRejectModal('{{ route('apps.management_reject', $project->id) }}', 'catatan_management', {}, 'Tolak Aplikasi')" class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold transition">Tolak Aplikasi</button>
                            </div>
                        @endif

                        @if($project->needs_procurement && ($project->procurement_approval_status === 'pending' || is_null($project->procurement_approval_status)))
                            <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
                                <h5 class="font-bold text-amber-800 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    Persetujuan Pengadaan
                                </h5>
                                <p class="text-xs text-amber-700 mb-3 bg-amber-100 p-2 rounded">
                                    📌 Anda dapat menyetujui aplikasi terlebih dahulu sambil menunggu proses pengadaan.
                                </p>
                                
                                <form action="{{ route('management.app.procurement.approve', $project->id) }}" method="POST" class="space-y-3">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-bold transition text-sm">
                                        ✓ Lanjutkan Pengadaan
                                    </button>
                                </form>

                                <button type="button" onclick="openRejectModal('{{ route('management.app.procurement.reject', $project->id) }}', 'catatan_management_procurement', {}, 'Tolak Pengadaan')" class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold transition text-sm">Tolak Pengadaan</button>
                            </div>
                        @elseif($project->needs_procurement && $project->procurement_approval_status && $project->procurement_approval_status !== 'pending')
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <h5 class="font-bold text-blue-800 mb-2 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Status Pengadaan
                                </h5>
                                <div class="text-sm">
                                    <strong>Status:</strong> {{ $project->procurement_approval_status_label }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Tindakan Direktur (Aplikasi dan Pengadaan independen) --}}
            @if(Auth::user()->role === 'direktur' && ($project->status === 'submitted_to_director' || ($project->status === 'approved' && $project->needs_procurement && $project->procurement_approval_status === 'submitted_to_director')))
                <div class="mt-6 border-t pt-6">
                    <h4 class="font-bold mb-4">Tindakan Direktur</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($project->status === 'submitted_to_director')
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <h5 class="font-bold text-purple-800 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7-1a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Persetujuan Aplikasi
                                </h5>
                                <form action="{{ route('apps.approve', $project->id) }}" method="POST" class="space-y-3">
                                    @csrf @method('PATCH')
                                    <button type="submit" name="status" value="terima" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold transition">
                                        ✓ Setujui Aplikasi
                                    </button>
                                </form>

                                <button type="button" onclick="openRejectModal('{{ route('apps.approve', $project->id) }}', 'catatan', {status: 'tolak'}, 'Tolak Aplikasi')" class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold transition">Tolak Aplikasi</button>
                            </div>
                        @endif

                        @if($project->needs_procurement && $project->procurement_approval_status === 'submitted_to_director')
                            <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200">
                                <h5 class="font-bold text-indigo-800 mb-3 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    Persetujuan Pengadaan
                                    <span class="ml-auto text-xs bg-indigo-200 px-2 py-1 rounded">Final Approval</span>
                                </h5>
                                <p class="text-xs text-indigo-700 mb-3 bg-indigo-100 p-2 rounded">
                                    ✓ Pengadaan sudah disetujui Bendahara. Silakan berikan approval akhir.
                                </p>
                                
                                <form action="{{ route('director.app.procurement.approve', $project->id) }}" method="POST" class="space-y-3">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold transition text-sm">
                                        ✓ Setujui Pengadaan
                                    </button>
                                </form>

                                <button type="button" onclick="openRejectModal('{{ route('director.app.procurement.reject', $project->id) }}', 'catatan', {}, 'Tolak Pengadaan')" class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold transition text-sm">Tolak Pengadaan</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Tindakan Bendahara --}}
            @php
                // If a Procurement record exists for this AppRequest, prefer its status
                $procUniversal = isset($procUniversal) ? $procUniversal : \App\Models\Procurement::where('app_request_id', $project->id)->first();
                $isSubmittedToBendahara = ($project->procurement_approval_status === 'submitted_to_bendahara') || ($procUniversal && ($procUniversal->status ?? null) === 'submitted_to_bendahara');
            @endphp

            @if(Auth::user()->role === 'bendahara' && $isSubmittedToBendahara)
                <div class="mt-6 border-t pt-6">
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <h4 class="font-bold text-yellow-800 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            Validasi Pengadaan
                        </h4>
                        <p class="text-xs text-yellow-700 mb-3 bg-yellow-100 p-2 rounded">
                            📋 Silakan validasi detail pengadaan dan teruskan ke Direktur untuk persetujuan akhir.
                        </p>
                        
                        <form action="{{ route('bendahara.app.procurement.approve', $project->id) }}" method="POST" class="space-y-3">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold transition">
                                ✓ Validasi & Teruskan ke Direktur
                            </button>
                        </form>

                        <button type="button" onclick="openRejectModal('{{ route('bendahara.app.procurement.reject', $project->id) }}', 'catatan', {}, 'Tolak Pengadaan')" class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold transition">Tolak Pengadaan</button>
                    </div>
                </div>
            @endif

            {{-- FITUR & CHECKLIST --}}
            @if(in_array($project->status, ['in_progress', 'completed']))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                        <span>📋 Checklist Fitur / Modul</span>
                    </h3>
                    <ul class="space-y-3">
                        @forelse($project->features as $feature)
                            <li class="flex items-start justify-between p-3 rounded-lg border {{ $feature->is_done ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200 hover:bg-gray-100' }} transition group">
                                <div class="flex items-center gap-3">
                                    @if(Auth::user()->role === 'admin' && $project->status !== 'completed')
                                        <form action="{{ route('apps.toggle_feature', $feature->id) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="checkbox" onchange="this.form.submit()" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer" {{ $feature->is_done ? 'checked' : '' }}>
                                        </form>
                                    @else
                                        <input type="checkbox" disabled class="w-5 h-5 text-gray-400 rounded border-gray-300" {{ $feature->is_done ? 'checked' : '' }}>
                                    @endif
                                    
                                    <div class="flex flex-col">
                                        <span class="{{ $feature->is_done ? 'line-through text-gray-400' : 'text-gray-700 font-medium' }}">
                                            {{ $feature->nama_fitur }}
                                        </span>
                                        @if($feature->is_done && $feature->completed_at)
                                            <span class="text-[10px] text-green-700 font-mono flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Selesai: {{ $feature->completed_at->format('d/m H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if(Auth::user()->role === 'admin' && $project->status !== 'completed')
                                    <form action="{{ route('apps.delete_feature', $feature->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus fitur ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition p-1 rounded-md hover:bg-red-50" title="Hapus Fitur">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @empty
                            <li class="text-center text-gray-400 italic py-8 border border-dashed rounded-lg bg-gray-50">Belum ada fitur yang ditambahkan oleh Admin.</li>
                        @endforelse
                    </ul>
                </div>

                @if(Auth::user()->role === 'admin' && $project->status !== 'completed')
                <div class="space-y-6">
                    <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 shadow-sm">
                        <h4 class="font-bold text-sm text-blue-800 mb-3 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Tambah Fitur
                        </h4>
                        <form action="{{ route('apps.add_feature', $project->id) }}" method="POST">
                            @csrf
                            <input type="text" name="nama_fitur" required placeholder="Nama fitur / modul..." class="w-full text-sm border-blue-200 rounded-lg mb-3 focus:ring-blue-500 focus:border-blue-500">
                            <button class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-bold hover:bg-blue-700 shadow transition">Simpan Fitur</button>
                        </form>
                    </div>

                    @if($project->progress == 100 && $project->features->count() > 0)
                    <div class="bg-green-50 p-5 rounded-xl border border-green-200 shadow-sm text-center">
                        <div class="mb-3 flex justify-center text-green-600">
                             <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm text-green-800 mb-4 font-bold">Semua fitur telah selesai dikerjakan.</p>
                        <form action="{{ route('apps.complete', $project->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="w-full bg-green-600 text-white py-2.5 rounded-lg shadow hover:bg-green-700 font-bold text-sm transition flex items-center justify-center gap-2">
                                <span>🚀</span> Tandai Project Selesai
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

        </div>
    </div>

    {{-- ==========================================
         MODAL PENOLAKAN UNIVERSAL
         ========================================== --}}
    <div id="rejectModal" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 transform transition-all">
            <h3 class="text-xl font-bold text-gray-900 mb-4" id="rejectModalTitle">Tolak Pengajuan</h3>
            
            <form id="rejectForm" method="POST" action="">
                @csrf
                @method('PATCH')
                
                {{-- Container untuk menampung hidden input tambahan secara dinamis --}}
                <div id="rejectHiddenInputs"></div>

                <div class="mb-5">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea 
                        name="catatan" 
                        id="rejectReasonInput" 
                        required 
                        rows="4" 
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 text-sm" 
                        placeholder="Silakan jelaskan alasan mengapa pengajuan ini ditolak..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()" class="px-5 py-2.5 bg-gray-200 text-gray-800 rounded-lg text-sm font-bold hover:bg-gray-300 transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 shadow transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Konfirmasi Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(actionUrl, inputName, extraParams, title) {
            // Tampilkan Modal
            document.getElementById('rejectModal').classList.remove('hidden');
            
            // Set judul & Action Form
            document.getElementById('rejectModalTitle').innerText = title;
            document.getElementById('rejectForm').action = actionUrl;
            
            // Atur nama input yang akan menampung alasan (berbeda per role/tindakan)
            const inputEl = document.getElementById('rejectReasonInput');
            inputEl.name = inputName;
            inputEl.value = ''; // Kosongkan text area
            inputEl.focus();
            
            // Siapkan hidden parameter (contoh: status=tolak, atau action=reject)
            const hiddenContainer = document.getElementById('rejectHiddenInputs');
            hiddenContainer.innerHTML = ''; 
            
            for (const [key, value] of Object.entries(extraParams)) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = key;
                hiddenInput.value = value;
                hiddenContainer.appendChild(hiddenInput);
            }
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</x-app-layout>