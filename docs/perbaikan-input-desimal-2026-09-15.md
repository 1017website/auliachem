# Perbaikan input desimal

Seluruh input angka bisnis pada form aktif yang diaudit kini memakai batas maksimal tiga digit desimal: harga, Qty, stok, target User, probability Lead, rating Supplier, serta PPN. Batas nilai tetap berlaku, termasuk rating 0–5, probability/PPN 0–100, dan Qty dokumen minimal 0,001.

- Normalisasi server menerima koma dan kombinasi pemisah ribuan/desimal seperti `1.000,567`, tanpa mengubah angka titik desimal yang sudah dikirim dalam format mesin.
- Validasi create/update menolak lebih dari tiga digit desimal; model dan kolom database diselaraskan.
- Input Qty Lead/Customer, stok, rating, probability, dan PPN memakai input teks dengan keyboard desimal, sehingga penerimaan koma tidak bergantung pada input number browser.
- Input lebih dari tiga desimal tidak lagi dipotong. Validasi form menampilkan pesan dan pemformatan saat blur melewati input yang tidak valid.
- Harga dan PPN Penawaran/Invoice mempertahankan tiga desimal saat dibuka untuk edit.
- Migrasi database lokal sudah dijalankan. Saat diterapkan pada server lain, jalankan `php artisan migrate --force` setelah kode diperbarui.

## Verifikasi

- 14 tes backend terkait lulus, 224 assertions, mencakup create/update/read, harga, Qty, stok, target, probability, rating, PPN, serta batas presisi dan rentang.
- 3 tes JavaScript lulus: normalisasi koma, penolakan presisi berlebih tanpa mengubah nilai, serta batas nilai.
- Kompilasi Blade berhasil; metadata database lokal sudah diperiksa setelah migrasi.
- Tes ini tidak mencakup klik manual seluruh form di browser.

Nilai lama yang pecahannya sudah hilang tidak dapat dikembalikan oleh migrasi. Jalur import Lead lama (`potensi_revenue`) yang bukan input aktif tidak diubah pada perbaikan form ini.
