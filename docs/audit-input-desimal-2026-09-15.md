# Audit input desimal

Tanggal: 15 September 2026. Audit saja; tidak mengubah kode aplikasi atau data.

## Kesimpulan

Belum semua input angka mempertahankan desimal. Pemeriksaan meliputi form tambah/edit, JavaScript, validasi controller, model Eloquent, migrasi, dan metadata kolom database lokal yang sedang terhubung. Nomor telepon, NPWP, kode dokumen, ID, tanggal, dan hitungan sistem bukan nilai pecahan sehingga tidak diperlakukan sebagai input desimal.

## Temuan

1. **P1 — Target User menerima desimal tetapi database bilangan bulat.** Form `resources/views/users/index.blade.php:181` dan `:212` memakai `idr-input`, yang mengonversi koma menjadi titik ketika submit. `app/Http/Controllers/UserController.php:63` dan `:81` menerima numeric. Kolom aktual `users.target` adalah `bigint(20)`, sehingga pecahan tidak dapat dipertahankan. Perlu kolom decimal dan pengujian simpan/buka ulang.

2. **P1 — Harga Master Barang, Penawaran, dan Invoice membulatkan angka desimal ketiga.** Form harga mengizinkan tiga angka pecahan lewat `resources/views/layouts/app.blade.php:2167`, tetapi kolom aktual `products.buy_price`, `products.sell_price`, `quotation_items.unit_price`, dan `invoice_items.unit_price` adalah `decimal(18,2)`. Model juga memakai `decimal:2` (`app/Models/Product.php:20`, `app/Models/QuotationItem.php:11`, `app/Models/InvoiceItem.php:11`). Uji model langsung membuktikan `14.567` menjadi `14.57` pada ketiga model; PO mempertahankan `14.567`. Angka dua desimal seperti `14,56` berada dalam presisi yang didukung, tetapi `14,567` tidak. Perlu menyamakan presisi form, model, dan database.

3. **P2 — Dukungan koma pada input number belum konsisten.** Qty Lead (`resources/views/leads/index.blade.php:317`, `show.blade.php:484`), Qty Customer (`resources/views/customers/index.blade.php:631`), stok (`resources/views/products/index.blade.php:62`), PPN (`resources/views/documents/index.blade.php:115`), dan rating Supplier (`resources/views/suppliers/index.blade.php:413`) memakai input number tanpa normalisasi koma aplikasi. Browser dapat menormalisasi separator sesuai locale, tetapi perilaku mengetik/paste koma belum diuji di browser. Uji validator menunjukkan string `14,56` ditolak oleh aturan numeric apabila sampai ke server tanpa normalisasi. Ini risiko kompatibilitas input koma, bukan bukti bahwa semua browser gagal. Perlu penanganan separator yang konsisten dan uji browser.

4. **P2 — Probability, rating, dan PPN memiliki batas presisi berbeda.** Probability Lead memakai input tanpa step desimal, validasi integer (`app/Http/Controllers/LeadsController.php:64`, `:145`), dan kolom `int(11)`: pecahan ditolak. Rating Supplier dibatasi step `0.1` dan `decimal(3,1)`, sehingga `4,56` tidak didukung utuh. PPN memakai step `0.01`, cast dua desimal, dan `decimal(5,2)`: mendukung dua angka pecahan, bukan tiga. Jika persyaratan berlaku untuk seluruh nilai numerik bisnis, field-field ini juga perlu diselaraskan.

5. **P2 — Batas tiga desimal memotong input tambahan tanpa pesan.** Formatter `idr-input`, Qty PO/dokumen, dan harga PO memakai `.slice(0, 3)`. Input `14,5678` dibatasi menjadi `14,567`. Database Qty/stok dan harga PO juga berpresisi tiga. Perlu batas presisi eksplisit dan validasi yang memberi tahu pengguna ketika melebihi batas, bukan menghilangkan digit diam-diam.

## Ringkasan cakupan

| Input | Presisi database aktual | Status |
|---|---|---|
| PO: Qty, harga beli/jual | 3 | Simpan/edit sudah diuji; maksimum 3 desimal |
| Penawaran/Invoice: Qty | 3 | Normalisasi koma tersedia; maksimum 3 |
| Penawaran/Invoice: harga | 2 | Tidak sesuai form yang menerima 3 |
| Penawaran/Invoice: PPN | 2 | Step 0.01; koma bergantung browser |
| Master Barang: harga | 2 | Tidak sesuai form yang menerima 3 |
| Master Barang: stok/minimum stok | 3 | Step 0.001; koma bergantung browser |
| Lead/Customer: Qty produk | 3 | Step 0.001; koma bergantung browser |
| User: target | 0 | Tidak mempertahankan pecahan |
| Lead: probability | 0 | Pecahan ditolak validasi |
| Supplier: rating | 1 | Maksimum 1 desimal |

Tambahan jalur lama: `leads.potensi_revenue` masih `decimal(15,0)`; jalur import `LeadsController.php:479` memakai `is_numeric`, sehingga teks koma menjadi fallback nol. Field ini tidak ditemukan sebagai input angka aktif pada form yang diaudit, dan tidak ada pada `$fillable` Lead; perlu audit import terpisah jika jalur tersebut digunakan. Lead score adalah hasil/field internal satu desimal, bukan input angka aktif yang ditemukan.

## Verifikasi dan batas audit

- Metadata kolom aktual diperiksa read-only; sesuai batas presisi di atas.
- Probe model tanpa penyimpanan membuktikan pembulatan tiga desimal pada Product, QuotationItem, dan InvoiceItem.
- Probe validasi numeric menolak string koma mentah.
- `php artisan test --filter='PurchaseOrder|ProductMaster|SalesDocumentModules'`: 12 lulus, 2 gagal, 170 assertion. Dua kegagalan mengharapkan awalan nomor dokumen Agustus 2026, sementara nomor yang dihasilkan September 2026 (`SalesDocumentModulesTest.php:39`, `:100`); bukan bukti masalah desimal.
- Belum dilakukan pengujian interaktif browser seluruh form ataupun pengujian simpan/buka ulang untuk setiap field. Keberhasilan tes lama tidak membuktikan seluruh kasus pecahan sudah tercakup.

Prioritas tindak lanjut: perbaiki target dan ketidaksesuaian harga; standarkan normalisasi koma; tetapkan presisi pecahan per field; tambah pengujian create/update/reopen yang mencakup koma, pemisah ribuan, nol, serta input yang melebihi presisi.
