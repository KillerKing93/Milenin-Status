<?php
// PHP Logic for Minecraft Server Status and Geo-location
// Last Updated: 08-07-2025

// --- Konfigurasi Server Minecraft Anda ---
$server_ip_java = 'milenin.craftthingy.com'; // Alamat IP/Domain Server Java Edition
$server_port_java = 25565; // Port Java Edition

$server_ip_bedrock = 'milenin.craftthingy.com'; // Alamat IP/Domain Server Bedrock Edition
$server_port_bedrock = 19132; // Port Bedrock Edition

// --- Konfigurasi Server Website Anda (Hostinger) ---
// Ganti ini dengan lokasi pusat data Hostinger yang Anda gunakan
$website_server_location = 'Indonesia (Jakarta, Singapore/APAC Region)'; // Contoh, sesuaikan dengan lokasi Hostinger Anda

// --- Variabel untuk Status ---
$status_java = null;
$error_java = null;
$players_java = [];
$ping_java_ms = 'N/A'; // Ping dari API
$minecraft_server_geo_location = 'Tidak diketahui'; // Lokasi server Minecraft

// --- Konfigurasi Cache ---
$cache_file = 'minecraft_status_cache.json';
$cache_time = 60 * 6; // Cache selama 6 menit (360 detik). API mcsrvstat.us cache 5 menit.


// --- Fungsi untuk mendapatkan geolokasi dari IP ---
function getGeoLocation($ip) {
    // Menggunakan API IP-API.com (gratis untuk non-komersial, batasan 45 requests/menit)
    // User-Agent yang deskriptif dan non-empty
    $url = "http://ip-api.com/json/$ip?fields=country,city,regionName";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout 2 detik untuk API call geolokasi
    curl_setopt($ch, CURLOPT_USERAGENT, 'MileninCraftthingyStatusWebsite-GeoLocation/1.0 (Contact: admin@craftthingy.com)');
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data && ($data['status'] ?? null) === 'success') {
        $location = [];
        if (!empty($data['city'])) {
            $location[] = $data['city'];
        }
        if (!empty($data['regionName'])) {
            $location[] = $data['regionName'];
        }
        if (!empty($data['country'])) {
            $location[] = $data['country'];
        }
        return implode(', ', $location);
    }
    return 'Tidak diketahui';
}

// --- Fungsi untuk mendapatkan status server dari mcsrvstat.us API (Updated for v3 & User-Agent) ---
function getMinecraftStatusFromAPI($domain, $port, $isBedrock = false) {
    // Menggunakan API v3
    $endpoint = $isBedrock ? "bedrock/3" : "3";
    $apiUrl = "https://api.mcsrvstat.us/{$endpoint}/{$domain}:{$port}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8); // Timeout lebih panjang (8 detik)
    // User-Agent yang lebih deskriptif dan non-empty sesuai dokumentasi API
    curl_setopt($ch, CURLOPT_USERAGENT, 'MileninCraftthingyStatusWebsite-APIClient/1.0 (Contact: admin@craftthingy.com)'); 
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 403) {
        return ['error' => 'API returned 403 Forbidden. Check your User-Agent and rate limits.', 'http_code' => 403];
    }
    if ($httpCode !== 200 || $curlError) {
        return ['error' => 'API call failed: ' . ($curlError ? $curlError : 'HTTP Status ' . $httpCode), 'http_code' => $httpCode];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Failed to parse API response: ' . json_last_error_msg(), 'http_code' => 200]; // Parse error, but received 200
    }

    return $data;
}


// --- Dapatkan Status Server Java Edition (dengan Cache) ---
$cache_valid = false;
$cached_data = [];
$cache_last_updated_time = time(); // Default ke waktu saat ini jika file cache belum ada

if (file_exists($cache_file) && is_readable($cache_file)) {
    $cache_last_updated_time = filemtime($cache_file);
    if ((time() - $cache_last_updated_time) < $cache_time) {
        $cached_data_json = file_get_contents($cache_file);
        $cached_data = json_decode($cached_data_json, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($cached_data)) {
            $cache_valid = true;
        }
    }
}

if ($cache_valid) {
    $api_response_java = $cached_data;
    // Pesan error diisi oleh API jika ada, atau di sini untuk indikator cache
    $error_java = '(Data dari cache. Terakhir diperbarui ' . date('H:i:s, d-m-Y', $cache_last_updated_time) . ' WIB)';
} else {
    // Jika cache tidak valid, panggil API
    $api_response_java = getMinecraftStatusFromAPI($server_ip_java, $server_port_java);

    // Simpan ke cache jika berhasil dan tidak ada error
    if (!isset($api_response_java['error'])) {
        file_put_contents($cache_file, json_encode($api_response_java));
        $cache_last_updated_time = time(); // Perbarui waktu cache
    }
}

