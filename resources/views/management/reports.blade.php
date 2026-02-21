<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">
            {{ __('Monitoring Laporan Kerusakan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- TABEL 1: LAPORAN AKTIF (Pending & Proses) --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-100 bg-blue-50/50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-blue-900 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Laporan Sedang Berjalan
                    </h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full">
                        {{ $activeReports->total() }} Unit
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Tiket / Ruangan</th>
                                <th class="px-6 py-4 text-left">Masalah</th>
                                <th class="px-6 py-4 text-left">Urgensi</th>
                                <th class="px-6 py-4 text-center">Status Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($activeReports as $report)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800">{{ $report->ruangan }}</div>
                                        <div class="text-xs font-mono text-gray-500">{{ $report->ticket_number }}</div>
                                        <div class="text-[10px] text-gray-400 mt-1">{{ $report->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ Str::limit($report->keluhan, 60) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php $map = ['rendah' => 'bg-gray-100 text-gray-600', 'sedang' => 'bg-amber-100 text-amber-700', 'tinggi' => 'bg-red-100 text-red-700']; @endphp
                                        <span class="text-xs font-bold px-2 py-1 rounded {{ $map[$report->urgency] ?? '' }}">
                                            {{ ucfirst($report->urgency) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($report->status == 'Belum Diproses')
                                            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-100">
                                                ⏳ Menunggu ACC
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-100">
                                                🛠️ Sedang Dikerjakan
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Tidak ada laporan aktif.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    {{ $activeReports->appends(request()->query())->links() }}
                </div>
            </div>

            {{-- TABEL 2: RIWAYAT LAPORAN --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <h3 class="text-lg font-bold text-gray-800">Riwayat & Arsip Laporan</h3>
                    @if(auth()->user() && auth()->user()->role === 'management')
                        <!-- <div class="self-start md:self-center">
                            <a href="{{ route('bendahara.procurements.index') }}" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-sm font-bold hover:bg-purple-700">Daftar Pengadaan</a>
                        </div> -->
                    @endif
                    <form action="{{ route('management.reports') }}" method="GET" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Tiket/Ruangan..." class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <input type="date" name="date" value="{{ request('date') }}" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-700">Filter</button>
                        @if(request('search') || request('date'))
                            <a href="{{ route('management.reports') }}" class="text-sm text-red-600 hover:underline self-center">Reset</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 text-left">Tanggal Selesai</th>
                                <th class="px-6 py-4 text-left">Tiket / Ruangan</th>
                                <th class="px-6 py-4 text-left">Tindakan Teknisi</th>
                                <th class="px-6 py-4 text-center">Status Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($historyReports as $report)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $report->updated_at->format('d M Y') }}
                                        <div class="text-xs">{{ $report->updated_at->format('H:i') }} WIB</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800">{{ $report->ruangan }}</div>
                                        <div class="text-xs font-mono text-gray-400">{{ $report->ticket_number }}</div>
                                        <div class="text-xs text-gray-500 italic mt-1">"{{ Str::limit($report->keluhan, 30) }}"</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 italic">
                                        {{ $report->tindakan_teknisi ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($report->status == 'Selesai')
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">
                                                ✅ Selesai
                                            </span>
                                        @elseif($report->status == 'Tidak Selesai')
                                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">
                                                📦 Butuh Pengadaan
                                            </span>
                                            @if($report->procurement)
                                                <div class="text-[10px] mt-1 text-purple-600 font-bold">
                                                    Status: {{ $report->procurement->status_label }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">
                                                {{ $report->status }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">
                                        Data riwayat tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $historyReports->appends(request()->query())->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
