# 📘 Buku Panduan Pengguna & Pengujian Lengkap (Finova User Manual & Testing Guide)

Selamat datang di **Finova (Personal Wealth & Cash Flow Tracker)**! Dokumen ini dirancang sebagai panduan operasional sekaligus panduan pengujian langkah-demi-langkah (*end-to-end testing checklist*) untuk setiap menu dan fitur yang ada di Finova, mulai dari persiapan awal hingga otomatisasi AI dan WhatsApp.

---

## 📑 Daftar Isi

1. [Persiapan Awal & Menjalankan Aplikasi](#1-persiapan-awal--menjalankan-aplikasi)
2. [Modul 1: Autentikasi & Keamanan Akun](#2-modul-1-autentikasi--keamanan-akun)
3. [Modul 2: Pengaturan Profil & Integrasi WhatsApp](#3-modul-2-pengaturan-profil--integrasi-whatsapp)
4. [Modul 3: Akun Kas & Bank (Accounts)](#4-modul-3-akun-kas--bank-accounts)
5. [Modul 4: Kategori Transaksi (Categories)](#5-modul-4-kategori-transaksi-categories)
6. [Modul 5: Pencatatan Transaksi (Transactions)](#6-modul-5-pencatatan-transaksi-transactions)
7. [Modul 6: Finova AI Quick Input (Pencatatan Natural Language)](#7-modul-6-finova-ai-quick-input-pencatatan-natural-language)
8. [Modul 7: Dashboard Finansial (Financial Dashboard)](#8-modul-7-dashboard-finansial-financial-dashboard)
9. [Modul 8: Portofolio & Aset Investasi (Assets & Investments)](#9-modul-8-portofolio--aset-investasi-assets--investments)
10. [Modul 9: Kewajiban & Hutang (Liabilities)](#10-modul-9-kewajiban--hutang-liabilities)
11. [Modul 10: Anggaran Belanja (Budgets)](#11-modul-10-anggaran-belanja-budgets)
12. [Modul 11: Transaksi Berulang (Recurring Transactions)](#12-modul-11-transaksi-berulang-recurring-transactions)
13. [Modul 12: Laporan Keuangan (Reports)](#13-modul-12-laporan-keuangan-reports)
14. [Modul 13: Integrasi WhatsApp Bot (Finova AI via Chat)](#14-modul-13-integrasi-whatsapp-bot-finova-ai-via-chat)
15. [Modul 14: Instalasi PWA di Ponsel (Android & iOS)](#15-modul-14-instalasi-pwa-di-ponsel-android--ios)
16. [Checklist Skenario Pengujian Menyeluruh (Master Testing Checklist)](#16-checklist-skenario-pengujian-menyeluruh-master-testing-checklist)

---

## 1. Persiapan Awal & Menjalankan Aplikasi

Sebelum memulai pengujian, pastikan service backend dan frontend berjalan dengan benar.

### A. Menjalankan di Komputer / Laptop
Buka Terminal dan jalankan perintah berikut:
```bash
# 1. Jalankan Laravel Backend (dapat diakses dari HP via IP lokal WiFi yang sama)
php artisan serve --host=0.0.0.0 --port=8000

# 2. Buka aplikasi di browser laptop:
# http://127.0.0.1:8000 atau http://localhost:8000
```

> [!TIP]
> Jika Anda ingin mengakses aplikasi dari HP, pastikan HP dan laptop terhubung ke **jaringan WiFi yang sama**. Buka browser HP dan ketik: `http://<IP_LAPTOP_ANDA>:8000` (contoh: `http://192.168.1.101:8000`).

---

## 2. Modul 1: Autentikasi & Keamanan Akun

Menu ini mengelola akses masuk, pendaftaran akun baru, hingga fitur proteksi keamanan ganda.

```
+-----------------------------------------------------------------------+
|  Halaman Login / Register                                            |
|  - Email & Password                                                   |
|  - Login Sosial (Google, Apple, Facebook)                             |
|  - Biometric Passkey (Touch ID / Face ID)                             |
|  - Two-Factor Authentication (TOTP Google Authenticator)              |
+-----------------------------------------------------------------------+
```

### Langkah Pengujian & Penggunaan:

#### 1. Registrasi Akun Baru
1. Buka halaman `/register`.
2. Masukkan **Nama Lengkap**, **Alamat Email**, dan **Password**.
   - *Validasi*: Password minimal 12 karakter dengan kombinasi huruf besar, kecil, angka, dan simbol.
3. Klik tombol **Register**.
4. **Hasil yang diharapkan**: Akun berhasil dibuat dan Anda langsung diarahkan ke halaman utama `/dashboard`.

#### 2. Logout & Login Kembali
1. Klik avatar profil di pojok kiri bawah sidebar, lalu pilih **Log out**.
2. Anda akan dialihkan ke halaman `/login`.
3. Masukkan email dan password yang telah didaftarkan.
4. Centang **Remember Me** agar sesi tetap aktif.
5. Klik **Log in**.
6. **Hasil yang diharapkan**: Berhasil login masuk ke dashboard.

#### 3. Keamanan Tambahan: Two-Factor Authentication (2FA) & Passkeys
1. Buka menu **Settings** $\rightarrow$ **Security** (`/settings/security`).
2. **Aktifkan 2FA**:
   - Klik **Enable 2FA**.
   - Pindai QR Code menggunakan aplikasi Google Authenticator atau Authy di ponsel Anda.
   - Masukkan 6 digit kode OTP dan simpan *Recovery Codes* darurat.
3. **Tambahkan Passkey**:
   - Klik **Register Passkey**.
   - Konfirmasi sidik jari (Touch ID / Windows Hello) atau Face ID pada perangkat Anda.
   - Pada saat login berikutnya, Anda dapat memilih login tanpa password menggunakan Passkey.

---

## 3. Modul 2: Pengaturan Profil & Integrasi WhatsApp

Halaman ini mengatur preferensi finansial dan menghubungkan nomor WhatsApp ke bot AI Finova.

### Cara Akses:
Klik avatar di sidebar $\rightarrow$ **Settings** $\rightarrow$ **Profile** (`/settings/profile`).

### Kolom Pengaturan:
1. **Name**: Nama tampilan Anda.
2. **Email Address**: Alamat email akun.
3. **WhatsApp Number**: Masukkan nomor WhatsApp aktif Anda, contoh: `081234567890` atau `+6281234567890`.
   - *Fungsi*: Sistem akan menormalisasi nomor menjadi format internasional (`6281234567890`). Ini wajib diisi jika Anda ingin mencatat transaksi lewat WhatsApp.
4. **Default Currency**: Pilihan mata uang utama (`IDR`, `USD`, `EUR`, `SGD`).
5. **Timezone**: Zona waktu Anda (pilih `Asia/Jakarta (WIB)`, `Asia/Makassar (WITA)`, atau `Asia/Jayapura (WIT)`).
6. **Date Format**: Format tampilan tanggal (`DD/MM/YYYY`, `YYYY-MM-DD`, dsb).
7. **Month Start Day**: Tanggal awal perhitungan siklus bulanan (misal tanggal `25` untuk tanggal gajian).
8. Klik **Save**.
9. **Hasil yang diharapkan**: Muncul notifikasi hijau *"Profile updated."*.

---

## 4. Modul 3: Akun Kas & Bank (Accounts)

Menu ini mencatat seluruh dompet fisik, rekening bank, dan dompet digital (*e-wallet*) yang Anda miliki.

```
Sidebar -> Accounts (/accounts)
```

### Langkah Pengujian & Penggunaan:

#### 1. Membuat Akun Kas/Bank Baru
1. Masuk ke menu **Accounts** dari sidebar (`/accounts`).
2. Klik tombol **+ Add Account** di pojok kanan atas.
3. Isi data pada form **Create Account**:
   - **Basic Information**:
     - **Account Name**: Nama akun/rekening (contoh: `"Bank BNI"`, `"Main BCA"`, atau `"GoPay"`).
     - **Account Type**: Pilih tipe rekening (`Cash`, `Bank`, `E-Wallet`, atau `Other`).
     - **Currency**: Pilih mata uang (default: `IDR - Indonesian Rupiah`).
   - **Initial Balance**:
     - **Starting Balance (in IDR)**: Masukkan nominal saldo awal Anda saat ini (contoh: `2.000.000`).
   - **Additional Information (Optional)**:
     - **Description or Notes**: Catatan keterangan rekening jika ada (contoh: `"Bank Utama"`, `"Rekening Operasional"`).
4. Klik tombol **Save Account**.
5. **Hasil yang diharapkan**:
   - Akun baru muncul di daftar.
   - Saldo awal otomatis tercatat ke dalam sistem dengan aman (*initial balance adjustment*).
   - Nilai *Liquid Cash* pada dashboard otomatis bertambah sesuai saldo awal.

#### 2. Buat Beberapa Akun untuk Pengujian Lengkap
Disarankan membuat minimal 2 akun untuk menguji fitur transfer, misalnya:
- Akun 1: `BCA Rekening` (Saldo: Rp 10.000.000)
- Akun 2: `GoPay Dompet Digital` (Saldo: Rp 500.000)
- Akun 3: `Cash / Dompet Tunai` (Saldo: Rp 200.000)

---

## 5. Modul 4: Kategori Transaksi (Categories)

Menu ini mengelola pos-pos pemasukan dan pos-pos pengeluaran Anda.

```
Sidebar -> Categories (/categories)
```

### Langkah Pengujian & Penggunaan:

1. Buka menu **Categories**.
2. Anda akan melihat dua tab: **Expenses (Pengeluaran)** dan **Income (Pemasukan)**.
3. Klik tombol **+ Add Category**.
4. Isi data:
   - **Category Name**: Contoh `"Makanan & Minuman"`, `"Transportasi"`, `"Gaji"`.
   - **Type**: Pilih `Expense` atau `Income`.
   - **Icon & Color**: Pilih ikon (misal garpu & sendok untuk makanan, mobil untuk transportasi) dan palet warna.
   - **Parent Category** *(opsional)*: Untuk membuat sub-kategori (misal: "Kopi" sebagai anak dari "Makanan & Minuman").
5. Klik **Save Category**.
6. **Hasil yang diharapkan**: Kategori tersimpan dan siap digunakan saat mencatat transaksi atau membuat anggaran.

---

## 6. Modul 5: Pencatatan Transaksi (Transactions)

Menu utama untuk melihat riwayat mutasi keuangan serta mencatat transaksi secara manual.

```
Sidebar -> Transactions (/transactions)
```

### Langkah Pengujian & Skenario:

#### 1. Skenario A: Mencatat Pengeluaran (Expense)
1. Di halaman Transactions, klik tombol **+ Add Transaction**.
2. Isi form sesuai urutan tampilan di layar:
   - **Pilih Tab**: Klik tab **Expense** (tombol merah di paling atas).
   - **Amount**: Masukkan nominal pengeluaran di kolom angka utama (contoh: `50.000`).
   - **Description**: Masukkan keterangan singkat (contoh: `"Makan Siang Nasi Padang"`).
   - **Date**: Pilih tanggal transaksi (default hari ini).
   - **CATEGORY**: Pilih kartu kategori yang sesuai (contoh: klik tombol `"Food & Dining"`).
   - **ACCOUNT DETAILS**:
     - **Account**: Pilih rekening pembayaran (contoh: `"Bank BNI (IDR)"`).
     - **Additional Notes** *(opsional)*: Masukkan catatan tambahan jika diperlukan.
3. Klik tombol **Save Transaction**.
4. **Verifikasi Hasil**:
   - Anda otomatis diarahkan ke halaman `/transactions`.
   - Transaksi baru muncul di baris paling atas dengan nominal bertuliskan merah: **-Rp 50.000**.
   - Saldo rekening yang digunakan langsung terpotong sebesar Rp 50.000.

#### 2. Skenario B: Mencatat Pemasukan (Income)
1. Klik tombol **+ Add Transaction**.
2. Isi form:
   - **Pilih Tab**: Klik tab **Income** (tombol hijau).
   - **Amount**: Masukkan nominal pemasukan (contoh: `8.000.000`).
   - **Description**: Masukkan deskripsi (contoh: `"Gaji Bulanan"`).
   - **Date**: Pilih tanggal transaksi.
   - **CATEGORY**: Pilih kategori pemasukan (contoh: `"Salary"` atau kategori pemasukan Anda).
   - **ACCOUNT DETAILS**:
     - **Account**: Pilih rekening penerima dana (contoh: `"Bank BNI (IDR)"`).
     - **Additional Notes** *(opsional)*: Catatan tambahan.
3. Klik tombol **Save Transaction**.
4. **Verifikasi Hasil**:
   - Saldo rekening penerima bertambah Rp 8.000.000.
   - Transaksi tercatat dengan nominal hijau: **+Rp 8.000.000**.

#### 3. Skenario C: Transfer Antar Akun (Transfer)
1. Klik **+ Add Transaction**.
2. Isi form:
   - **Pilih Tab**: Klik tab **Transfer** (tombol biru).
   - **Amount**: Masukkan nominal transfer (contoh: `200.000`).
   - **Description**: Keterangan transfer (contoh: `"Top up GoPay"`).
   - **Date**: Tanggal transaksi.
   - **ACCOUNT DETAILS**:
     - **Source Account (From)**: Pilih akun sumber dana (contoh: `"Bank BNI"`).
     - **Destination Account (To)**: Pilih akun tujuan transfer (contoh: `"GoPay"`).
     - **Additional Notes** *(opsional)*: Catatan.
3. Klik tombol **Save Transaction**.
4. **Verifikasi Integritas**:
   - Saldo Bank BNI berkurang Rp 200.000.
   - Saldo GoPay bertambah Rp 200.000.
   - Total Net Worth Anda **tetap sama persis** karena uang hanya berpindah kantong.

---

## 7. Modul 6: Finova AI Quick Input (Pencatatan Natural Language)

Fitur cerdas untuk mencatat transaksi hanya dengan mengetik bahasa sehari-hari tanpa repot memilih dropdown satu per satu.

```
Tersedia langsung di bagian atas Dashboard (/dashboard)
```

### Contoh Kalimat yang Bisa Anda Coba:
Ketik salah satu teks di bawah ini pada kotak *"Finova AI Quick Input"*, lalu tekan **Enter**:

| Contoh Teks yang Diketik | Hasil Parsing Otomatis oleh AI |
| :--- | :--- |
| `beli kopi starbucks 45rb pake gopay` | Tipe: **Expense** \| Nominal: **Rp 45.000** \| Akun: **GoPay** \| Kategori: **Makanan & Minuman** |
| `gajian masuk bca 8.5juta` | Tipe: **Income** \| Nominal: **Rp 8.500.000** \| Akun: **BCA** \| Kategori: **Gaji** |
| `transfer bca ke gopay 150rb` | Tipe: **Transfer** \| Dari: **BCA** $\rightarrow$ Ke: **GoPay** \| Nominal: **Rp 150.000** |
| `beli bensin 50000` | Tipe: **Expense** \| Nominal: **Rp 50.000** \| Kategori: **Transportasi** |

### Alur Konfirmasi:
1. Kartu tinjauan (*AI Preview Card*) akan muncul di bawah kotak input.
2. Data yang diekstrak (nominal, akun, tanggal, deskripsi) ditampilkan dengan rapi.
3. Anda dapat mengedit jika ada detail yang ingin diubah.
4. Klik tombol centang hijau **"Confirm & Save"**.
5. Transaksi langsung tersimpan ke sistem buku besar.

---

## 8. Modul 7: Dashboard Finansial (Financial Dashboard)

Halaman pusat ringkasan seluruh kondisi kekayaan dan keuangan Anda secara *real-time*.

```
Sidebar -> Dashboard (/dashboard)
```

### Komponen yang Harus Diperiksa:
1. **Hero Net Worth Card**:
   - **Total Net Worth**: Menampilkan nilai total kekayaan bersih ($\text{Total Kas} + \text{Investasi} - \text{Hutang}$).
   - **Pill Breakdown**: Nilai *Liquid Cash*, *Investments*, dan *Liabilities*.
   - **Persentase Perubahan**: Indikator tren kenaikan/penurunan vs bulan lalu.
2. **Budget Summary Card**:
   - Menampilkan progres pemakaian anggaran bulan ini.
   - Indikator bar warna: **Hijau** (aman), **Kuning** (waspada), atau **Merah** (mendekati/melebihi batas).
   - Menampilkan jumlah kategori yang mengalami *over-budget*.
3. **Filter Periode Arus Kas**:
   - Klik tab **This Month** atau **Last Month**.
   - Grafik batang arus kas (*Income vs Expense vs Net Savings*) otomatis menyesuaikan.
4. **Donut Chart Alokasi Aset**:
   - Menunjukkan persebaran kekayaan (Cash, Saham, Reksadana, dsb) secara proporsional.
5. **Daftar 10 Transaksi Terkini**:
   - Tabel ringkas aktivitas transaksi terakhir dengan tautan cepat ke detail.

---

## 9. Modul 8: Portofolio & Aset Investasi (Assets & Investments)

Menu untuk melacak kepemilikan saham, reksadana, emas, kripto, maupun aset fisik seperti properti atau kendaraan.

```
Sidebar -> Portfolio (/assets)
```

### Langkah Pengujian:

#### 1. Mendaftarkan Akun Aset / Saham Baru
1. Buka menu **Portfolio**.
2. Klik **+ Add Asset Account**.
3. Isi data:
   - **Name**: `"Saham BBCA"` atau `"Emas Antam"`.
   - **Asset Type**: Pilih `Stocks` (atau `Gold & Precious Metals`).
   - **Ticker / Symbol**: Contoh `BBCA`.
   - **Current Market Price**: Masukkan harga pasar per lembar saat ini (contoh: `10.000`).
4. Klik **Save Asset**.

#### 2. Melakukan Transaksi Pembelian Aset (Buy)
1. Buka detail aset yang baru dibuat.
2. Klik tombol **+ Buy Asset**.
3. Masukkan:
   - **Quantity (Lots)**: Masukkan `2` lot (1 lot = 100 lembar, sistem otomatis menghitung 200 lembar).
   - **Price per Share**: `10.000`.
   - **Source Cash Account**: Pilih `BCA Rekening`.
4. Klik **Record Purchase**.
5. **Verifikasi Integritas Finansial**:
   - Saldo `BCA Rekening` berkurang Rp 2.000.000 ($2 \times 100 \times 10.000$).
   - Portofolio memiliki 2 lot Saham BBCA senilai Rp 2.000.000.
   - **Net Worth Anda tetap sama persis**, hanya bentuknya yang berpindah dari Kas menjadi Investasi.

#### 3. Update Harga Pasar (Market Price Update)
1. Pada kartu Saham BBCA, klik **Update Price**.
2. Ubah harga dari `10.000` menjadi `10.500`.
3. Klik **Save**.
4. **Verifikasi**:
   - Nilai aset kini menjadi Rp 2.100.000.
   - Muncul *Unrealized Profit/Loss*: **+Rp 100.000 (+5.0%)**.
   - Net Worth di dashboard otomatis naik sebesar Rp 100.000.

---

## 10. Modul 9: Kewajiban & Hutang (Liabilities)

Menu untuk mencatat dan memantau pinjaman, kartu kredit, cicilan KPR/KKB, serta pelunasannya.

```
Sidebar -> Liabilities (/liabilities)
```

### Langkah Pengujian:

#### 1. Menambah Akun Hutang / Kewajiban
1. Buka menu **Liabilities**.
2. Klik **+ Add Liability**.
3. Masukkan:
   - **Name**: Contoh `"Kartu Kredit BCA"` atau `"Pinjaman Bank KTA"`.
   - **Type**: `Credit Card` atau `Personal Loan`.
   - **Initial Balance**: Masukkan sisa pokok hutang (contoh: `5.000.000`).
   - **Credit Limit** *(khusus kartu kredit)*: Contoh `20.000.000`.
   - **Interest Rate (%)**: Contoh `1.75%`.
   - **Due Date (Tanggal Jatuh Tempo)**: Contoh tanggal `15`.
4. Klik **Create Liability**.
5. **Verifikasi**: Total kewajiban tercatat Rp 5.000.000 dan memotong Net Worth di dashboard secara seimbang.

#### 2. Mencatat Pembayaran Hutang / Cicilan
1. Buka detail kewajiban tersebut.
2. Klik tombol **Record Payment** (Bayar Tagihan).
3. Masukkan:
   - **Amount**: `1.000.000`.
   - **Source Account**: Pilih `BCA Rekening`.
   - **Date**: Tanggal hari ini.
4. Klik **Save Payment**.
5. **Verifikasi**:
   - Saldo `BCA Rekening` berkurang Rp 1.000.000.
   - Sisa hutang berkurang dari Rp 5.000.000 menjadi Rp 4.000.000.
   - Net Worth tetap stabil karena pengurangan kas seimbang dengan pengurangan hutang.

---

## 11. Modul 10: Anggaran Belanja (Budgets)

Fitur untuk membatasi pengeluaran per kategori agar tidak boros dalam satu bulan.

```
Sidebar -> Budgets (/budgets)
```

### Langkah Pengujian:

#### 1. Membuat Anggaran Bulan Berjalan
1. Buka menu **Budgets** $\rightarrow$ Klik **+ Buat Anggaran**.
2. Masukkan:
   - **Nama Anggaran**: `"Anggaran Bulan Ini"`.
   - **Tanggal Mulai**: Tanggal `1` bulan ini.
   - **Tanggal Selesai**: Akhir bulan ini.
3. Pada daftar kategori:
   - Masukkan limit untuk `Makanan & Minuman`: `1.500.000`.
   - Masukkan limit untuk `Transportasi`: `500.000`.
   - Masukkan limit untuk `Hiburan`: `300.000`.
4. Sistem otomatis menjumlahkan total anggaran (Rp 2.300.000).
5. Klik **Simpan Anggaran**.

#### 2. Menguji Progres & Alert Over-Budget
1. Masuk ke menu **Transactions** dan catat pengeluaran baru:
   - Kategori `Makanan & Minuman` sebesar `1.200.000` (80% terpakai).
2. Buka menu **Budgets**:
   - Progres bar kategori makanan akan berwarna **Kuning (Waspada - 80% Terpakai)**.
3. Catat transaksi pengeluaran lagi untuk makanan sebesar `400.000` (total menjadi 1.600.000 / lebih dari kuota 1.500.000):
   - Buka kembali menu **Budgets**:
   - Progres bar berubah menjadi **Merah (Bahaya/Over-budget)**.
   - Muncul spanduk peringatan merah: *"1 kategori telah melebihi batas anggaran! Total kelebihan pengeluaran: Rp 100.000"*.
   - Kartu di Dashboard utama juga ikut menampilkan badge merah peringatan tersebut.

---

## 12. Modul 11: Transaksi Berulang (Recurring Transactions)

Fitur otomatisasi untuk transaksi berkala seperti gaji bulanan, sewa kost, tagihan internet, atau langganan streaming.

```
Sidebar -> Recurring (/recurring-transactions)
```

### Langkah Pengujian:

#### 1. Membuat Template Transaksi Berulang
1. Buka menu **Recurring** $\rightarrow$ Klik **+ Buat Template Baru**.
2. Masukkan:
   - **Tipe**: Pilih `Pengeluaran Rutin` (atau `Pemasukan Rutin`).
   - **Nama**: `"Langganan Internet & TV"`.
   - **Nominal**: `350.000`.
   - **Akun**: `BCA Rekening`.
   - **Kategori**: `Utilitas / Tagihan`.
   - **Frekuensi**: Pilih `Setiap Bulan (Bulanan)`.
   - **Tanggal Eksekusi**: Contoh tanggal `20`.
   - **Opsi Auto-Confirm**: Centang agar langsung masuk saldo saat digenerate.
3. Klik **Simpan Template**.

#### 2. Menguji Eksekusi Manual (*Generate Now*)
1. Pada kartu template yang baru dibuat, Anda tidak perlu menunggu sampai tanggal 20 untuk menguji.
2. Klik tombol **"Generate Sekarang"** (ikon play).
3. Konfirmasi popup dialog.
4. Buka menu **Transactions**:
   - Transaksi baru sebesar Rp 350.000 langsung tercatat otomatis dengan sumber berlabel *"recurring"*.
   - Tanggal terakhir generate (*last generated date*) pada kartu template otomatis terupdate.

#### 3. Menguji Saklar Aktif / Nonaktif
1. Klik tombol saklar daya (*Power icon*) pada kartu.
2. Kartu akan menjadi redup (*disabled*) menandakan penjadwal tidak akan memproses template ini sampai Anda mengaktifkannya kembali.

---

## 13. Modul 12: Laporan Keuangan (Reports)

Menu analisis komprehensif untuk melihat tren finansial, rasio tabungan, dan alokasi pengeluaran.

```
Sidebar -> Reports (/reports)
```

### Analisis yang Tersedia:
1. **Cash Flow Summary**:
   - Total Pemasukan vs Total Pengeluaran vs Tabungan Bersih (*Net Savings*).
   - **Savings Rate (%)**: Berapa persen pendapatan Anda yang berhasil disisihkan menjadi tabungan/investasi.
2. **Net Worth Trend Chart**:
   - Grafik garis yang memperlihatkan pertumbuhan kekayaan bersih Anda dari bulan ke bulan.
3. **Kategori Pengeluaran Terbesar**:
   - Daftar peringkat kategori dari yang paling banyak menguras kantong beserta persentasenya.

---

## 14. Modul 13: Integrasi WhatsApp Bot (Finova AI via Chat)

Fitur unggulan Finova untuk mencatat keuangan langsung lewat obrolan WhatsApp tanpa perlu membuka aplikasi web.

```
             +--------------------------+
             | WhatsApp User (Ponsel)   |
             +-------------+------------+
                           |
                           v (Kirim pesan WA: "beli makan 25rb")
             +--------------------------+
             | WhatsApp Gateway / Webhook|
             +-------------+------------+
                           |
                           v (POST /api/webhooks/whatsapp)
             +-----------------------------------------+
             | Finova AI Engine & Parser               |
             | - Ekstraksi nominal, akun, kategori     |
             | - Verifikasi nomor telepon pengguna     |
             | - Auto-record jika akurasi tinggi       |
             +--------------------+--------------------+
                                  |
                                  v (Kirim Struk Balasan WA)
             +-----------------------------------------+
             | "✅ Transaksi Berhasil Dicatat: Rp 25.000"|
             +-----------------------------------------+
```

### Langkah Konfigurasi & Pengujian:

1. **Pastikan Nomor WhatsApp Terdaftar**:
   - Buka `/settings/profile` di aplikasi.
   - Masukkan nomor HP WhatsApp Anda (contoh: `081234567890`).
   - Simpan.
2. **Kirim Pesan Simulasi ke Webhook Finova**:
   - Anda dapat menguji endpoint webhook via curl / Postman:
   ```bash
   curl -X POST http://127.0.0.1:8000/api/webhooks/whatsapp \
     -H "Content-Type: application/json" \
     -d '{
       "sender": "6281234567890",
       "message": "beli kopi 25rb pake bca",
       "id": "wa-msg-001"
     }'
   ```
3. **Hasil yang Didapatkan**:
   - Sistem mencocokkan nomor pengirim dengan user pemilik akun.
   - AI menguraikan pesan $\rightarrow$ mencatat pengeluaran Rp 25.000 di akun BCA.
   - Gateway mengirimkan balasan WhatsApp:
     ```
     ✅ *Transaksi Berhasil Dicatat!*
     📌 *Tipe*: Expense
     💰 *Nominal*: Rp 25.000
     📝 *Deskripsi*: beli kopi 25rb pake bca
     ```
4. **Respon Conversational ("Ya" / "Ok")**:
   - Jika pesan Anda ambigu dan AI membutuhkan konfirmasi, bot akan membalas meminta konfirmasi.
   - Cukup balas *"ya"* atau *"ok"*, transaksi otomatis tersimpan.

---

## 15. Modul 14: Instalasi PWA di Ponsel (Android & iOS)

Finova telah dilengkapi *Progressive Web App* (PWA) lengkap dengan Service Worker, Web App Manifest, dan ikon maskable sehingga dapat diinstal seperti aplikasi native dari Play Store / App Store.

### Cara Install di Android (Google Chrome):
1. Sambungkan HP dan laptop ke WiFi yang sama.
2. Buka Chrome di HP dan ketik: `http://<IP_LAPTOP>:8000/dashboard`.
3. Akan muncul banner di bawah: **"Add Finova to Home screen"** (atau klik menu titik tiga di kanan atas $\rightarrow$ pilih **"Install app"** / **"Tambahkan ke Layar Utama"**).
4. Klik **Install**.
5. Ikon aplikasi Finova akan muncul di layar utama (*home screen*) ponsel Anda. Saat dibuka, aplikasi tampil layar penuh (*standalone*) tanpa bilah alamat browser.

### Cara Install di iOS / iPhone (Apple Safari):
1. Buka browser **Safari** di iPhone Anda.
2. Masuk ke: `http://<IP_LAPTOP>:8000/dashboard`.
3. Tekan tombol **Share** (ikon kotak dengan panah ke atas di bagian bawah layar).
4. Gulir ke bawah dan pilih **"Add to Home Screen" (Tambahkan ke Layar Utama)**.
5. Beri nama **"Finova"** lalu klik **Add**.
6. Aplikasi Finova kini siap digunakan langsung dari layar iPhone Anda.

---

## 16. Checklist Skenario Pengujian Menyeluruh (Master Testing Checklist)

Gunakan tabel checklist ini saat Anda melakukan pengetesan seluruh alur dari awal sampai akhir:

| No | Komponen / Fitur | Aksi Pengujian | Hasil yang Diharapkan | Status |
| :-: | :--- | :--- | :--- | :-: |
| 1 | **Registrasi & Login** | Buat user baru & login ulang | Berhasil masuk ke dashboard dengan session aktif | [ ] |
| 2 | **Profil & Mata Uang** | Ubah mata uang default & nomor WA di Settings | Profil terupdate dan nomor WA tersimpan | [ ] |
| 3 | **Buat Akun Kas** | Buat akun Bank BCA (saldo 10 jt) & GoPay (500 rb) | Akun tampil, saldo awal tercatat di sistem | [ ] |
| 4 | **Buat Kategori** | Buat kategori Makanan (expense) & Gaji (income) | Kategori muncul di daftar pemasukan & pengeluaran | [ ] |
| 5 | **Catat Pengeluaran** | Masukkan pengeluaran Rp 50.000 di akun BCA | Saldo BCA berkurang jadi Rp 9.950.000 | [ ] |
| 6 | **Catat Pemasukan** | Masukkan gaji Rp 5.000.000 ke akun BCA | Saldo BCA bertambah jadi Rp 14.950.000 | [ ] |
| 7 | **Transfer Antar Akun**| Transfer Rp 200.000 dari BCA ke GoPay | Saldo BCA berkurang, GoPay bertambah, Net Worth tetap | [ ] |
| 8 | **AI Quick Input** | Ketik `"beli nasi goreng 25rb pake gopay"` di Dashboard | AI memparse data, klik confirm $\rightarrow$ transaksi tersimpan | [ ] |
| 9 | **Beli Saham/Aset** | Beli 1 lot BBCA seharga Rp 1.000.000 dari BCA | Saldo BCA terpotong 1 jt, portofolio aset bertambah 1 jt | [ ] |
| 10 | **Update Harga Aset**| Naikkan harga saham BBCA | Nilai aset naik, timbul Unrealized P/L hijau | [ ] |
| 11 | **Catat Hutang** | Buat hutang KTA Rp 3.000.000 | Hutang tercatat, memotong nilai Net Worth | [ ] |
| 12 | **Bayar Hutang** | Bayar Rp 1.000.000 dari BCA ke hutang KTA | Saldo BCA berkurang 1 jt, sisa hutang jadi 2 jt | [ ] |
| 13 | **Buat Anggaran** | Pasang limit Makanan Rp 1.000.000 bulan ini | Progress bar muncul di menu Budgets & Dashboard | [ ] |
| 14 | **Uji Over-Budget** | Catat makan Rp 1.200.000 | Bar berubah merah & muncul alert over-budget Rp 200.000 | [ ] |
| 15 | **Template Berulang**| Buat recurring langganan Netflix bulanan | Template tersimpan dengan status aktif & jatuh tempo | [ ] |
| 16 | **Generate Berulang** | Klik "Generate Sekarang" pada template | Transaksi baru otomatis terbentuk di daftar transaksi | [ ] |
| 17 | **Verifikasi Saldo** | Jalankan command `php artisan finance:verify-balances` | Terverifikasi 100% konsisten tanpa diskrepansi | [ ] |
| 18 | **Install PWA** | Buka via browser HP dan klik Install | Finova terpasang di homescreen HP seperti aplikasi native | [ ] |

---

*Manual book ini diperbarui otomatis sesuai spesifikasi teknis dan implementasi Finova v1.5.*