// Proses respons API (dari cache atau API langsung)
if (isset($api_response_java['online']) && $api_response_java['online'] === true) {
    $status_java = $api_response_java;
    
    // Ambil ping dari debug info jika tersedia, jika tidak, gunakan latency
    $ping_java_ms = $api_response_java['latency'] ?? ($api_response_java['debug']['ping'] ? ($api_response_java['latency'] ?? 'N/A') : 'N/A');

    // Coba dapatkan lokasi dari IP server utama
    if (isset($api_response_java['ip']) && filter_var($api_response_java['ip'], FILTER_VALIDATE_IP)) {
        $minecraft_server_geo_location = getGeoLocation($api_response_java['ip']);
    } elseif (isset($api_response_java['dns']['ip']) && filter_var($api_response_java['dns']['ip'], FILTER_VALIDATE_IP)) {
         $minecraft_server_geo_location = getGeoLocation($api_response_java['dns']['ip']);
    }

    // Pemain: Perhatikan struktur v3 players.list[i].name
    if (isset($api_response_java['players']['list']) && is_array($api_response_java['players']['list'])) {
        foreach ($api_response_java['players']['list'] as $player) {
            $players_java[] = $player['name'] ?? 'Unknown Player';
        }
    }
} else {
    if (isset($api_response_java['error'])) {
        $error_java = 'Gagal mendapatkan status dari API. (Error: ' . htmlspecialchars($api_response_java['error']) . ')';
    } else {
        $error_java = 'Server Java offline atau tidak merespons.';
    }
    $status_java = null;
}


// --- Dapatkan Status Server Bedrock Edition dari API ---
$api_response_bedrock = getMinecraftStatusFromAPI($server_ip_bedrock, $server_port_bedrock, true); // Parameter true untuk Bedrock API
$bedrock_online = false;
$bedrock_error = null;

if (isset($api_response_bedrock['error'])) {
    $bedrock_error = 'Gagal mendapatkan status Bedrock dari API. (Error: ' . htmlspecialchars($api_response_bedrock['error']) . ')';
} elseif (isset($api_response_bedrock['online']) && $api_response_bedrock['online'] === true) {
    $bedrock_online = true;
    // Jika perlu detail Bedrock lain dari API, tambahkan di sini dari $api_response_bedrock
} else {
    $bedrock_error = 'Server Bedrock offline atau tidak merespons.';
}


