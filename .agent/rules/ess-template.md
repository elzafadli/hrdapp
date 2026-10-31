---
trigger: always_on
description: Standard architecture, UI/UX patterns, and base templates for Employee Self Service (ESS) modules
---

# Standar & Base Template Modul ESS (Employee Self Service)

Seluruh modul ESS (seperti Cuti Tahunan, Izin, Sakit, Lembur, Klaim/Reimbursement, dll.) **WAJIB** mengacu pada struktur dan pola yang diterapkan pada modul Cuti Tahunan:
📁 **Base Template Folder:** `resources/views/ess/leave/`
📁 **Base Controller:** `app/Http/Controllers/LeaveRequestController.php`

---

## 1. Struktur Halaman & Penamaan File

Setiap fitur ESS minimal memiliki 3 view utama:
- `resources/views/ess/<fitur>/index.blade.php` (Dashboard riwayat & inbox approval)
- `resources/views/ess/<fitur>/create.blade.php` (Formulir pengajuan baru)
- `resources/views/ess/<fitur>/show.blade.php` (Detail pengajuan, riwayat audit, & approval)

---

## 2. Standar Tampilan & Interaksi UI/UX

### A. Halaman Index (`index.blade.php`)
1. **Statistik / Saldo Card:** Menampilkan sisa jatah/kuota (Total, Terpakai, Sisa Tersedia) di bagian atas.
2. **Approval Inbox Card:** Muncul hanya jika user adalah approver yang memiliki permohonan bawahan berstatus `pending`. Dilengkapi tombol cepat *Lihat* dan *Setujui*.
3. **Tabel Riwayat Mandiri:**
   - Menampilkan nomor pengajuan, tanggal pengajuan, rentang tanggal/waktu riil, total hari kerja, status badge, dan tombol aksi *Detail*.
   - Status badge menggunakan warna standar: Hijau (`Disetujui` / `completed`), Kuning (`Menunggu Persetujuan` / `pending`), Merah (`Ditolak` / `rejected`), Abu-abu (`Dibatalkan` / `cancelled`).

### B. Halaman Form Pengajuan (`create.blade.php`)
1. **Header Sederhana:** Tombol panah kembali (`fa-arrow-left`) ke index + Judul modul & sub-keterangan.
2. **Notice Ketentuan:** Banner info kuning di bagian atas (misal ketentuan H-1, ketentuan berkas pendukung).
3. **Card Simulasi Real-Time (Live Preview Calculation):**
   - Menampilkan simulasi potongan dan sisa saldo secara langsung saat tanggal/tipe diubah.
   - **Loading Block & Auto-Unlock:** Saat kalkulasi preview via AJAX berjalan, card ditutup overlay semi-transparan (`bg-white/85 backdrop-blur-[2px]`) dengan spinner berputar. Otomatis unlock saat selesai.
   - **Badge Status Kelayakan:** Menampilkan badge real-time (`Bisa Diajukan`, `Saldo Kurang!`, `Tanggal Bentrok!`, `Back-date Dilarang!`, `Hari Libur / Off (0 Hari)`).
4. **Form Inputs:**
   - **Wajib menggunakan Blade Component:** `<x-text-input>`, `<x-select-input>`, `<x-input-label>`, `<x-input-error>`.
   - **Datepicker Terintegrasi Flatpickr:** Menggunakan Flatpickr lokal berbahasa Indonesia (`locale: 'id'`). Tanggal lampau (back-date), hari libur off, dan tanggal yang sudah pernah diajukan **otomatis di-disable (dicoret & unclickable)**.
   - **Quick Reason Template:** Tombol "Gunakan template" untuk mengisi keterangan secara cepat.
   - **Upload File:** Drag/klik upload dengan validasi klien ukuran maksimal 3 MB per file dan batas jumlah file.
5. **Tombol Aksi di Bawah Form:**
   - Tombol diletakkan di **bagian bawah kanan** formulir (Tombol *Batal* abu-abu & Tombol *Ajukan* biru mencolok).
   - **Proteksi Double-Submit:** Saat tombol diklik, state `isSubmitting = true`, tombol langsung disabled dengan spinner `Mengajukan...`, dan event submit berulang dicegah via `$event.preventDefault()`.
   - Tombol wajib otomatis disabled saat kalkulasi preview sedang berjalan (`isLoading`), saat tanggal bentrok, saat back-date, atau saat saldo tidak mencukupi.

### C. Halaman Detail Pengajuan (`show.blade.php`)
1. Header navigasi kembali, nomor permohonan, dan status badge besar.
2. Data pemohon (Nama, Jabatan, Waktu Pengajuan).
3. Rincian tanggal kerja riil terpotong (audit absensi kalender).
4. Lampiran dokumen yang bisa diunduh/dilihat.
5. **Alur Persetujuan Berjenjang (Multi-Level Workflow):**
   - Timeline berurutan berdasarkan level jabatan (`DocumentApproval`).
   - Riwayat audit historis lengkap (`ApprovalHistory`).
   - Form aksi bagi approver yang berwenang (Catatan persetujuan, tombol *Setujui*, dan tombol *Tolak*).
   - Opsi *Ajukan Ulang (Resubmit)* jika permohonan ditolak sebelumnya.

---

## 3. Logika Backend & Validasi Controller
1. **Pengecekan Hari Kerja:** Integrasi dengan jadwal kerja karyawan (`isWorkDay`) dan tabel `holidays` (libur nasional). Akhir pekan dan hari libur tidak memotong kuota.
2. **Validasi Anti-Bentrok (Overlap):** Memeriksa tanggal yang diajukan terhadap seluruh riwayat cuti karyawan berstatus `['pending', 'approved', 'completed']`.
3. **Validasi Back-Date:** Menolak pengajuan tanggal lampau / di luar ketentuan minimal hari pengajuan.
4. **Approval Multi-Level Fleksibel:** Jika ada aturan approval di `approval_settings`, generate approval berjenjang. Jika belum ada aturan approval, status otomatis `completed` dan kuota langsung terpotong.

