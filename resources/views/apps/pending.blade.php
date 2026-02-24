<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-xl text-gray-800">{{ __('Daftar Request Pending') }}</h2>
            <div class="flex gap-2">
                {{-- Tombol Laporan Bulanan Baru --}}
                @if(Auth::user()->role === 'admin')
                <button type="button" onclick="openAppMonthlyModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow hover:bg-indigo-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Laporan Bulanan
                </button>
                @endif

                <a href="{{ route('apps.ongoing') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 transition">
                    Lihat Proyek Berjalan →
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Tombol Buat Request (Hanya kepala ruang/Direktur) --}}
            @if(in_array(Auth::user()->role, ['kepala_ruang', 'direktur']))
            <div class="mb-6 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h3 class="font-bold mb-4 text-gray-800">Ajukan Request Baru</h3>
    <form action="{{ route('apps.store') }}" method="POST" class="space-y-4">
        @csrf
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Aplikasi</label>
            <input type="text" name="nama_aplikasi" placeholder="Contoh: SIM-RS" 
                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
            <textarea name="deskripsi" rows="3" placeholder="Jelaskan kebutuhan aplikasi..." 
                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium transition-colors">
                Kirim Request
            </button>
        </div>
    </form>
</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiket</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aplikasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemohon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($projects as $app)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">{{ $app->ticket_number ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $app->nama_aplikasi }}</div>
                                <div class="text-xs text-gray-500">{{ Str::limit($app->deskripsi, 50) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $app->user->name }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusLabels = [
                                        'submitted_to_admin' => ['label' => 'Menunggu Admin', 'class' => 'bg-blue-100 text-blue-800'],
                                        'submitted_to_management' => ['label' => 'Menunggu Management', 'class' => 'bg-indigo-100 text-indigo-800'],
                                        'submitted_to_bendahara' => ['label' => 'Menunggu Bendahara', 'class' => 'bg-yellow-100 text-yellow-800'],
                                        'submitted_to_director' => ['label' => 'Menunggu Direktur', 'class' => 'bg-yellow-100 text-yellow-800'],
                                        'pending_director' => ['label' => 'Menunggu Direktur', 'class' => 'bg-yellow-100 text-yellow-800'],
                                        'approved' => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-800'],
                                        'rejected' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-800'],
                                    ];
                                    $s = $statusLabels[$app->status] ?? ['label' => ucfirst(str_replace('_',' ',$app->status)), 'class' => 'bg-gray-100 text-gray-800'];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $s['class'] }}">{{ $s['label'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <a href="{{ route('apps.show', $app->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold">Buka Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada request pending.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL POP-UP PEMILIHAN BULAN --}}
    <div id="app-monthly-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full m-4 overflow-hidden relative">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-lg text-gray-800">Unduh Laporan Bulanan</h3>
                <button type="button" onclick="closeAppMonthlyModal()" class="text-gray-400 hover:text-red-500">✕</button>
            </div>
            
            <form action="{{ route('admin.apps.export.monthly') }}" method="GET">
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bulan & Tahun</label>
                    <input type="month" name="month" value="{{ date('Y-m') }}" 
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required />
                    <p class="text-xs text-gray-400 mt-2">Data laporan akan mencakup proyek aplikasi yang aktif atau selesai pada bulan yang dipilih.</p>
                </div>
                
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
                    <button type="button" onclick="closeAppMonthlyModal()" class="bg-white border px-4 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-indigo-700 shadow-md transition">Unduh PDF</button>
                </div>
            </form>
        </div>
    </div>

    {{-- SCRIPT MODAL --}}
    <script>
        function openAppMonthlyModal() {
            document.getElementById('app-monthly-modal').classList.remove('hidden');
        }

        function closeAppMonthlyModal() {
            document.getElementById('app-monthly-modal').classList.add('hidden');
        }

        // Tutup modal jika klik di luar area modal
        window.onclick = function(event) {
            let modal = document.getElementById('app-monthly-modal');
            if (event.target == modal) {
                closeAppMonthlyModal();
            }
        }
    </script>
</x-app-layout>