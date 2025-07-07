---

# Milenin-Status

Ini adalah pemeriksa status server Minecraft yang dirancang untuk memantau status server **Milenin Craftthingy** yang berjalan di `milenin.craftthingy.com`. Alat ini memeriksa apakah server Minecraft **Java Edition** aktif di **port 25565** dan server **Bedrock Edition** aktif di **port 19132**.

Anda dapat melihat status server secara langsung di:
**[milenin-status.craftthingy.com](https://www.google.com/url?sa=E&source=gmail&q=http://milenin-status.craftthingy.com)**

---

## Fitur

- **Pemeriksaan Status Server Java Edition:** Menampilkan apakah server Java Edition _online_ atau _offline_, lengkap dengan jumlah pemain, versi server, _ping_ (latensi), _MOTD_ (Message of the Day), dan informasi port yang digunakan.
- **Pemeriksaan Status Server Bedrock Edition:** Melakukan pemeriksaan dasar untuk melihat apakah server Bedrock Edition _online_ di port 19132, dengan informasi port yang digunakan.
- **Salin Link Server Utama:** Tombol praktis di bagian atas halaman untuk menyalin langsung alamat domain server (`milenin.craftthingy.com`). Notifikasi SweetAlert akan muncul setelah disalin, menginformasikan port Java dan Bedrock.
- **Informasi Lokasi Server:** Menampilkan lokasi geografis server Minecraft dan lokasi _hosting_ website (Hostinger) Anda untuk referensi.
- **Desain Responsif:** Tampilan yang menarik dan adaptif di berbagai perangkat.
- **Notifikasi Interaktif:** Menggunakan SweetAlert2 untuk notifikasi yang lebih menarik saat menyalin IP.

---

## Cara Menggunakan (untuk Pengembang/Pengelola)

Untuk menyiapkan atau memperbarui pemeriksa status ini di lingkungan _hosting_ Anda (misalnya, Hostinger):

1.  **Integrasi SweetAlert2:**
    Pastikan _link_ CDN untuk SweetAlert2 (CSS dan JavaScript) ditambahkan ke file `index.php` Anda seperti yang ditunjukkan di bawah ini. Ini sangat penting untuk fungsionalitas notifikasi yang modern.

    ```html
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"
    />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    ```

2.  **Siapkan File `index.php`:**
    Buat atau perbarui file `index.php` di _root_ direktori _website_ Anda (misalnya, `milenin-status.craftthingy.com`) dengan kode PHP yang telah disediakan. Kode ini kini menggunakan API pihak ketiga (`https://api.mcsrvstat.us/`) untuk mendapatkan status server Java Edition.

3.  **Unggah File ke Server Anda:**

    - Akses File Manager atau cPanel/hPanel di akun _hosting_ Hostinger Anda.
    - Navigasi ke direktori domain tempat Anda ingin meng-_host_ halaman status ini (misalnya, `public_html/milenin-status.craftthingy.com`).
    - Unggah file `index.php` Anda ke direktori tersebut. (Pustaka xPaw tidak lagi diperlukan karena kita beralih ke API.)

4.  **Konfigurasi Lokasi Website (Opsional):**
    Edit baris `$website_server_location` di dalam file `index.php` untuk mencerminkan lokasi _data center_ Hostinger Anda yang sebenarnya (contoh: 'Indonesia (Jakarta, Singapore/APAC Region)').

5.  **Pastikan Ekstensi `cURL` Aktif:**
    Ekstensi PHP `cURL` harus aktif di _hosting_ Anda karena _script_ ini menggunakannya untuk melakukan panggilan ke API eksternal. Biasanya, ini sudah aktif secara _default_ di Hostinger. Jika ada masalah, periksa konfigurasi PHP Anda di hPanel dan pastikan `curl` diaktifkan.

Setelah semua file terunggah, buka URL _website_ Anda (misalnya, `http://milenin-status.craftthingy.com`) di _browser_ untuk melihat status server secara _real-time_.

---

## Batasan

- **Ketergantungan API Pihak Ketiga:** Fungsionalitas status server Java Edition sangat bergantung pada ketersediaan dan batasan penggunaan API `api.mcsrvstat.us`. Jika API tersebut mengalami masalah atau Anda melebihi batasan permintaan mereka, status mungkin tidak terdeteksi.
- **Detail Bedrock Edition:** Status Bedrock Edition masih berupa cek _online/offline_ dasar melalui koneksi _port_. Detail lebih lanjut (pemain, _MOTD_) untuk Bedrock memerlukan pustaka atau API yang berbeda.
- **Geolokasi IP:** Layanan geolokasi IP pihak ketiga (seperti IP-API.com yang digunakan dalam _script_) mungkin memiliki batasan jumlah permintaan per menit. Untuk situs dengan _traffic_ tinggi, pertimbangkan _caching_ hasil atau menggunakan API berbayar.

---

Semoga ini membantu para pemain dan komunitas Milenin Craftthingy untuk selalu tahu status server favorit mereka\!
