<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengadaan Bulanan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size:12px; color: #333; }
        table { width:100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border:1px solid #ddd; padding:6px; }
        th { background:#f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .section-title { background: #eeeeee; padding: 5px; font-weight: bold; border: 1px solid #ddd; margin-top: 20px; }
        
        /* Style untuk Footer Tanda Tangan */
        .footer-signature {
            float: right; 
            width: 220px; 
            text-align: center; 
            margin-top: 30px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div style="text-align:center; margin-bottom:8px;">
        @php $hasImage = extension_loaded('gd') || extension_loaded('imagick'); @endphp
        @if($hasImage)
            @php
                $kopFile = public_path('images/KOPSurat.jfif');
                $hasKopImage = file_exists($kopFile);
            @endphp
            @if($hasKopImage)
                <img src="{{ $kopFile }}" alt="Kop Surat" style="width:100%; max-height:120px; object-fit:contain;" />
            @else
                <div style="text-align:center; margin-bottom:6px;">
                    <div style="font-weight:700; font-size:16px;">{{ config('app.name', 'RS MAINTENANCE SYSTEM') }}</div>
                    <div style="font-size:12px;">Laporan Terpadu Pengadaan IT</div>
                </div>
                <hr />
            @endif
        @endif
    </div>

    <h2 style="text-align: center;">LAPORAN PENGADAAN BULANAN</h2>
    <div style="text-align: center;">Periode: {{ $monthLabel ?? '' }}</div>
    <br>

    {{-- BAGIAN 1: PENGADAAN KERUSAKAN / MAINTENANCE --}}
    <div class="section-title">A. Pengadaan Unit Maintenance (Kerusakan)</div>
    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="15%">Waktu Dibuat</th>
                <th width="25%">Tiket / Ruangan</th>
                <th width="35%">Detail Barang (Jml x Harga)</th>
                <th width="20%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($procurements as $p)
                @php $pTotal = 0; @endphp
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($p->report)
                            <strong>{{ $p->report->ticket_number ?? '' }}</strong><br>
                            <small>{{ $p->report->ruangan ?? '' }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <ul style="padding-left: 15px; margin: 0;">
                        @php $items = is_array($p->items) ? $p->items : (json_decode($p->items, true) ?: []); @endphp
                        @foreach($items as $it)
                            @php
                                $qty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['jumlah']) ? (int)$it['jumlah'] : 1);
                                $price = isset($it['unit_price']) ? (float)$it['unit_price'] : (isset($it['harga_satuan']) ? (float)$it['harga_satuan'] : (isset($it['harga']) ? (float)$it['harga'] : (isset($it['biaya']) ? (float)$it['biaya'] : 0)));
                                $name = $it['name'] ?? $it['nama'] ?? '-';
                                $subtotal = $qty * $price; 
                                $pTotal += $subtotal;
                            @endphp
                            <li>{{ $name }} ({{ $qty }}x {{ number_format($price,0,',','.') }})</li>
                        @endforeach
                        </ul>
                    </td>
                    <td class="text-right">Rp {{ number_format($pTotal,0,',','.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;">Tidak ada data pengadaan kerusakan bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- BAGIAN 2: PENGADAAN APLIKASI --}}
    <div class="section-title">B. Pengadaan Pengembangan Aplikasi</div>
    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="15%">Waktu Dibuat</th>
                <th width="30%">Nama Aplikasi</th>
                <th width="30%">Detail Barang (Jml x Harga)</th>
                <th width="20%" class="text-right">Total Estimasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($app_requests as $app)
                <tr>
                    <td>{{ $app->id }}</td>
                    <td>{{ optional($app->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <strong>{{ $app->nama_aplikasi }}</strong>
                    </td>
                    <td>
                        {{-- Menampilkan daftar item --}}
                        @if(is_array($app->requested_items))
                            @foreach($app->requested_items as $item)
                                - {{ $item['nama'] ?? $item['name'] ?? '-' }} <br>
                            @endforeach
                        @elseif(!empty($app->requested_items))
                            {{ $app->requested_items }}
                        @else
                            {{ $app->deskripsi }}
                        @endif
                        
                        <br>
                        
                        {{-- LOGIKA WARNA STATUS --}}
                        @php
                            $rawStatus = $app->procurement_approval_status ?: 'Pending';
                            $statusLower = strtolower($rawStatus);
                            $statusColor = '#555'; // Warna default abu-abu gelap
                            $statusWeight = 'normal';

                            if (str_contains($statusLower, 'tolak') || str_contains($statusLower, 'reject')) {
                                $statusColor = '#dc2626'; // Merah untuk ditolak
                                $statusWeight = 'bold';
                            } elseif (str_contains($statusLower, 'setuju') || str_contains($statusLower, 'approve')) {
                                $statusColor = '#16a34a'; // Hijau untuk disetujui
                                $statusWeight = 'bold';
                            }
                        @endphp

                        <div style="margin-top: 5px; color: {{ $statusColor }}; font-weight: {{ $statusWeight }}; font-size: 11px;">
                            Status: {{ strtoupper($rawStatus) }}
                        </div>
                    </td>
                    <td class="text-right">
                        {{-- Coret harga total jika ditolak --}}
                        @if(str_contains($statusLower, 'tolak') || str_contains($statusLower, 'reject'))
                            <span style="text-decoration: line-through; color: #999;">
                                Rp {{ number_format($app->procurement_estimate, 0, ',', '.') }}
                            </span>
                        @else
                            Rp {{ number_format($app->procurement_estimate, 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">Tidak ada data pengadaan aplikasi bulan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 20px;">Demikian laporan pengadaan ini disusun sebagai dokumentasi aset dan pengeluaran sistem IT. 
    Atas perhatian dan kerja samanya, kami ucapkan terima kasih.</p>

    <div class="footer-signature">
        <p style="margin-bottom: 5px;">Disetujui/Divalidasi Oleh,</p>
        
        @if(isset($qrCode))
            <img src="data:{{ $qrMime ?? 'image/png' }};base64, {!! $qrCode !!}" alt="QR Validasi" style="width: 80px; height: 80px; margin: 5px 0;">
        @else
            <br><br><br>
        @endif
        
        <br>
        <span style="font-size: 12px; font-weight: bold; text-decoration: underline;">
            {{ $validator ?? 'Administrator' }}
        </span>
        <br>
        <span style="font-size: 10px; color: #555;">
            {{ $waktuValidasi ?? date('d F Y') }}
        </span>
    </div>
</body>
</html>