<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $downloadFilename }}</title>
    <style>
        @page{size:A4;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0;font-size:10.5px;background:#edf1f5}.page{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:10mm 12mm;position:relative}.print-actions{position:fixed;z-index:10;right:18px;top:18px}.print-actions button{border:0;border-radius:7px;padding:10px 16px;background:#1f4d8f;color:#fff;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(0,0,0,.16)}.print-actions button:hover{background:#173d73}.print-actions button:disabled{opacity:.7;cursor:wait}.letterhead{text-align:center;padding:0 10mm 8px;border-bottom:3px double #111;margin-bottom:10px}.letterhead.quotation-letterhead{display:grid;grid-template-columns:minmax(0,1fr) minmax(220px,.85fr);gap:30px;align-items:center;text-align:left;padding:0 0 10px;border-bottom:2px solid #111}.logo{display:block;max-height:72px;max-width:340px;margin:0 auto 5px;object-fit:contain}.quotation-letterhead .logo{max-height:72px;max-width:310px;margin:0;object-position:left center}.wordmark{font-size:30px;font-weight:900;line-height:.9;letter-spacing:-1px;color:#07529a}.wordmark .chem{color:#15898c}.wordmark-sub{font-size:14px;letter-spacing:7px;color:#777;margin:4px 0 2px}.wordmark-tagline{font-size:9px;letter-spacing:2.4px;color:#777}.quotation-letterhead .wordmark,.quotation-letterhead .wordmark-sub,.quotation-letterhead .wordmark-tagline{text-align:left}.company-info{font-size:8.5px;font-weight:700;text-transform:uppercase;line-height:1.45;margin-top:6px}.quotation-company-info{text-align:right;font-size:9.5px;line-height:1.7;color:#222;overflow-wrap:anywhere}.quotation-company-info b{display:inline-block;min-width:82px}.tax-number{font-size:9.5px;margin-bottom:2px}.title{border:1.8px solid #111;text-align:center;font-size:18px;font-weight:800;padding:4px;margin:10px 0 18px}.meta{display:grid;grid-template-columns:1.35fr 1fr;gap:38px;margin-bottom:22px}.meta-title{font-weight:700;font-size:9px;text-transform:uppercase;margin-bottom:5px}.party{line-height:1.55}.doc-meta{display:grid;grid-template-columns:105px 1fr;line-height:1.7}.doc-meta b{font-weight:700}.items{width:100%;border-collapse:collapse}.items th{background:#090909!important;color:#fff!important;padding:6px 5px;font-size:9px;-webkit-print-color-adjust:exact;print-color-adjust:exact}.items td{border:1px solid #111;padding:5px;height:23px}.items .num{text-align:right;white-space:nowrap}.items .center{text-align:center}.totals{margin-left:auto;width:42%;border-collapse:collapse;margin-top:-1px}.totals td{border:1px solid #111;padding:5px}.totals td:last-child{text-align:right}.totals .grand{font-weight:800;background:#edf3fa!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}.notes{margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px;min-height:54px}.note-box{white-space:pre-line;line-height:1.5}.bank{border:2px solid #111;padding:8px;text-align:center;margin-top:18px;white-space:pre-line;line-height:1.5}.signatures{display:grid;grid-template-columns:1fr 1.2fr;gap:50px;margin-top:24px;align-items:end;break-inside:avoid;page-break-inside:avoid}.receiver{text-align:center}.signature-space{height:76px}.line{border-top:1px solid #111;padding-top:4px;font-weight:700}.digital-signature{border:1.5px solid #111;padding:7px;display:grid;grid-template-columns:82px 1fr;gap:10px;min-height:100px;text-align:left;align-items:center}.digital-signature img{width:78px;height:78px;display:block}.signature-copy{font-size:9px;line-height:1.35}.signature-copy .signed-by{font-size:8.5px;margin-bottom:2px}.signature-copy .name{font-size:11px;font-weight:800;margin:3px 0}.signature-copy .role{font-weight:700}.signature-copy .verify{font-size:7.5px;color:#555;margin-top:5px;word-break:break-word}.print-footer{text-align:center;margin-top:10px;font-size:7.5px;color:#666}@media print{body{background:#fff}.print-actions{display:none!important}.page{width:auto;min-height:auto;margin:0;padding:0}.letterhead{padding-top:0}a{color:inherit;text-decoration:none}}
    </style>
    <script src="{{ asset('js/document-print.js') }}" defer></script>
</head>
<body>
@php
    $printLogo = $settings[$config['kind'] . '_print_logo'] ?? $settings['company_print_logo'] ?? null;
    $companyName = strtoupper($settings['company_name'] ?? 'AULIACHEM PERKASA');
    $signerName = $document->salesUser?->name ?? 'Administrator';
    $signerPosition = $document->salesUser?->position ?: '-';
    $isQuotation = $config['kind'] === 'quotation';
@endphp
<div class="print-actions"><button type="button" data-print-document>Cetak / Simpan PDF</button></div>
<main class="page">
    <header class="letterhead {{ $isQuotation ? 'quotation-letterhead' : '' }}">
        <div class="letterhead-brand">
            @if(!empty($printLogo))
                <img class="logo" src="{{ asset('storage/' . $printLogo) }}" alt="Logo {{ $companyName }}">
            @else
                <div class="wordmark">AULIA<span class="chem">CHEM</span></div>
                <div class="wordmark-sub">PERKASA</div>
                <div class="wordmark-tagline">CHEMICAL &amp; LABORATORY SOLUTION</div>
            @endif
        </div>
        @if($isQuotation)
            <div class="quotation-company-info">
                @if(!empty($settings['company_tax_number']))<div><b>Tax Company</b>: {{ $settings['company_tax_number'] }}</div>@endif
                @if(!empty($settings['company_website']))<div><b>Website</b>: {{ preg_replace('#^https?://#', '', $settings['company_website']) }}</div>@endif
                @if(!empty($settings['company_email']))<div><b>Email</b>: {{ $settings['company_email'] }}</div>@endif
            </div>
        @else
            <div class="company-info">
                @if(!empty($settings['company_tax_number']))<div class="tax-number">Tax No: {{ $settings['company_tax_number'] }}</div>@endif
                {{ $settings['company_address'] ?? 'Bratang Gede IV No. 8, Surabaya - East Java, Indonesia' }}
                @if(!empty($settings['company_phone'])) &bull; Phone &amp; Fax: {{ $settings['company_phone'] }} @endif
                @if(!empty($settings['company_email'])) &bull; {{ $settings['company_email'] }} @endif
                @if(!empty($settings['company_website'])) &bull; {{ preg_replace('#^https?://#', '', $settings['company_website']) }} @endif
            </div>
        @endif
    </header>

    <div class="title">{{ $config['title'] }}</div>
    <section class="meta">
        <div>
            @unless($isQuotation)<div class="meta-title">Kepada:</div>@endunless
            <div class="party"><b>{{ $document->customer_name }}</b><br>{!! nl2br(e($document->customer_address ?: '-')) !!}@if(!$isQuotation && $document->customer_phone)<br>Telp {{ $document->customer_phone }}@endif</div>
        </div>
        <div class="doc-meta">
            <b>Tanggal</b><span>{{ $document->{$config['date_field']}?->translatedFormat('d F Y') }}</span>
            <b>No. {{ $config['label'] }}</b><span>{{ $document->{$config['number_field']} }}</span>
            <b>{{ $config['secondary_date_label'] }}</b><span>{{ $document->{$config['secondary_date_field']}?->translatedFormat('d F Y') ?? '-' }}</span>
            @if($config['kind'] === 'invoice' && $document->purchaseOrder)<b>No. PO</b><span>{{ $document->purchaseOrder->po_number }}</span>@endif
        </div>
    </section>

    <table class="items">
        <thead><tr><th style="width:36px">NO</th><th>DESKRIPSI</th><th style="width:62px">UNIT</th><th style="width:82px">QTY</th><th style="width:115px">HARGA</th><th style="width:125px">JUMLAH</th></tr></thead>
        <tbody>
        @foreach($document->items as $index => $item)
            <tr><td class="center">{{ $index + 1 }}</td><td><b>{{ $item->item_name }}</b>@if($item->description)<br><span style="font-size:9px">{{ $item->description }}</span>@endif</td><td class="center">{{ $item->unit }}</td><td class="num">{{ number_format((float)$item->qty, 3, ',', '.') }}</td><td class="num">{{ $document->currency === 'IDR' ? idr($item->unit_price) : $document->currency . ' ' . number_format($item->unit_price, 2) }}</td><td class="num">{{ $document->currency === 'IDR' ? idr($item->subtotal) : $document->currency . ' ' . number_format($item->subtotal, 2) }}</td></tr>
        @endforeach
        @for($i=$document->items->count();$i<8;$i++)<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
        </tbody>
    </table>
    <table class="totals">
        <tr><td>Subtotal</td><td>{{ $document->currency === 'IDR' ? idr($document->subtotal) : $document->currency . ' ' . number_format($document->subtotal, 2) }}</td></tr>
        @if((float)$document->tax_percent > 0)<tr><td>PPN {{ number_format((float)$document->tax_percent, 0) }}%</td><td>{{ $document->currency === 'IDR' ? idr($document->tax_amount) : $document->currency . ' ' . number_format($document->tax_amount, 2) }}</td></tr>@endif
        <tr class="grand"><td>TOTAL</td><td>{{ $document->currency === 'IDR' ? idr($document->grand_total) : $document->currency . ' ' . number_format($document->grand_total, 2) }}</td></tr>
    </table>

    <section class="notes">
        <div>@if($document->notes)<b>Catatan:</b><div class="note-box">{{ $document->notes }}</div>@endif</div>
        <div>@if($document->terms)<b>Syarat &amp; Ketentuan:</b><div class="note-box">{{ $document->terms }}</div>@endif</div>
    </section>
    @if($config['kind'] === 'invoice' && $document->bank_details)<div class="bank"><b>INFORMASI PEMBAYARAN</b><br>{{ $document->bank_details }}</div>@endif

    <section class="signatures">
        <div class="receiver">Diterima oleh:<div class="signature-space"></div><div class="line">Nama &amp; tanda tangan</div></div>
        <div class="digital-signature">
            <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" title="Buka verifikasi dokumen"><img src="{{ $signatureQr }}" alt="QR verifikasi tanda tangan elektronik"></a>
            <div class="signature-copy">
                <div class="signed-by">Dokumen ini ditandatangani secara elektronik oleh:</div>
                <div class="name">{{ $signerName }}</div>
                <div class="role">{{ $signerPosition }}</div>
                <div>{{ $companyName }}</div>
                <div class="verify">Pindai QR untuk memverifikasi keaslian dokumen.</div>
            </div>
        </div>
    </section>
    <div class="print-footer">Tanda tangan elektronik pada dokumen ini dapat diverifikasi melalui kode QR di atas.</div>
</main>
</body>
</html>
