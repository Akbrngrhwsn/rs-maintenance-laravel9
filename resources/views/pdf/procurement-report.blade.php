<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengadaan #{{ $project->ticket_number }}</title>
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
            vertical-align: middle;
            width: 100%;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
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
            <div style="font-size:12px;">Laporan Pengadaan Aplikasi</div>
            <hr style="margin-top:5px; border:1px solid #000;">
        @endif
    </div>

    {{-- HEADER INFO --}}
    <table style="border:none; margin-bottom:15px;">
        <tr style="border:none;">
            <td style="border:none; width:60%;">
                <h3 style="margin:0;">Laporan Pengadaan #{{ $project->ticket_number ?? $project->id }}</h3>
                <div style="margin-top:5px;">Tanggal Pengajuan: {{ $project->created_at->format('d/m/Y') }}</div>
            </td>
            <td style="border:none; width:40%; text-align:right;">
                <div style="font-weight:bold; font-size:12px; padding:5px; border:1px solid #333; display:inline-block;">
                    STATUS: {{ strtoupper(str_replace('_', ' ', $project->procurement_approval_status ?? 'PENDING')) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- INFO RELASI --}}
    <div style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">
        <strong>Informasi Referensi:</strong><br>
        Nama Aplikasi: <strong>{{ $project->nama_aplikasi }}</strong><br>
        Pemohon: <strong>{{ $project->user->name ?? '-' }}</strong>
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
                $items = is_array($items) ? $items : [];
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

    {{-- CATATAN MANAGEMENT/DIREKTUR --}}
    @if($project->catatan_management_procurement || $project->catatan_management || $project->catatan_direktur)
        <div style="margin-top: 15px;">
            @if($project->catatan_management_procurement)
                <strong>Catatan Management (Pengadaan):</strong>
                <div style="border:1px dashed #555; padding:8px; margin-bottom:5px; background: #f0f7ff;">
                    {{ $project->catatan_management_procurement }}
                </div>
            @endif
            @if($project->catatan_management)
                <strong>Catatan Management (Aplikasi):</strong>
                <div style="border:1px dashed #555; padding:8px; margin-bottom:5px; background: #f0f7ff;">
                    {{ $project->catatan_management }}
                </div>
            @endif
            @if($project->catatan_direktur)
                <strong>Catatan Direktur:</strong>
                <div style="border:1px dashed #555; padding:8px; background: #fffbe6;">
                    {{ $project->catatan_direktur }}
                </div>
            @endif
        </div>
    @endif

    {{-- AREA TANDA TANGAN (5 KOLOM) --}}
    <div style="margin-top: 30px;">
        <table style="border: none;">
            <tr style="border: none;">
                
                {{-- 1. PEMOHON (Kepala Ruang) --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Diajukan Oleh</p>
                    <div style="height: 65px; border: 1px solid #999; margin: 0 auto 5px; width: 65px;"></div>
                    <span style="font-size: 8pt;">{{ $project->user->name ?? 'Pemohon' }}</span>
                </td>

                {{-- 2. MANAGEMENT (Validasi) --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Validasi</p>
                    @if(isset($qrCode) && $qrCode)
                        <img src="data:image/png;base64,{{ $qrCode }}" class="qr-img" style="margin: 0 auto 5px; display: block;">
                        <span style="font-size: 8pt;">Management</span>
                    @else
                        <div style="height: 65px; border: 1px solid #999; margin: 0 auto 5px; width: 65px;"></div>
                        <span class="italic text-gray" style="font-size: 8pt;">
                            @if(in_array($project->procurement_approval_status, ['rejected']))
                                -
                            @else
                                Menunggu
                            @endif
                        </span>
                    @endif
                </td>

                {{-- 3. BENDAHARA (Verifikasi) --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Verifikasi</p>
                    <div style="height: 65px; border: 1px solid #999; margin: 0 auto 5px; width: 65px;"></div>
                    <span style="font-size: 8pt;">Bendahara</span>
                </td>

                {{-- 4. DIREKTUR (Menyetujui) --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">Menyetujui</p>
                    <div style="height: 65px; border: 1px solid #999; margin: 0 auto 5px; width: 65px;"></div>
                    <span style="font-size: 8pt;">Direktur Utama</span>
                </td>

                {{-- 5. QR CODE VALIDASI --}}
                <td width="20%" class="text-center" style="border: none; vertical-align: top;">
                    <p class="text-bold" style="margin-bottom: 5px;">QR Validasi</p>
                    @if(isset($qrCode) && $qrCode)
                        <img src="data:image/png;base64,{{ $qrCode }}" class="qr-img" style="margin: 0 auto 5px; display: block;">
                        <span style="font-size: 8pt;">Scan verifikasi</span>
                    @else
                        <div style="height: 65px; border: 1px solid #999; margin: 0 auto 5px; width: 65px;"></div>
                        <span class="italic text-gray" style="font-size: 8pt;">-</span>
                    @endif
                </td>

            </tr>
        </table>
    </div>

</body>
</html>