// --- Fungsi untuk menyorot IP utama (tanpa port) ---
function highlightServerAddress($ip) {
    return "<span class='server-ip'>$ip</span>";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Server Milenin Craftthingy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #4CAF50; /* Hijau Minecraft */
            --secondary-color: #388E3C;
            --background-color: #2c2f33; /* Dark Grey */
            --card-background: #36393f; /* Slightly lighter dark grey */
            --text-color: #ffffff;
            --offline-color: #F44336; /* Merah */
            --shadow-color: rgba(0, 0, 0, 0.4);
            --border-radius: 12px;
            --font-pixel: 'Press Start 2P', cursive;
            --font-general: 'Montserrat', sans-serif;
        }

        body {
            font-family: var(--font-general);
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
        }

        .container {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px var(--shadow-color);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            font-family: var(--font-pixel);
            color: var(--primary-color);
            margin-bottom: 30px; /* Jarak bawah default untuk h1 */
            font-size: 2.2em;
            text-shadow: 2px 2px 4px var(--shadow-color);
        }

        /* Styling untuk bagian Salin IP Utama */
        .main-ip-copy-section {
            background-color: var(--background-color);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px; /* Jarak di bawah block ini */
            box-shadow: inset 0 0 10px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .main-ip-copy-section h3 {
            font-family: var(--font-pixel);
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2em;
        }

        .main-ip-display {
            font-family: var(--font-pixel);
            font-size: 1.1em;
            color: #FFE0B2;
            background-color: rgba(0,0,0,0.3);
            padding: 12px 20px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 15px; /* Jarak dari tombol */
        }
        .main-ip-display .server-ip {
            color: var(--primary-color);
        }

        .copy-link-button {
            background-color: var(--primary-color);
            color: var(--background-color);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.5);
        }

        .copy-link-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.7);
        }

        .copy-link-button:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.5);
        }


        .server-section { /* Ini adalah blok status server utama */
            background-color: var(--background-color);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .server-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(76, 175, 80, 0.1) 0%, transparent 70%);
            animation: pulse 10s infinite alternate;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }

        h2 {
            font-family: var(--font-pixel);
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .server-type-status {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Agar responsif */
        }

        .type-box {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px 20px;
            border-radius: 10px;
            flex: 1;
            min-width: 200px; /* Minimum width untuk setiap box */
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; /* Untuk menempatkan konten di dalam box */
            flex-direction: column;
        }

        .type-box h3 {
            font-family: var(--font-general);
            font-size: 1.2em;
            color: #FFEB3B;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .status-indicator {
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9em;
        }

        .status-online {
            background-color: var(--primary-color);
            color: var(--background-color);
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.6);
        }

        .status-offline {
            background-color: var(--offline-color);
            color: var(--text-color);
            box-shadow: 0 0 15px rgba(244, 67, 54, 0.6);
        }

        .info-item {
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .info-item strong {
            color: var(--primary-color);
            margin-right: 5px;
        }

        .motd {
            background-color: rgba(0,0,0,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-style: italic;
            color: #ccc;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            font-family: 'Courier New', monospace;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .players-list {
            background-color: rgba(0,0,0,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .players-list h3 {
            font-family: var(--font-pixel);
            font-size: 1em;
            color: #FFEB3B;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .players-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
            columns: 2;
            -webkit-columns: 2;
            -moz-columns: 2;
        }

        .players-list li {
            background-color: rgba(255,255,255,0.05);
            padding: 5px 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .players-list li::before {
            content: "🎮 ";
            margin-right: 5px;
            color: var(--primary-color);
        }

        .footer {
            margin-top: 40px;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.6);
        }

        .server-locations {
            margin-top: 20px;
            background-color: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: var(--border-radius);
            font-size: 0.95em;
            color: #E0E0E0;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }
        .server-locations strong {
            color: #FFEB3B;
        }

        /* SweetAlert2 Dark Mode Custom Styles */
        .swal2-dark-mode {
            background-color: var(--card-background) !important;
            color: var(--text-color) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .swal2-title {
            color: var(--primary-color) !important;
            font-family: var(--font-pixel) !important;
            font-size: 1.3em !important;
        }
        .swal2-html-container {
            color: var(--text-color) !important;
            font-family: var(--font-general) !important;
        }
        .swal2-icon.swal2-success [class^=swal2-success-line][class$=long] {
            background-color: var(--primary-color) !important;
        }
        .swal2-icon.swal2-success [class^=swal2-success-line][class$=tip] {
            background-color: var(--primary-color) !important;
        }
        .swal2-confirm.swal2-styled {
            background-color: var(--primary-color) !important;
            color: var(--background-color) !important;
            font-weight: bold !important;
        }
        .swal2-progress-bar {
            background: var(--primary-color) !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 25px;
                max-width: 100%;
            }
            h1 {
                font-size: 1.8em;
            }
            h2 {
                font-size: 1.2em;
            }
            .server-type-status {
                flex-direction: column;
                gap: 15px;
            }
            .type-box {
                min-width: unset;
                width: 100%;
            }
            .main-ip-display {
                font-size: 0.9em;
                padding: 10px 15px;
            }
            .copy-link-button {
                padding: 10px 20px;
                font-size: 1em;
            }
            .players-list ul {
                columns: 1;
            }
        }
    </style>

</head>
<body>
    <div class="container">
        <h1>Status Server Milenin Craftthingy</h1>

        <div class="main-ip-copy-section">
            <h3>Gabung Server</h3>
            <span class="main-ip-display" id="mainIpDisplay">
                <span class="server-ip"><?php echo $server_ip_java; ?></span>
            </span>
            <button class="copy-link-button" onclick="copyMainLink()">Salin Link Server</button>
        </div>

        <hr style="border: 0; height: 1px; background-image: linear-gradient(to right, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0)); margin: 40px auto;">


        <div class="server-section">
            <h2>Status Server</h2>

            <div class="server-type-status">
                <div class="type-box">
                    <h3>Minecraft Java Edition</h3>
                    <?php if ($status_java && ($status_java['online'] ?? false)): ?>
                        <div class="status-indicator status-online">Online</div>
                        <div class="info-item">
                            <strong>Pemain:</strong> <?php echo $status_java['players']['online'] ?? '0'; ?> / <?php echo $status_java['players']['max'] ?? '0'; ?>
                        </div>
                        <div class="info-item">
                            <strong>Versi:</strong> <?php echo htmlspecialchars($status_java['version'] ?? 'N/A'); ?>
                        </div>
                        <div class="info-item">
                            <strong>Ping:</strong> <?php echo $ping_java_ms; ?>ms
                        </div>
                        <div class="info-item">
                            <strong>Port:</strong> <?php echo $server_port_java; ?>
                        </div>
                        <?php if (!empty($status_java['motd']['clean'])): ?>
                            <div class="motd"><?php echo htmlspecialchars($status_java['motd']['clean']); ?></div>
                        <?php elseif (!empty($status_java['motd']['html'])): ?>
                            <div class="motd"><?php echo htmlspecialchars($status_java['motd']['html']); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="status-indicator status-offline">Offline</div>
                        <div class="info-item">Server sedang tidak tersedia.</div>
                        <div class="info-item">
                            <strong>Port:</strong> <?php echo $server_port_java; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="type-box">
                    <h3>Minecraft Bedrock Edition</h3>
                    <?php if ($bedrock_online): ?>
                        <div class="status-indicator status-online">Online</div>
                        <div class="info-item">Server Bedrock Edition terdeteksi online.</div>
                        <div class="info-item" style="font-size: 0.9em; color: #BBB;">(Tidak ada detail pemain/MOTD dari cek dasar.)</div>
                        <div class="info-item">
                            <strong>Port:</strong> <?php echo $server_port_bedrock; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-indicator status-offline">Offline</div>
                        <div class="info-item">Server sedang tidak tersedia.</div>
                        <div class="info-item">
                            <strong>Port:</strong> <?php echo $server_port_bedrock; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($bedrock_error): ?>
                        <div class="info-item" style="color: #FFEB3B; font-size: 0.9em; margin-top: 15px;">
                            **Pesan Error Bedrock Edition:** <?php echo htmlspecialchars($bedrock_error); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div> <?php if ($error_java): ?>
                <div class="info-item" style="color: #FFEB3B; font-size: 0.9em; margin-top: 15px;">
                    **Pesan Error Java Edition:** <?php echo htmlspecialchars($error_java); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($players_java)): ?>
                <div class="players-list">
                    <h3>Pemain Online Java Edition:</h3>
                    <ul>
                        <?php foreach ($players_java as $player): ?>
                            <li><?php echo htmlspecialchars($player); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif ($status_java && ($status_java['players']['online'] ?? 0) > 0 && empty($players_java)): ?>
                <div class="players-list">
                    <h3>Pemain Online Java Edition:</h3>
                    <p style="text-align: center; font-style: italic; color: #aaa;">Daftar pemain tidak tersedia (mungkin karena server tidak mengizinkan query daftar pemain).</p>
                </div>
            <?php endif; ?>

        </div> <div class="server-locations">
            <h3>Lokasi Server:</h3>
            <p>
                <strong>Server Minecraft (Java Edition):</strong> <?php echo $minecraft_server_geo_location; ?><br>
                <strong>Server Website (Hostinger):</strong> <?php echo $website_server_location; ?>
            </p>
        </div>

        <div class="footer">
            Dibuat dengan ❤️ untuk Milenin Craftthingy. | Terakhir diperbarui: <?php echo date('H:i:s, d-m-Y', $cache_last_updated_time); ?> WIB
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyMainLink() {
            const mainLink = '<?php echo $server_ip_java; ?>'; // Hanya IP/Domain utama, tanpa port

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(mainLink).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disalin!',
                        html: 'Link Server: <strong>' + mainLink + '</strong> telah disalin ke clipboard Anda.<br>Gunakan port <strong><?php echo $server_port_java; ?></strong> untuk Java dan <strong><?php echo $server_port_bedrock; ?></strong> untuk Bedrock.',
                        showConfirmButton: false,
                        timer: 5000, // Durasi lebih lama karena ada info port
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                }).catch(err => {
                    console.error('Gagal menyalin link:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyalin!',
                        text: 'Silakan salin link secara manual: ' + mainLink,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                });
            } else {
                // Fallback untuk browser lama atau jika clipboard API tidak tersedia
                const textArea = document.createElement("textarea");
                textArea.value = mainLink;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disalin!',
                        html: 'Link Server: <strong>' + mainLink + '</strong> telah disalin ke clipboard Anda.<br>Gunakan port <strong><?php echo $server_port_java; ?></strong> untuk Java dan <strong><?php echo $server_port_bedrock; ?></strong> untuk Bedrock.',
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                } catch (err) {
                    console.error('Gagal menyalin link (fallback):', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyalin!',
                        text: 'Silakan salin link secara manual: ' + mainLink,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                }
                document.body.removeChild(textArea);
            }
        }
    </script>

</body>
</html>
