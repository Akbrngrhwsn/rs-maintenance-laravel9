<x-app-layout>
    {{-- NAVBAR MANUAL DIHAPUS (Sudah otomatis muncul dari x-app-layout) --}}

    <div class="py-12 px-4 sm:px-6 lg:px-8 flex justify-center items-start">
        <div class="max-w-xl w-full">
            
            <div class="text-center mb-10">
                <h2 class="text-3xl font-extrabold text-blue-900">Lapor Kerusakan</h2>
                <p class="mt-2 text-gray-500">Silakan isi detail kerusakan perangkat atau fasilitas di bawah ini.</p>
            </div>

            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm flex items-center">
                    <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-green-800 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-xl border-t-4 border-blue-900 p-8">
                <form method="POST" action="{{ route('public.store') }}" class="space-y-6">
                    @csrf

                    {{-- Input Ruangan (Select) --}}
                    <div class="relative">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Lokasi Ruangan</label>
                        <select name="room_id" id="room_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3">
                            <option value="">Pilih ruangan...</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                    </div>

                    {{-- Input Keluhan --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Kerusakan</label>
                        <textarea name="keluhan" rows="4" class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 py-3" placeholder="Contoh: Wifi mati, Printer macet..."></textarea>
                        <x-input-error :messages="$errors->get('keluhan')" class="mt-2" />
                    </div>

                    {{-- Input Urgensi --}}
                    <div class="mb-4">
                        <x-input-label for="urgency" :value="__('Tingkat Urgensi')" />
                        <select name="urgency" id="urgency" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="rendah">Rendah</option>
                            <option value="sedang">Sedang</option>
                            <option value="tinggi">Tinggi</option>
                        </select>
                        <x-input-error :messages="$errors->get('urgency')" class="mt-2" />
                    </div>

                    {{-- Alasan Urgensi --}}
                    <div class="mb-4 hidden" id="urgency_reason_wrapper">
                        <x-input-label for="urgency_reason" :value="__('Alasan (wajib untuk Sedang/Tinggi)')" />
                        <textarea name="urgency_reason" id="urgency_reason" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Jelaskan mengapa tingkat ini diperlukan..."></textarea>
                        <x-input-error :messages="$errors->get('urgency_reason')" class="mt-2" />
                    </div>

                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="checkProcurement" name="needs_procurement" value="1" 
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm font-semibold text-blue-800">Ajukan Pengadaan Barang Langsung</span>
                        </label>
                    </div>

                    <div id="procurementForm" class="hidden mt-2 p-4 bg-white border border-blue-200 rounded-lg shadow-inner">
                        <h4 class="text-sm font-bold text-gray-700 mb-2">Daftar Barang yang Dibutuhkan:</h4>
                        <div id="itemsContainer">
                            <div class="flex gap-2 mb-2 item-row">
                                <input type="text" name="item_names[]" placeholder="Nama Barang (contoh: Mouse Logitech)" class="flex-1 rounded-md border-gray-300 text-sm">
                                <input type="number" name="item_qtys[]" placeholder="Jml" class="w-20 rounded-md border-gray-300 text-sm">
                            </div>
                        </div>
                        <button type="button" onclick="addItemRow()" class="text-xs text-blue-600 font-medium">+ Tambah Barang Lain</button>
                    </div>

                    <script>
                        const checkbox = document.getElementById('checkProcurement');
                        const form = document.getElementById('procurementForm');

                        checkbox.addEventListener('change', function() {
                            form.classList.toggle('hidden', !this.checked);
                        });

                        function addItemRow() {
                            const row = document.querySelector('.item-row').cloneNode(true);
                            row.querySelectorAll('input').forEach(input => input.value = '');
                            document.getElementById('itemsContainer').appendChild(row);
                        }
                    </script>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all hover:shadow-lg transform hover:-translate-y-0.5">
                        Kirim Laporan
                    </button>
                </form>
            </div>
            
            <div class="mt-8 text-center text-sm text-gray-400">
                &copy; {{ date('Y') }} RSU PKU Muhammadiyah
            </div>
        </div>
    </div>
    


    {{-- Script Toggle Urgency --}}
    <script>
        (function(){
            const sel = document.getElementById('urgency');
            const wrapper = document.getElementById('urgency_reason_wrapper');
            const reason = document.getElementById('urgency_reason');
            if(!sel || !wrapper || !reason) return;

            function update(){
                const v = sel.value;
                if(v === 'sedang' || v === 'tinggi'){
                    wrapper.classList.remove('hidden');
                    reason.setAttribute('required','required');
                } else {
                    wrapper.classList.add('hidden');
                    reason.removeAttribute('required');
                }
            }
            sel.addEventListener('change', update);
            update();
        })();
    </script>
</x-app-layout>