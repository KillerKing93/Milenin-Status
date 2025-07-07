---
# Milenin-Status

Ini adalah pemeriksa status server Minecraft yang dirancang untuk memantau status server **Milenin Craftthingy** yang berjalan di `milenin.craftthingy.com`. Alat ini memeriksa apakah server Minecraft **Java Edition** masih aktif di **port 25565** dan server **Bedrock Edition** aktif di **port 19132**.
---

## Fitur

- **Pemeriksaan Status Server Java Edition:** Menampilkan apakah server Java Edition _online_ atau _offline_.
- **Detail Server Java Edition:** Jika _online_, akan menampilkan jumlah pemain yang sedang bermain, versi server, _ping_ (latensi), dan _MOTD_ (Message of the Day).
- **Daftar Pemain Java Edition:** Mencoba mengambil dan menampilkan daftar nama pemain yang saat ini _online_ (tergantung konfigurasi server Minecraft).
- **Pemeriksaan Status Server Bedrock Edition:** Melakukan pemeriksaan dasar untuk melihat apakah server Bedrock Edition _online_ di port 19132.
- **Salin IP:** Tombol praktis untuk menyalin alamat IP server Java dan Bedrock ke _clipboard_ Anda.
- **Informasi Lokasi Server:** Menampilkan lokasi geografis server Minecraft dan lokasi _hosting_ website (Hostinger) Anda untuk referensi.
- **Desain Responsif:** Tampilan yang menarik dan adaptif di berbagai perangkat.

---

## Cara Menggunakan (untuk Pengembang/Pengelola)

Untuk menyiapkan atau memperbarui pemeriksa status ini di lingkungan _hosting_ Anda (misalnya, Hostinger):

1.  **Unduh Pustaka xPaw:**

    - Kunjungi repositori GitHub [xPaw/PHP-Minecraft-Query](https://github.com/xPaw/PHP-Minecraft-Query/).
    - Klik tombol hijau **"Code"**, lalu pilih **"Download ZIP"**.
    - Ekstrak file ZIP yang telah diunduh.

2.  **Pilih File yang Diperlukan:**
    Dari hasil ekstraksi, Anda akan membutuhkan file-file PHP berikut:

    - `MinecraftPing.php`
    - `MinecraftPingException.php`
    - `MinecraftQuery.php`
    - `MinecraftQueryException.php`

3.  **Siapkan File `index.php`:**
    Buat atau perbarui file `index.php` di _root_ direktori _website_ Anda (misalnya, `milenin-status.craftthingy.com`) dengan kode PHP yang disediakan sebelumnya (versi yang mengintegrasikan `MinecraftPing` dan `MinecraftQuery`, serta fitur lokasi server).

4.  **Unggah File ke Server Anda:**

    - Akses File Manager atau cPanel/hPanel di akun _hosting_ Hostinger Anda.
    - Navigasi ke direktori domain tempat Anda ingin meng-_host_ halaman status ini (misalnya, `public_html/milenin-status.craftthingy.com`).
    - Unggah keempat file pustaka xPaw (`MinecraftPing.php`, `MinecraftPingException.php`, `MinecraftQuery.php`, `MinecraftQueryException.php`) dan file `index.php` Anda ke direktori tersebut.

5.  **Konfigurasi Lokasi Website (Opsional):**
    Edit baris `$website_server_location` di dalam file `index.php` untuk mencerminkan lokasi _data center_ Hostinger Anda yang sebenarnya (contoh: 'Indonesia (Jakarta, Singapore/APAC Region)').

6.  **Pastikan Ekstensi `cURL` Aktif:**
    Biasanya, ekstensi PHP `cURL` sudah aktif secara _default_ di _hosting_ Hostinger. Namun, jika ada masalah dalam mendapatkan lokasi server Minecraft, periksa konfigurasi PHP Anda di hPanel dan pastikan `curl` diaktifkan.

Setelah semua file terunggah, buka URL _website_ Anda (misalnya, `http://milenin-status.craftthingy.com`) di _browser_ untuk melihat status server secara _real-time_.

---

## Batasan

- **Geolokasi IP:** Layanan geolokasi IP pihak ketiga (seperti IP-API.com yang digunakan dalam _script_) mungkin memiliki batasan jumlah permintaan per menit. Untuk situs dengan _traffic_ tinggi, pertimbangkan _caching_ hasil atau menggunakan API berbayar.
- **Detail Bedrock Edition:** Pustaka xPaw utamanya dirancang untuk Java Edition. Status Bedrock Edition hanya berupa cek _online/offline_ dasar melalui koneksi _port_. Detail lebih lanjut (pemain, _MOTD_) untuk Bedrock memerlukan pustaka atau API yang berbeda.

---

Semoga ini membantu para pemain dan komunitas Milenin Craftthingy untuk selalu tahu status server favorit mereka!
