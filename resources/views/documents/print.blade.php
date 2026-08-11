<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>{{ $document->{$config['number_field']} }} - {{ $config['label'] }}</title>
<style>
@page{size:A4;margin:12mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0;font-size:11px}.page{width:100%;min-height:270mm;padding:5mm 7mm;position:relative}.print-actions{position:fixed;right:18px;top:18px;display:flex;gap:8px}.print-actions button{border:0;border-radius:6px;padding:9px 14px;background:#1f4d8f;color:#fff;cursor:pointer}.header{text-align:center;margin-bottom:18px}.logo{max-height:58px;max-width:260px}.company-name{font-size:24px;font-weight:800;color:#13539a;letter-spacing:.5px}.company-info{font-size:9px;margin-top:4px}.title{border:2px solid #111;text-align:center;font-size:20px;font-weight:800;padding:3px;margin:12px 0 22px}.meta{display:grid;grid-template-columns:1.35fr 1fr;gap:38px;margin-bottom:26px}.meta-title{font-weight:700;font-size:10px;text-transform:uppercase;margin-bottom:5px}.party{line-height:1.55}.doc-meta{display:grid;grid-template-columns:110px 1fr;line-height:1.7}.doc-meta b{font-weight:700}.items{width:100%;border-collapse:collapse}.items th{background:#090909;color:#fff;padding:6px 5px;font-size:10px}.items td{border:1px solid #111;padding:5px}.items .num{text-align:right;white-space:nowrap}.items .center{text-align:center}.totals{margin-left:auto;width:42%;border-collapse:collapse;margin-top:-1px}.totals td{border:1px solid #111;padding:5px}.totals td:last-child{text-align:right}.totals .grand{font-weight:800;background:#edf3fa}.notes{margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:20px}.note-box{white-space:pre-line;line-height:1.5}.bank{border:2px solid #111;padding:8px;text-align:center;margin-top:22px;white-space:pre-line;line-height:1.5}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:100px;margin-top:34px;text-align:center}.signature-space{height:68px}.line{border-top:1px solid #111;padding-top:4px;font-weight:700}@media print{.print-actions{display:none}.page{padding:0;min-height:auto}}
</style></head><body>
<div class="print-actions"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
<div class="page">
    <div class="header">
        @if(!empty($settings['company_logo']))<img class="logo" src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Logo">
        @else<div class="company-name">{{ strtoupper($settings['company_name'] ?? 'AULIACHEM PERKASA') }}</div>@endif
        <div class="company-info">{{ $settings['company_address'] ?? 'Bratang Gede IV No. 8, Surabaya - Indonesia' }} @if(!empty($settings['company_phone'])) · Telp {{ $settings['company_phone'] }} @endif</div>
    </div>
    <div class="title">{{ $config['title'] }}</div>
    <div class="meta">
        <div><div class="meta-title">Kepada:</div><div class="party"><b>{{ $document->customer_name }}</b><br>{!! nl2br(e($document->customer_address ?: '-')) !!}@if($document->customer_phone)<br>Telp {{ $document->customer_phone }}@endif</div></div>
        <div class="doc-meta">
            <b>Tanggal</b><span>{{ $document->{$config['date_field']}?->translatedFormat('d F Y') }}</span>
            <b>No. {{ $config['label'] }}</b><span>{{ $document->{$config['number_field']} }}</span>
            <b>{{ $config['secondary_date_label'] }}</b><span>{{ $document->{$config['secondary_date_field']}?->translatedFormat('d F Y') ?? '-' }}</span>
            @if($config['kind'] === 'invoice' && $document->purchaseOrder)<b>No. PO</b><span>{{ $document->purchaseOrder->po_number }}</span>@endif
        </div>
    </div>
    <table class="items"><thead><tr><th style="width:36px">NO</th><th>DESKRIPSI</th><th style="width:70px">UNIT</th><th style="width:85px">QTY</th><th style="width:125px">HARGA</th><th style="width:135px">JUMLAH</th></tr></thead><tbody>
        @foreach($document->items as $index => $item)<tr><td class="center">{{ $index + 1 }}</td><td><b>{{ $item->item_name }}</b>@if($item->description)<br><span style="font-size:9px">{{ $item->description }}</span>@endif</td><td class="center">{{ $item->unit }}</td><td class="num">{{ number_format((float)$item->qty, 3, ',', '.') }}</td><td class="num">{{ $document->currency === 'IDR' ? idr($item->unit_price) : $document->currency . ' ' . number_format($item->unit_price, 2) }}</td><td class="num">{{ $document->currency === 'IDR' ? idr($item->subtotal) : $document->currency . ' ' . number_format($item->subtotal, 2) }}</td></tr>@endforeach
        @for($i=$document->items->count();$i<8;$i++)<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
    </tbody></table>
    <table class="totals"><tr><td>Subtotal</td><td>{{ idr($document->subtotal) }}</td></tr>@if((float)$document->tax_percent > 0)<tr><td>PPN {{ number_format((float)$document->tax_percent, 0) }}%</td><td>{{ idr($document->tax_amount) }}</td></tr>@endif<tr class="grand"><td>TOTAL</td><td>{{ idr($document->grand_total) }}</td></tr></table>
    <div class="notes"><div>@if($document->notes)<b>Catatan:</b><div class="note-box">{{ $document->notes }}</div>@endif</div><div>@if($document->terms)<b>Syarat & Ketentuan:</b><div class="note-box">{{ $document->terms }}</div>@endif</div></div>
    @if($config['kind'] === 'invoice' && $document->bank_details)<div class="bank"><b>INFORMASI PEMBAYARAN</b><br>{{ $document->bank_details }}</div>@endif
    <div class="signatures"><div>Diterima oleh:<div class="signature-space"></div><div class="line">Nama & tanda tangan</div></div><div>{{ strtoupper($settings['company_name'] ?? 'AULIACHEM PERKASA') }}<div class="signature-space"></div><div class="line">{{ $document->salesUser?->name ?? 'Authorized Signature' }}</div></div></div>
</div></body></html>
