-----

# Milenin-Status

Ini adalah pemeriksa status server Minecraft yang dirancang untuk memantau status server **Milenin Craftthingy** yang berjalan di `milenin.craftthingy.com`. Alat ini menampilkan apakah server Minecraft **Java Edition** aktif di **port 25565** dan server **Bedrock Edition** aktif di **port 19132**.

Anda dapat melihat status server secara langsung di:
**[milenin-status.craftthingy.com](https://www.google.com/url?sa=E&source=gmail&q=http://milenin-status.craftthingy.com)**

-----

## Fitur

  * **Pemeriksaan Status Komprehensif (Java Edition):** Menampilkan apakah server Java Edition *online* atau *offline*, lengkap dengan jumlah pemain yang sedang bermain, versi server, *ping* (latensi), *MOTD* (Message of the Day), dan informasi port yang digunakan. Data ini diambil dengan andal melalui API `api.mcsrvstat.us`.
  * **Pemeriksaan Status Dasar (Bedrock Edition):** Menampilkan apakah server Bedrock Edition *online* atau *offline* di port 19132, juga didapatkan melalui API `api.mcsrvstat.us`.
  * **Salin Link Server Utama:** Tombol praktis di bagian atas halaman untuk menyalin langsung alamat domain server (`milenin.craftthingy.com`). Notifikasi SweetAlert2 yang interaktif akan muncul setelah disalin, menginformasikan port Java dan Bedrock yang relevan.
  * **Informasi Lokasi Server:** Menampilkan lokasi geografis server Minecraft (didapat secara dinamis) dan lokasi *hosting* website Anda (Hostinger, dikonfigurasi secara manual untuk akurasi) untuk referensi pemain.
  * **Sistem Cache PHP:** Menggunakan sistem *caching* sederhana berbasis file untuk mengurangi jumlah panggilan ke API eksternal, membantu menghindari masalah *rate limit* dan mempercepat waktu muat halaman.
  * **Desain Modern & Responsif:** Tampilan yang bersih, terinspirasi Minecraft/Discord, adaptif di berbagai ukuran layar dan perangkat.
  * **Penanganan Error API:** Memberikan pesan *error* yang informatif jika terjadi masalah saat berkomunikasi dengan API status (misalnya, `403 Forbidden`).

-----

## Cara Menggunakan (untuk Pengembang/Pengelola)

Untuk menyiapkan atau memperbarui pemeriksa status ini di lingkungan *hosting* Anda (misalnya, Hostinger):

1.  **Integrasi SweetAlert2:**
    Pastikan *link* CDN untuk SweetAlert2 (CSS dan JavaScript) ditambahkan ke file `index.php` Anda seperti yang ditunjukkan di bawah ini. Ini sangat penting untuk fungsionalitas notifikasi yang modern.

    ```html
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    ```

2.  **Siapkan File `index.php`:**
    Gunakan kode PHP lengkap yang telah disediakan. Simpan file ini sebagai `index.php`. Kode ini mengandalkan `cURL` untuk komunikasi API.

3.  **Unggah File ke Server Anda:**

      * Akses File Manager atau cPanel/hPanel di akun *hosting* Hostinger Anda.
      * Navigasi ke direktori domain tempat Anda ingin meng-*host* halaman status ini (misalnya, `public_html/milenin-status.craftthingy.com`).
      * Unggah file `index.php` Anda ke direktori tersebut.

4.  **Konfigurasi Lokasi Website:**
    Edit baris `$website_server_location` di awal file `index.php` untuk mencerminkan lokasi *data center* Hostinger Anda yang sebenarnya (contoh: `'Indonesia (Jakarta, Singapore/APAC Region)'`). Informasi ini ditulis secara manual karena PHP tidak dapat mendeteksinya secara otomatis dari lingkungan *shared hosting*.

5.  **Manajemen Cache:**

      * Script ini akan secara otomatis membuat file `minecraft_status_cache.json` untuk menyimpan hasil API. Pastikan server web memiliki izin tulis ke direktori tempat `index.php` berada.
      * **Saat *debugging* atau jika Anda ingin memaksa pembaruan data:** Hapus file `minecraft_status_cache.json` secara manual melalui File Manager Hostinger Anda. Ini akan memaksa *script* untuk melakukan panggilan API baru.

6.  **Pastikan Ekstensi `cURL` Aktif:**
    Ekstensi PHP `cURL` harus aktif di *hosting* Anda karena *script* ini menggunakannya untuk melakukan panggilan ke API eksternal. Biasanya, ini sudah aktif secara *default* di Hostinger. Jika ada masalah, periksa konfigurasi PHP Anda di hPanel dan pastikan `curl` diaktifkan.

Setelah semua file terunggah, buka URL *website* Anda (misalnya, `http://milenin-status.craftthingy.com`) di *browser* untuk melihat status server secara *real-time*.

-----

## Batasan & Pemecahan Masalah

  * **"Error: API call failed: HTTP Status 403"**:

      * Ini adalah masalah yang paling umum dan berarti `api.mcsrvstat.us` menolak permintaan dari server *hosting* Anda.
      * **Penyebab Paling Mungkin:** Batas jumlah permintaan (*rate limit*) API yang tercapai dari alamat IP Hostinger Anda (yang mungkin dibagikan banyak pengguna), atau IP Hostinger Anda masuk daftar hitam sementara.
      * **Solusi:**
        1.  **Tunggu:** Jika Anda mendapatkan 403, tunggu setidaknya 15-30 menit (atau lebih) sebelum mencoba lagi.
        2.  **Hapus Cache:** Pastikan Anda menghapus `minecraft_status_cache.json` setiap kali Anda ingin *debug* atau mendapatkan data terbaru.
        3.  **Hubungi Dukungan Hostinger:** Tanyakan apakah ada masalah dengan reputasi IP server Anda atau pemblokiran keluar untuk koneksi API.
        4.  **Pertimbangkan Alternatif API:** Jika masalah 403 terus-menerus terjadi, Anda mungkin perlu mengganti API status Minecraft lain (misalnya, MineTools API atau Minecraft-API.com).

  * **Detail Bedrock Edition:** Fungsionalitas status Bedrock Edition melalui `api.mcsrvstat.us` mungkin tidak menyediakan detail sebanyak Java Edition (misalnya, daftar pemain atau MOTD). Jika detail tersebut penting, Anda mungkin perlu mencari API atau pustaka khusus Bedrock lainnya.

  * **Geolokasi IP:** Layanan geolokasi IP pihak ketiga (IP-API.com) memiliki batasan penggunaan gratis. Untuk *traffic* yang sangat tinggi, ini bisa menjadi perhatian.

-----

Semoga ini membantu para pemain dan komunitas Milenin Craftthingy untuk selalu tahu status server favorit mereka dengan tampilan yang indah dan informasi yang lengkap\!

-----