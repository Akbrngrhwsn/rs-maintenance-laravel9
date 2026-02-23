<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-blue-900 leading-tight">{{ __('Edit Pengadaan Barang') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 p-8">
                <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                    <h3 class="text-sm font-bold text-blue-900 uppercase mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Detail Permintaan Pengadaan
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Nomor Tiket</p>
                            <p class="font-mono font-bold text-lg text-blue-900">{{ $proc->report->ticket_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Ruangan</p>
                            <p class="font-bold text-blue-900">{{ $proc->report->ruangan }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-semibold uppercase">Pelapor</p>
                            <p class="font-bold text-blue-900">{{ $proc->report->pelapor_nama }}</p>
                        </div>
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Keluhan / Masalah</p>
                            <p class="text-sm text-gray-800 mt-1 p-2 bg-white rounded border border-gray-200">{{ $proc->report->keluhan }}</p>
                        </div>
                        @if($proc->report->tindakan_teknisi)
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Tindakan Teknisi</p>
                            <p class="text-sm text-gray-800 mt-1 p-2 bg-white rounded border border-gray-200">{{ $proc->report->tindakan_teknisi }}</p>
                        </div>
                        @endif
                        @if($proc->report->procurement_items_request && count($proc->report->procurement_items_request ?? []) > 0)
                        <div class="lg:col-span-3">
                            <p class="text-xs text-gray-600 font-semibold uppercase">Barang yang Diminta</p>
                            <ul class="text-sm text-gray-800 mt-1 p-2 bg-white rounded border border-gray-200 space-y-1">
                                @foreach($proc->report->procurement_items_request as $item)
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
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $urgencyColors[$proc->report->urgency] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $urgencyLabels[$proc->report->urgency] ?? $proc->report->urgency }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('procurement.update', $proc->id) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div id="item-list" class="space-y-6">
                        @foreach($proc->items as $index => $item)
                        <div class="item-card bg-gray-50/50 p-6 rounded-2xl border border-gray-200 relative" id="item-{{ $index }}">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="item-number font-bold text-blue-900 uppercase text-sm tracking-wider">Pengajuan Barang #{{ $index+1 }}</h3>
                                <button type="button" onclick="removeRow({{ $index }})" class="text-red-400 hover:text-red-600 transition">Hapus</button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nama/Jenis Barang</label>
                                    @php
                                        $nama_val = is_array($item['nama'] ?? null) ? implode(', ', (array)$item['nama']) : ($item['nama'] ?? '');
                                    @endphp
                                    <input type="text" name="items[{{ $index }}][nama]" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" value="{{ $nama_val }}" placeholder="Contoh: SSD 512GB">
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Merk/Tipe</label>
                                    @php
                                        $merk_val = is_array($item['merk'] ?? null) ? implode(', ', (array)$item['merk']) : ($item['merk'] ?? ($item['spek'] ?? ''));
                                        $merk_val = is_array($merk_val) ? implode(', ', (array)$merk_val) : $merk_val;
                                    @endphp
                                    <input type="text" name="items[{{ $index }}][merk]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" value="{{ $merk_val }}" placeholder="Contoh: ASUS">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jml</label>
                                    <input type="number" name="items[{{ $index }}][jumlah]" required min="1" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" value="{{ $item['jumlah'] ?? 1 }}">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Estimasi Harga Satuan (Rp)</label>
                                    @php
                                        $harga_val = $item['harga_satuan'] ?? ($item['harga'] ?? ($item['biaya'] ?? 0));
                                        $harga_val = is_array($harga_val) ? 0 : $harga_val;
                                    @endphp
                                    <input type="number" name="items[{{ $index }}][harga_satuan]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" value="{{ $harga_val }}" placeholder="0">
                                </div>
                                <div class="md:col-span-12 mt-3">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Deskripsi</label>
                                    @php
                                        $desk_val = is_array($item['deskripsi'] ?? null) ? implode(', ', (array)$item['deskripsi']) : ($item['deskripsi'] ?? '');
                                    @endphp
                                    <input type="text" name="items[{{ $index }}][deskripsi]" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500" value="{{ $desk_val }}" placeholder="Keterangan tambahan (opsional)">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="button" onclick="addRow()" class="text-blue-600 font-bold">Tambah Barang</button>
                    </div>

                    <div class="mt-8 flex flex-col md:flex-row justify-between items-center gap-4 border-t pt-6">
                        <a href="{{ route('dashboard') }}" class="flex-1 md:flex-none text-center px-6 py-2 text-sm font-bold text-gray-500 hover:text-gray-700">Batal</a>
                        <button type="submit" class="flex-1 md:flex-none bg-red-600 text-white px-8 py-2 rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-100 transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let counter = {{ count($proc->items) }};

        function addRow() {
            const container = document.getElementById('item-list');
            const displayNum = counter + 1;
            const newRow = `
                <div class="item-card bg-gray-50/50 p-6 rounded-2xl border border-gray-200 relative mt-6" id="item-${counter}">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="item-number font-bold text-blue-900 uppercase text-sm tracking-wider">Pengajuan Barang #${displayNum}</h3>
                        <button type="button" onclick="removeRow(${counter})" class="text-red-400 hover:text-red-600 transition">Hapus</button>
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
            if(row) row.remove();
        }
    </script>
</x-app-layout>
