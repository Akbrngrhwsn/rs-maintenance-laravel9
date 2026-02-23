<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-blue-900 leading-tight">
            {{ __('Buat Pengadaan Barang') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-8">
                
                {{-- Detail Laporan Lengkap --}}
                <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                    <h3 class="text-sm font-bold text-blue-900 uppercase mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Detail Permintaan Pengadaan
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Nomor Tiket</p>
                            <p class="font-mono font-bold text-lg text-blue-900">{{ $report->ticket_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Ruangan</p>
                            <p class="font-bold text-blue-900">{{ $report->ruangan }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Pelapor</p>
                            <p class="font-bold text-blue-900">{{ $report->pelapor_nama }}</p>
                        </div>
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Keluhan / Masalah</p>
                            <p class="text-sm text-gray-800 mt-1 p-2 bg-white rounded border border-gray-200">{{ $report->keluhan }}</p>
                        </div>
                        @if($report->tindakan_teknisi)
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Tindakan Teknisi</p>
                            <p class="text-sm text-gray-800 mt-1 p-2 bg-white rounded border border-gray-200">{{ $report->tindakan_teknisi }}</p>
                        </div>
                        @endif
                        @if($report->procurement_items_request && count($report->procurement_items_request ?? []) > 0)
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Barang yang Diminta</p>
                            <ul class="text-sm text-gray-800 mt-1 p-2 bg-white rounded border border-gray-200 space-y-1">
                                @foreach($report->procurement_items_request as $item)
                                    @php
                                        // Safely extract and convert all values to string
                                        // Handle both name variations: 'nama' and 'name'
                                        if (is_array($item)) {
                                            $itemName = $item['nama'] ?? ($item['name'] ?? '');
                                            $itemQty = $item['jumlah'] ?? ($item['quantity'] ?? '');
                                        } else {
                                            $itemName = $item;
                                            $itemQty = '';
                                        }
                                        
                                        // If itemName is still an array, convert to string
                                        if (is_array($itemName)) {
                                            $itemName = implode(', ', array_filter((array)$itemName));
                                        }
                                        $itemName = trim((string)$itemName);
                                        
                                        // Convert quality to string if array
                                        if (is_array($itemQty)) {
                                            $itemQty = implode(', ', array_filter((array)$itemQty));
                                        }
                                        $itemQty = trim((string)$itemQty);
                                    @endphp
                                    @if($itemName)
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600 font-bold">•</span>
                                        <span>{{ $itemName }} @if($itemQty)({{ $itemQty }} unit)@endif</span>
                                    </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                        @else
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Barang yang Diminta</p>
                            <p class="text-sm text-gray-500 mt-1 p-2 bg-gray-50 rounded border border-gray-200 italic">Belum ada permintaan barang</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Tingkat Urgensi</p>
                            <div class="mt-1">
                                @php
                                    $urgencyColors = [
                                        'high' => 'bg-red-100 text-red-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'low' => 'bg-green-100 text-green-800'
                                    ];
                                    $urgencyLabels = [
                                        'high' => 'URGENT',
                                        'medium' => 'NORMAL',
                                        'low' => 'RENDAH'
                                    ];
                                @endphp
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $urgencyColors[$report->urgency] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $urgencyLabels[$report->urgency] ?? $report->urgency }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('procurement.store', $report->id) }}" method="POST">
                    @csrf
                    
                    <div id="item-list" class="space-y-6">
                        {{-- ITEM PERTAMA (Statis) --}}
                        <div class="item-card bg-gray-50/50 p-6 rounded-2xl border border-gray-200 relative animate-fade-in" id="item-0">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="item-number font-bold text-blue-900 uppercase text-sm tracking-wider">
                                    Pengajuan Barang #1
                                </h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama/Jenis Barang</label>
                                    <input type="text" name="items[0][nama]" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Contoh: SSD 512GB">
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Merk/Tipe</label>
                                    <input type="text" name="items[0][merk]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Contoh: ASUS">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jml</label>
                                    <input type="number" name="items[0][jumlah]" required min="1" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Estimasi Harga Satuan (Rp)</label>
                                    <input type="number" name="items[0][harga_satuan]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="0">
                                </div>
                                <div class="md:col-span-12 mt-3">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi</label>
                                    <input type="text" name="items[0][deskripsi]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Keterangan tambahan (opsional)">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="mt-8 flex flex-col md:flex-row justify-between items-center gap-4 border-t pt-6">
                        <button type="button" onclick="addRow()" class="flex items-center gap-2 text-blue-600 font-bold text-sm hover:text-blue-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Tambah Barang Lagi
                        </button>
                        
                        <div class="flex gap-3 w-full md:w-auto">
                            <a href="{{ route('dashboard') }}" class="flex-1 md:flex-none text-center px-6 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">
                                Batal
                            </a>
                            <button type="submit" class="flex-1 md:flex-none bg-red-600 text-white px-8 py-2 rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-100 transition">
                                Ajukan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    

    <script>
        let counter = 1;

        function addRow() {
            const container = document.getElementById('item-list');
            const displayNum = counter + 1;
            
            // Format HTML yang sama persis dengan baris pertama agar konsisten
            const newRow = `
                <div class="item-card bg-gray-50/50 p-6 rounded-2xl border border-gray-200 relative animate-fade-in mt-6" id="item-${counter}">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="item-number font-bold text-blue-900 uppercase text-sm tracking-wider">
                            Pengajuan Barang #${displayNum}
                        </h3>
                        <button type="button" onclick="removeRow(${counter})" class="text-red-400 hover:text-red-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-4">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama/Jenis Barang</label>
                            <input type="text" name="items[${counter}][nama]" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Contoh: SSD 512GB">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Merk/Tipe</label>
                            <input type="text" name="items[${counter}][merk]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Contoh: ASUS">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jml</label>
                            <input type="number" name="items[${counter}][jumlah]" required min="1" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Estimasi Harga Satuan (Rp)</label>
                            <input type="number" name="items[${counter}][harga_satuan]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="0">
                        </div>
                        <div class="md:col-span-12 mt-3">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi</label>
                            <input type="text" name="items[${counter}][deskripsi]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" placeholder="Keterangan tambahan (opsional)">
                        </div>
                    </div>
                </div>`;
            
            container.insertAdjacentHTML('beforeend', newRow);
            counter++;
        }

        function removeRow(id) {
            const row = document.getElementById(`item-${id}`);
            row.remove();
            reorderNumbers();
        }

        function reorderNumbers() {
            const cards = document.querySelectorAll('.item-card');
            cards.forEach((card, index) => {
                const title = card.querySelector('.item-number');
                title.innerText = `Pengajuan Barang #${index + 1}`;
            });
            // Update counter agar baris baru berikutnya tetap sinkron
            counter = cards.length;
        }
    </script>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>