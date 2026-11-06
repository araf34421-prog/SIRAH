# SIRAH

SIRAH (Sistem Informasi Rencana Hafalan) membantu pengelolaan program hafalan santri di Surau Tahfizh Firdaus, termasuk target, setoran, pemantauan, dan laporan.

## Struktur proyek

```text
SIRAH/
|-- index.php                 # Router utama
|-- semua_kode.txt            # Dump teks kode proyek
|-- config/database.php       # Koneksi database
|-- assets/
|   |-- css/style.css         # Satu stylesheet untuk seluruh tampilan
|   `-- js/
|       `-- app.js            # JavaScript seluruh halaman
|-- includes/
|   |-- auth.php               # Helper sesi dan autentikasi
|   |-- footer.php             # Script bersama dan penutup layout
|   |-- functions.php          # Helper izin, filter, dan notifikasi
|   |-- head_assets.php        # Aset browser bersama
|   |-- page_data.php          # Data server dalam JSON
|   |-- header.php             # Metadata dan pembuka layout
|   |-- init_rbac.php          # Inisialisasi RBAC dan menu
|   `-- sidebar.php            # Navigasi bersama
|-- api/                       # Proses backend dan endpoint aksi
|-- pages/                     # Tampilan yang dikelompokkan per fitur
`-- .htaccess               # Meneruskan URL lama ke router/API
```

### Rute halaman

Akses halaman melalui `index.php?page=nama_rute`. `.htaccess` meneruskan tautan, bookmark, dan form lama ke router atau API sehingga file PHP penghubung di root tidak diperlukan. Apache perlu mengaktifkan `mod_rewrite` dan mengizinkan `AllowOverride All`; konfigurasi XAMPP lokal sudah memenuhi keduanya.

### Gaya tampilan

Semua CSS khusus SIRAH berada di `assets/css/style.css`. Ubah file ini untuk menyesuaikan desain seluruh halaman. Tailwind masih digunakan untuk kelas utilitas pada markup.

### Menambahkan halaman

1. Buat tampilan di `pages/<fitur>/`.
2. Tambahkan nama rutenya ke daftar `$routes` di `index.php`.
3. Gunakan `includes/header.php`, `includes/sidebar.php` bila perlu, dan `includes/footer.php`.
4. Letakkan aksi backend baru di folder fitur yang sesuai di `api/`.