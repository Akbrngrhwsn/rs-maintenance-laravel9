<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengadaan #{{ $procurement->id }}</title>
    <style>
        body { font-family: sans-serif; font-size:11px; color: #333; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #333; padding:6px; }
        th { background:#f0f0f0; text-align: left; }
        
        /* Helper Text */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-red { color: #d32f2f; }
        .text-green { color: #388e3c; }
        .text-gray { color: #757575; }
        .italic { font-style: italic; }

        /* Status Box untuk Tanda Tangan */
        .status-box {
            height: 65px;
            display: table-cell;
            vertical-align: middle;
            width: 100%;
            text-align: center;
        }
        
        .qr-img {
            width: 65px;
            height: 65px;
        }
    </style>
</head>
<body>
    {{-- KOP SURAT --}}
    <div style="text-align:center; margin-bottom:15px;">
        @php 
            $kopFile = public_path('images/KOPSurat.jfif');
        @endphp
        @if(file_exists($kopFile))
            <img src="{{ $kopFile }}" alt="Kop Surat" style="width:100%; max-height:110px; object-fit:contain;" />
        @else
            <div style="font-size:16px; font-weight:bold;">{{ config('app.name', 'RS Maintenance') }}</div>
            <div style="font-size:12px;">Laporan Pengadaan Barang & Jasa</div>
            <hr style="margin-top:5px; border:1px solid #000;">
        @endif
    </div>

    {{-- HEADER INFO --}}
    <table style="border:none; margin-bottom:15px;">
        <tr style="border:none;">
            <td style="border:none; width:60%;">
                <h3 style="margin:0;">Laporan Pengadaan #{{ $procurement->id }}</h3>
                <div style="margin-top:5px;">Tanggal Pengajuan: {{ $procurement->created_at->format('d/m/Y') }}</div>
            </td>
            <td style="border:none; width:40%; text-align:right;">
                <div style="font-weight:bold; font-size:12px; padding:5px; border:1px solid #333; display:inline-block;">
                    STATUS: {{ strtoupper(str_replace('_', ' ', $procurement->status)) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- INFO RELASI --}}
    <div style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">
        <strong>Informasi Referensi:</strong><br>
        @if($procurement->report)
            Nomor Tiket Laporan: <strong>{{ $procurement->report->ticket_number ?? '-' }}</strong> | 
            Ruangan: <strong>{{ $procurement->report->room->name ?? $procurement->report->ruangan ?? '-' }}</strong>
        @else
            <em>(Pengadaan langsung tanpa referensi tiket laporan)</em>
        @endif
    </div>

    {{-- TABEL ITEM --}}
    <h4 style="margin-bottom:5px;">Rincian Item Pengadaan</h4>
    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Nama Barang</th>
                <th width="20%">Merk / Spesifikasi</th>
                <th width="10%" class="text-center">Jml</th>
                <th width="15%" class="text-right">Harga Satuan</th>
                <th width="20%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total = 0;
                $items = is_array($procurement->items) ? $procurement->items : [];
            @endphp
            @forelse($items as $index => $it)
                @php
                    $qty = isset($it['quantity']) ? (int)$it['quantity'] : (isset($it['jumlah']) ? (int)$it['jumlah'] : 1);
                    $price = isset($it['unit_price']) ? (float)$it['unit_price'] : (isset($it['harga_satuan']) ? (float)$it['harga_satuan'] : 0);
                    $name = $it['name'] ?? $it['nama'] ?? '-';
                    $brand = $it['brand'] ?? $it['merk'] ?? ($it['spek'] ?? '-');
                    
                    $subtotal = $price * $qty;
                    $total += $subtotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $brand }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">Rp {{ number_format($price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center italic">Tidak ada item barang.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right text-bold" style="background: #f0f0f0;">Total Estimasi Biaya</td>
                <td class="text-right text-bold" style="background: #f0f0f0;">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- CATATAN DIREKTUR/MANAGEMENT --}}
    @if($procurement->director_note || $procurement->management_note)
        <div style="margin-top: 15px;">
            @if($procurement->management_note)
                <strong>Catatan Management:</strong>
                <div style="border:1px dashed #555; padding:8px; margin-bottom:5px; background: #f0f7ff;">
                    {{ $procurement->management_note }}
                </div>
            @endif
            @if($procurement->director_note)
                <strong>Catatan Direktur:</strong>
                <div style="border:1px dashed #555; padding:8px; background: #fffbe6;">
                    {{ $procurement->director_note }}
                </div>
            @endif
        </div>
    @endif

    {{-- AREA TANDA TANGAN (5 KOLOM) --}}
    <div style="margin-top: 30px;">
        <table style="border: none;">
            <tr style="border: none;">
                
                {{-- 1. ADMIN IT (Pengaju) --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Diajukan Oleh</p>
                    @if(isset($qrAdmin) && $qrAdmin)
                        <img src="data:image/png;base64, {{ $qrAdmin }}" class="qr-img">
                        <br><span style="font-size: 8pt;">Admin IT</span>
                    @endif
                </td>

                {{-- 2. KEPALA RUANG --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Mengetahui</p>
                    @if(isset($qrkepala_ruang) && $qrkepala_ruang)
                        <img src="data:image/png;base64, {{ $qrkepala_ruang }}" class="qr-img">
                        <br><span style="font-size: 8pt;">Kepala Ruang</span>
                    @else
                        <div class="status-box">
                            <span class="italic text-gray" style="font-size: 8pt;">
                                @if($procurement->status == 'rejected') - @else Menunggu @endif
                            </span>
                        </div>
                    @endif
                </td>

                {{-- 3. MANAGEMENT (BARU) --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Validasi</p>
                    @if(isset($qrManagement) && $qrManagement)
                        <img src="data:image/png;base64, {{ $qrManagement }}" class="qr-img">
                        <br><span style="font-size: 8pt;">Management</span>
                        
                    @else
                        <div class="status-box">
                            <span class="italic text-gray" style="font-size: 8pt;">
                                @if($procurement->status == 'submitted_to_management')
                                    Menunggu<br>Management
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    @endif
                </td>

                {{-- 4. BENDAHARA --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Verifikasi</p>
                    @if(isset($qrBendahara) && $qrBendahara)
                        <img src="data:image/png;base64, {{ $qrBendahara }}" class="qr-img">
                        <br><span style="font-size: 8pt;">Bendahara</span>
                    @else
                        <div class="status-box">
                            <span class="italic text-gray" style="font-size: 8pt;">
                                @if($procurement->status == 'submitted_to_bendahara')
                                    Menunggu<br>Bendahara
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    @endif
                </td>

                {{-- 5. DIREKTUR --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Menyetujui</p>
                    @if(isset($qrDirektur) && $qrDirektur)
                        <img src="data:image/png;base64, {{ $qrDirektur }}" class="qr-img">
                        <br><span style="font-size: 8pt;">Direktur Utama</span>
                    @else
                        <div class="status-box">
                            <span class="italic text-gray" style="font-size: 8pt;">
                                @if($procurement->status == 'submitted_to_director')
                                    Menunggu<br>Direktur
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    @endif
                </td>

            </tr>
        </table>
    </div>

</body>
</html>