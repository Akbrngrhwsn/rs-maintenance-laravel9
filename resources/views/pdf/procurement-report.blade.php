<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengadaan #{{ $project->ticket_number }}</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; vertical-align: top; }
        th { background: #f2f2f2; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9pt; }
        
        /* Helper Classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .italic { font-style: italic; }
        .border-none { border: none !important; }
        
        /* Status Badge */
        .status-badge {
            font-weight: bold;
            padding: 5px 10px;
            border: 2px solid #333;
            display: inline-block;
            background: #eee;
        }

        /* Signature Section */
        .sig-table td { border: none !important; padding: 10px 5px; }
        .sig-box {
            height: 70px;
            width: 70px;
            margin: 5px auto;
            border: 1px solid #ddd; /* Placeholder jika tidak ada QR */
            display: block;
        }
        .qr-img { width: 70px; height: 70px; }
        .sig-name { font-size: 9pt; border-top: 1px solid #333; display: inline-block; min-width: 100px; margin-top: 5px; }
    </style>
</head>
<body>
    {{-- KOP SURAT --}}
    <div style="text-align:center; margin-bottom: 20px;">
        @php 
            $kopFile = public_path('images/KOPSurat.jfif');
        @endphp
        @if(file_exists($kopFile))
            <img src="{{ $kopFile }}" alt="Kop Surat" style="width:100%; max-height:120px;" />
        @else
            <div style="font-size:18px; font-weight:bold; text-transform: uppercase;">{{ config('app.name', 'RS Maintenance') }}</div>
            <div style="font-size:14px;">LAPORAN PENGADAAN BARANG / JASA</div>
            <hr style="border: 1.5px solid #000; margin-top: 5px;">
        @endif
    </div>

    {{-- HEADER INFO --}}
    <table class="border-none">
        <tr class="border-none">
            <td class="border-none" style="width:60%; padding-left: 0;">
                <div style="font-size: 14pt; font-weight: bold;">NO. TIKET: #{{ $project->ticket_number ?? $project->id }}</div>
                <div style="margin-top: 5px;">Tanggal Pengajuan: <strong>{{ $project->created_at->format('d F Y') }}</strong></div>
            </td>
            <td class="border-none text-right" style="width:40%; padding-right: 0;">
                <div class="status-badge">
                    {{ strtoupper(str_replace('_', ' ', $project->procurement_approval_status ?? 'PENDING')) }}
                </div>
            </td>
        </tr>
    </table>

   {{-- INFO DATA --}}
    <div style="margin-bottom: 20px;">
        <table style="border: none;">
            <tr class="border-none">
                <td class="border-none" width="20%">Nama Aplikasi</td>
                <td class="border-none" width="5%">:</td>
                <td class="border-none"><strong>{{ $project->nama_aplikasi }}</strong></td>
            </tr>
            <tr class="border-none">
                <td class="border-none">Unit Pemohon</td>
                <td class="border-none">:</td>
                {{-- Menggunakan relasi user ke room jika tersedia di model User --}}
                <td class="border-none">
                    {{ $project->user->room->name ?? '-' }} ({{ $project->user->name ?? '-' }})
                </td>
            </tr>
        </table>
    </div>

    {{-- TABEL ITEM --}}
    <h4 style="margin-bottom:10px; border-bottom: 1px solid #333; padding-bottom: 5px;">RINCIAN KEBUTUHAN BARANG</h4>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="35%">Nama Barang / Jasa</th>
                <th width="20%">Spesifikasi / Merk</th>
                <th width="8%">Qty</th>
                <th width="15%">Harga Satuan</th>
                <th width="17%">Subtotal</th>
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
                    $subtotal = $price * $qty;
                    $total += $subtotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $it['name'] ?? $it['nama'] ?? '-' }}</td>
                    <td>{{ $it['brand'] ?? $it['merk'] ?? ($it['spek'] ?? '-') }}</td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">{{ number_format($price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center italic" style="padding: 20px;">Tidak ada item barang yang dicatat.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right text-bold" style="background: #f2f2f2;">TOTAL ESTIMASI BIAYA</td>
                <td class="text-right text-bold" style="background: #f2f2f2;">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- CATATAN --}}
    @if($project->catatan_management_procurement || $project->catatan_direktur)
        <div style="margin-top: 10px;">
            <p class="text-bold" style="margin-bottom: 5px;">Catatan Persetujuan:</p>
            <table style="border: none;">
                @if($project->catatan_management_procurement)
                <tr>
                    <td class="italic" style="border: 1px dashed #ccc; background: #fafafa;">
                        <strong>Management:</strong> {{ $project->catatan_management_procurement }}
                    </td>
                </tr>
                @endif
                @if($project->catatan_direktur)
                <tr>
                    <td class="italic" style="border: 1px dashed #ccc; background: #fafafa;">
                        <strong>Direktur:</strong> {{ $project->catatan_direktur }}
                    </td>
                </tr>
                @endif
            </table>
        </div>
    @endif

    {{-- Area Tanda Tangan Validasi Bertahap --}}
    <div style="margin-top: 30px;">
        <table style="width: 100%; border: none; table-layout: fixed;">
            <tr style="border: none;">
                
                {{-- 1. KEPALA RUANG --}}
                <td class="text-center" style="border: none; vertical-align: top;">
                    <p style="font-weight: bold; margin-bottom: 5px; font-size: 9pt;">Kepala Ruang</p>
                    <div style="height: 70px; margin: 0 auto 5px; width: 70px;">
                        @if(!empty($qrCodes['kepala_ruang']))
                            <img src="data:image/png;base64,{{ $qrCodes['kepala_ruang'] }}" style="width: 100%;">
                        @else
                            <div style="height: 65px; border: 1px dashed #ccc;"></div>
                        @endif
                    </div>
                    <span style="font-size: 8pt; border-top: 1px solid #000; display: block; padding-top: 2px; margin: 0 10px;">
                        {{ $project->user->name ?? 'Pemohon' }}
                    </span>
                </td>

                {{-- 2. ADMIN IT --}}
                <td class="text-center" style="border: none; vertical-align: top;">
                    <p style="font-weight: bold; margin-bottom: 5px; font-size: 9pt;">Admin IT</p>
                    <div style="height: 70px; margin: 0 auto 5px; width: 70px;">
                        {{-- QR Admin IT muncul jika estimasi biaya sudah diisi --}}
                        @if(!empty($project->procurement_estimate) && !empty($qrCodes['admin_it']))
                            <img src="data:image/png;base64,{{ $qrCodes['admin_it'] }}" style="width: 100%;">
                        @else
                            <div style="height: 65px; border: 1px dashed #ccc;"></div>
                        @endif
                    </div>
                    <span style="font-size: 8pt; border-top: 1px solid #000; display: block; padding-top: 2px; margin: 0 10px;">
                        Admin IT
                    </span>
                </td>

                {{-- 3. MANAGEMENT --}}
                <td class="text-center" style="border: none; vertical-align: top;">
                    <p style="font-weight: bold; margin-bottom: 5px; font-size: 9pt;">Management</p>
                    <div style="height: 70px; margin: 0 auto 5px; width: 70px;">
                        @php
                            // List status setelah Management menyetujui
                            $afterManagement = ['submitted_to_bendahara', 'submitted_to_director', 'approved', 'completed'];
                        @endphp
                        {{-- QR muncul jika status sudah melewati management --}}
                        @if(in_array($project->procurement_approval_status, $afterManagement) && !empty($qrCodes['management']))
                            <img src="data:image/png;base64,{{ $qrCodes['management'] }}" style="width: 100%;">
                        @else
                            <div style="height: 65px; border: 1px dashed #ccc;"></div>
                        @endif
                    </div>
                    <span style="font-size: 8pt; border-top: 1px solid #000; display: block; padding-top: 2px; margin: 0 10px;">
                        {{ $project->management_name ?? 'Management' }}
                    </span>
                </td>

                {{-- 4. BENDAHARA --}}
                <td class="text-center" style="border: none; vertical-align: top;">
                    <p style="font-weight: bold; margin-bottom: 5px; font-size: 9pt;">Bendahara</p>
                    <div style="height: 70px; margin: 0 auto 5px; width: 70px;">
                        @php
                            // List status setelah Bendahara menyetujui
                            $afterBendahara = ['submitted_to_director', 'approved', 'completed'];
                        @endphp
                        {{-- QR muncul jika status sudah melewati bendahara --}}
                        @if(in_array($project->procurement_approval_status, $afterBendahara) && !empty($qrCodes['bendahara']))
                            <img src="data:image/png;base64,{{ $qrCodes['bendahara'] }}" style="width: 100%;">
                        @else
                            <div style="height: 65px; border: 1px dashed #ccc;"></div>
                        @endif
                    </div>
                    <span style="font-size: 8pt; border-top: 1px solid #000; display: block; padding-top: 2px; margin: 0 10px;">
                        Bag. Keuangan
                    </span>
                </td>

                {{-- 5. DIREKTUR --}}
                <td class="text-center" style="border: none; vertical-align: top;">
                    <p style="font-weight: bold; margin-bottom: 5px; font-size: 9pt;">Direktur</p>
                    <div style="height: 70px; margin: 0 auto 5px; width: 70px;">
                        {{-- QR muncul jika status final disetujui atau ditolak --}}
                        @if(in_array($project->procurement_approval_status, ['approved', 'completed', 'rejected']) && !empty($qrCodes['direktur']))
                            <img src="data:image/png;base64,{{ $qrCodes['direktur'] }}" style="width: 100%;">
                        @else
                            <div style="height: 65px; border: 1px dashed #ccc;"></div>
                        @endif
                    </div>
                    <span style="font-size: 8pt; border-top: 1px solid #000; display: block; padding-top: 2px; margin: 0 10px;">
                        Direktur Utama
                    </span>
                </td>

            </tr>
        </table>
    </div>

    <div style="position: fixed; bottom: 0; width: 100%; font-size: 8pt; text-align: center; color: #777;">
        Dokumen ini dicetak otomatis melalui Sistem RS Maintenance pada {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>