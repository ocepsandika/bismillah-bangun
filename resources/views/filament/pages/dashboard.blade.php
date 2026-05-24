<x-filament-panels::page>
    {{-- Page content --}}
</x-filament-panels::page>


/**
 * ==============================================================================
 * BLUEPRINT SISTEM E-ARSIP SMK TERPADU (LARAVEL & FILAMENT)
 * ==============================================================================
 * 
 * KONSEP UTAMA:
 * - Framework: Laravel & Filament v3
 * - Deployment: Server Lokal Sekolah
 * - Fokus: Manajemen Jabatan, Arsip Fisik, dan Keamanan Dokumen Rahasia.
 * 
 * STRUKTUR DATABASE (15 TABEL):
 * 
 * [Akses & Organisasi]
 * 1.  users             : Akun (Admin, Guru, Siswa).
 * 2.  positions         : Master Jabatan (Kepsek, Wakasek, Kaprog, dll).
 * 3.  user_position     : Pivot table untuk Jabatan Ganda.
 * 4.  departments       : Master Jurusan (RPL, TKRO, Akuntansi, dll).
 * 5.  academic_years    : Master Tahun Pelajaran.
 * 
 * [Penyimpanan Dokumen]
 * 6.  categories        : Jenis file (SK, RPP, Sertifikat, dll).
 * 7.  documents         : Arsip Umum & Rahasia (Fitur enkripsi & akses terbatas).
 * 8.  student_archives  : Arsip Siswa (ZIP Rapor, Legger, Ijazah per kelas).
 * 9.  document_versions : Riwayat revisi/sejarah perubahan dokumen.
 * 10. document_locations: Lokasi fisik dokumen (Nomor rak/lemari/box).
 * 
 * [Komunikasi & Keamanan]
 * 11. announcements     : Portal Pengumuman (Info Dokumen/Ulang Tahun).
 * 12. access_requests   : Sistem Izin akses dokumen rahasia.
 * 13. download_logs     : Tracking jejak digital pengunduhan file.
 * 14. activity_log      : Audit Trail (Tracking aktivitas Admin).
 * 15. notifications     : Sistem notifikasi/pemberitahuan pengguna.
 * 
 * FITUR UNGGULAN:
 * - Wali Kelas Sentris: Akses otomatis dibatasi per kelas.
 * - Akuntabilitas     : Monitoring distribusi dokumen secara real-time.
 * - Safety Net        : Soft Deletes (Fitur restore file yang terhapus).
 * - Efisiensi TU      : Bulk upload rapor via ZIP.
 * 
 * DEVELOPER NOTE:
 * Pastikan menjalankan 'php artisan storage:link' untuk akses file.
 * ==============================================================================
 */
