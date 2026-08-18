<!DOCTYPE html>
<html lang="{{ $language }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $downloadFilename }}</title>
    <style>
        @page{size:A4;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0;font-size:10.5px;background:#edf1f5}.page{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:10mm 12mm;position:relative}.print-actions{position:fixed;z-index:10;right:18px;top:18px}.print-actions button{border:0;border-radius:7px;padding:10px 16px;background:#1f4d8f;color:#fff;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(0,0,0,.16)}.print-actions button:hover{background:#173d73}.print-actions button:disabled{opacity:.7;cursor:wait}.letterhead{text-align:center;padding:0 10mm 8px;border-bottom:3px double #111;margin-bottom:10px}.logo{display:block;max-height:72px;max-width:340px;margin:0 auto 5px;object-fit:contain}.wordmark{font-size:30px;font-weight:900;line-height:.9;letter-spacing:-1px;color:#07529a}.wordmark .chem{color:#15898c}.wordmark-sub{font-size:14px;letter-spacing:7px;color:#777;margin:4px 0 2px}.wordmark-tagline{font-size:9px;letter-spacing:2.4px;color:#777}.company-info{font-size:8.5px;font-weight:700;text-transform:uppercase;line-height:1.45;margin-top:6px}.tax-number{font-size:9.5px;margin-bottom:2px}.document-heading{display:grid;grid-template-columns:1fr 230px;gap:24px;align-items:start;margin:10px 0 18px}.title{border:1.8px solid #111;text-align:center;font-size:18px;font-weight:800;padding:5px}.meta{width:100%;border-collapse:collapse;font-size:9.5px}.meta td{padding:3px 5px}.meta td:first-child{font-weight:700;width:76px}.meta td:last-child{border:1px solid #111;text-align:center;background:#edf3fa!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.parties{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:18px}.party-title{background:#405a92!important;color:#fff!important;font-weight:800;padding:5px 7px;-webkit-print-color-adjust:exact;print-color-adjust:exact}.party-body{border:1px solid #9ca3af;border-top:0;min-height:60px;padding:7px;line-height:1.45}.items{width:100%;border-collapse:collapse}.items thead{display:table-header-group}.items th{background:#405a92!important;color:#fff!important;padding:6px 5px;font-size:9px;border:1px solid #25375f;-webkit-print-color-adjust:exact;print-color-adjust:exact}.items td{border:1px solid #666;padding:5px;height:23px}.right{text-align:right;white-space:nowrap}.center{text-align:center}.summary{width:42%;margin:-1px 0 0 auto;border-collapse:collapse}.summary td{border:1px solid #111;padding:5px}.summary td:last-child{text-align:right}.summary .total{font-weight:800;background:#edf3fa!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.bottom{display:grid;grid-template-columns:1fr 1.15fr;gap:32px;margin-top:22px;align-items:end;break-inside:avoid;page-break-inside:avoid}.instructions{border:1px solid #555;min-height:102px;align-self:start}.instructions b{display:block;background:#d1d5db!important;padding:4px 6px;-webkit-print-color-adjust:exact;print-color-adjust:exact}.instructions div{padding:7px;white-space:pre-line;line-height:1.45}.digital-signature{border:1.5px solid #111;padding:7px;display:grid;grid-template-columns:82px 1fr;gap:10px;min-height:100px;align-items:center}.digital-signature img{width:78px;height:78px;display:block}.signature-copy{font-size:9px;line-height:1.35}.signature-copy .signed-by{font-size:8.5px;margin-bottom:2px}.signature-copy .name{font-size:11px;font-weight:800;margin:3px 0}.signature-copy .role{font-weight:700}.signature-copy .verify{font-size:7.5px;color:#555;margin-top:5px}.footer{text-align:center;margin-top:12px;font-size:7.5px;color:#666}@media print{body{background:#fff}.print-actions{display:none!important}.page{width:auto;min-height:auto;margin:0;padding:0}.letterhead{padding-top:0}a{color:inherit;text-decoration:none}}
    </style>
    <script src="{{ asset('js/document-print.js') }}" defer></script>
</head>
<body>
@php
    $printLogo = $settings['purchase_order_print_logo'] ?? $settings['company_print_logo'] ?? null;
    $companyName = strtoupper($settings['company_name'] ?? 'AULIACHEM PERKASA');
    $signerName = $po->salesUser?->name ?? 'Administrator';
    $signerPosition = $po->salesUser?->position ?: '-';
    $isEnglish = $language === 'en';
@endphp
<div class="print-actions"><button type="button" data-print-document>{{ $isEnglish ? 'Print / Save PDF' : 'Cetak / Simpan PDF' }}</button></div>
<main class="page">
    <header class="letterhead">
        @if(!empty($printLogo))
            <img class="logo" src="{{ asset('storage/'.$printLogo) }}" alt="Logo Purchase Order">
        @else
            <div class="wordmark">AULIA<span class="chem">CHEM</span></div>
            <div class="wordmark-sub">PERKASA</div>
            <div class="wordmark-tagline">CHEMICAL &amp; LABORATORY SOLUTION</div>
        @endif
        <div class="company-info">
            @if(!empty($settings['company_tax_number']))<div class="tax-number">Tax No: {{ $settings['company_tax_number'] }}</div>@endif
            {{ $settings['company_address'] ?? 'Bratang Gede IV No. 8, Surabaya - Jawa Timur, Indonesia' }}
            @if(!empty($settings['company_phone'])) &bull; Phone &amp; Fax: {{ $settings['company_phone'] }} @endif
            @if(!empty($settings['company_email'])) &bull; {{ $settings['company_email'] }} @endif
            @if(!empty($settings['company_website'])) &bull; {{ preg_replace('#^https?://#', '', $settings['company_website']) }} @endif
        </div>
    </header>

    <section class="document-heading">
        <div class="title">PURCHASE ORDER (PO)</div>
        <table class="meta">
            <tr><td>{{ $isEnglish ? 'Date' : 'Tanggal' }}</td><td>{{ $isEnglish ? $po->order_date?->locale('en')->translatedFormat('d F Y') : $po->order_date?->locale('id')->translatedFormat('d F Y') }}</td></tr>
            <tr><td>No. PO</td><td>{{ $po->po_number }}</td></tr>
        </table>
    </section>

    <section class="parties">
        <div>
            <div class="party-title">SUPPLIER</div>
            <div class="party-body"><b>{{ $po->supplier?->supplier_name ?? '-' }}</b><br>{!! nl2br(e($po->supplier?->address ?? '-')) !!}</div>
        </div>
        <div>
            <div class="party-title">{{ $isEnglish ? 'SHIP TO' : 'DIKIRIM KE' }}</div>
            <div class="party-body"><b>{{ $companyName }}</b><br>{!! nl2br(e($po->delivery_address ?: ($settings['company_address'] ?? 'Bratang Gede IV No. 8, Surabaya - Jawa Timur, Indonesia'))) !!}</div>
        </div>
    </section>

    <table class="items">
        <thead><tr><th style="width:36px">NO</th><th>{{ $isEnglish ? 'DESCRIPTION' : 'DESKRIPSI' }}</th><th style="width:62px">UNIT</th><th style="width:82px">QTY</th><th style="width:120px">{{ $isEnglish ? 'PRICE / UNIT' : 'HARGA / UNIT' }}</th><th style="width:125px">{{ $isEnglish ? 'AMOUNT' : 'JUMLAH' }}</th></tr></thead>
        <tbody>
        @foreach($po->items as $index => $item)
            <tr><td class="center">{{ $index + 1 }}</td><td><b>{{ $item->product_name }}</b>@if($item->description)<br><span style="font-size:9px">{{ $item->description }}</span>@endif</td><td class="center">{{ $item->unit }}</td><td class="right">{{ number_format((float)$item->qty, 3, ',', '.') }}</td><td class="right">{{ idr($item->buy_price) }}</td><td class="right">{{ idr($item->subtotal_cost) }}</td></tr>
        @endforeach
        @for($i=$po->items->count();$i<9;$i++)<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
        </tbody>
    </table>
    <table class="summary">
        <tr><td>Subtotal</td><td>{{ idr($po->total_cost) }}</td></tr>
        <tr class="total"><td>TOTAL</td><td>{{ idr($po->total_cost) }}</td></tr>
    </table>

    <section class="bottom">
        <div class="instructions"><b>{{ $isEnglish ? 'Special Instructions' : 'Instruksi Khusus' }}</b><div>{{ $po->special_instructions ?: ($po->notes ?: '-') }}</div></div>
        <div class="digital-signature">
            <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" title="{{ $isEnglish ? 'Open document verification' : 'Buka verifikasi dokumen' }}"><img src="{{ $signatureQr }}" alt="{{ $isEnglish ? 'Electronic signature verification QR' : 'QR verifikasi tanda tangan elektronik' }}"></a>
            <div class="signature-copy">
                <div class="signed-by">{{ $isEnglish ? 'This document is electronically signed by:' : 'Dokumen ini ditandatangani secara elektronik oleh:' }}</div>
                <div class="name">{{ $signerName }}</div>
                <div class="role">{{ $signerPosition }}</div>
                <div>{{ $companyName }}</div>
                <div class="verify">{{ $isEnglish ? 'Scan the QR code to verify this document\'s authenticity.' : 'Pindai QR untuk memverifikasi keaslian dokumen.' }}</div>
            </div>
        </div>
    </section>
    <div class="footer">{{ $isEnglish ? 'If you have any questions regarding this purchase order, please contact ' . ($settings['company_phone'] ?? 'our office') . '.' : 'Jika ada pertanyaan mengenai purchase order ini, silakan hubungi ' . ($settings['company_phone'] ?? 'kantor kami') . '.' }}</div>
</main>
</body>
</html>
