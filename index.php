<?php
// Pastikan semua file ini sudah diunggah ke direktori yang sama di server Hostinger Anda:
// MinecraftPing.php, MinecraftPingException.php, MinecraftQuery.php, MinecraftQueryException.php
require __DIR__ . '/MinecraftPing.php';
require __DIR__ . '/MinecraftPingException.php';
require __DIR__ . '/MinecraftQuery.php';
require __DIR__ . '/MinecraftQueryException.php';

use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;
use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;

// --- Konfigurasi Server Minecraft Anda ---
$server_ip_java = 'milenin.craftthingy.com'; // Alamat IP/Domain Server Java Edition
$server_port_java = 25565; // Port Java Edition (biasanya sama untuk game dan query)

$server_ip_bedrock = 'milenin.craftthingy.com'; // Alamat IP/Domain Server Bedrock Edition
$server_port_bedrock = 19132; // Port Bedrock Edition

// --- Konfigurasi Server Website Anda (Hostinger) ---
// Ganti ini dengan lokasi pusat data Hostinger yang Anda gunakan
$website_server_location = 'Indonesia (Jakarta, Singapore/APAC Region)'; // Contoh, sesuaikan dengan lokasi Hostinger Anda

$status_java = null;
$error_java = null;
$players_java = []; // Untuk menyimpan daftar pemain
$ping_java_ms = 'N/A'; // Untuk menyimpan nilai ping
$minecraft_server_geo_location = 'Tidak diketahui'; // Untuk lokasi server Minecraft

// --- Fungsi untuk mendapatkan geolokasi dari IP ---
function getGeoLocation($ip) {
    $url = "http://ip-api.com/json/$ip?fields=country,city,regionName";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data && $data['status'] === 'success') {
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


// --- Dapatkan Status Server Java Edition ---
try {
    // 1. Dapatkan Ping/Latency menggunakan MinecraftPing
    $Ping = new MinecraftPing($server_ip_java, $server_port_java, 5); // Tingkatkan timeout
    $ping_result = $Ping->Query();
    if ($ping_result && isset($ping_result['latency'])) {
        $ping_java_ms = $ping_result['latency'];
    }
    $Ping->Close();

    // 2. Dapatkan Informasi Detail menggunakan MinecraftQuery
    $Query = new MinecraftQuery();
    $Query->Connect($server_ip_java, $server_port_java, 5); // Tingkatkan timeout

    $info = $Query->GetInfo();
    $players = $Query->GetPlayers();

    if ($info) {
        $status_java = $info;
        $status_java['online'] = true;
        $status_java['players']['online'] = $info['Players'];
        $status_java['players']['max'] = $info['MaxPlayers'];
        $status_java['version']['name'] = $info['Version'];
        if (isset($info['HostName'])) {
            $status_java['description']['text'] = $info['HostName'];
        }

        // Dapatkan geolokasi server Minecraft
        $actual_server_ip = gethostbyname($server_ip_java);
        if ($actual_server_ip && filter_var($actual_server_ip, FILTER_VALIDATE_IP)) {
            $minecraft_server_geo_location = getGeoLocation($actual_server_ip);
        }

    }

    if ($players) {
        $players_java = $players;
    }

} catch (MinecraftPingException $e) {
    $error_java = 'Server Java offline atau tidak merespons ping. (Error: ' . htmlspecialchars($e->getMessage()) . ')';
    $status_java = null;
} catch (MinecraftQueryException $e) {
    // Query gagal, tapi ping mungkin berhasil
    $error_java = 'Server Java online, tapi gagal mendapatkan detail lengkap. Pastikan `enable-query=true` di server.properties dan port UDP 25565 (atau port query spesifik Anda) terbuka di firewall. (Error: ' . htmlspecialchars($e->getMessage()) . ')';
    if ($ping_java_ms !== 'N/A') {
        $status_java = ['online' => true, 'Players' => 0, 'MaxPlayers' => 0, 'Version' => 'Tidak Diketahui'];
    } else {
        $status_java = null; // Jika ping juga gagal, maka server memang offline
    }
}


// --- Dapatkan Status Server Bedrock Edition (Basic Check) ---
$bedrock_online = false;
$fp = @fsockopen($server_ip_bedrock, $server_port_bedrock, $errno, $errstr, 2);
if ($fp) {
    $bedrock_online = true;
    fclose($fp);
}

// --- Fungsi untuk menyorot IP dan Port (hanya menampilkan) ---
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
            margin-bottom: 30px;
            font-size: 2.2em;
            text-shadow: 2px 2px 4px var(--shadow-color);
        }

        .server-section {
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
            font-size: 1.5em;
            margin-bottom: 20px;
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
            display: flex; /* Untuk menempatkan IP dan tombol di bawah info */
            flex-direction: column;
            justify-content: space-between; /* Untuk mendorong IP ke bawah */
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

        .ip-copy-group {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* PERUBAHAN UKURAN FONT IP ADDRESS (lebih kecil lagi) */
        .ip-display {
            font-family: var(--font-pixel);
            font-size: 0.85em; /* Lebih kecil */
            color: #FFE0B2;
            background-color: rgba(0,0,0,0.3);
            padding: 8px 12px;
            border-radius: 8px;
            display: block; /* Pastikan selalu di baris baru */
            margin: 0 auto 10px auto;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .ip-display:hover {
            background-color: rgba(0,0,0,0.4);
            box-shadow: 0 3px 12px rgba(0,0,0,0.6);
        }
        .ip-display .server-ip {
            color: var(--primary-color);
        }
        /* Style untuk port di baris baru */
        .server-port-text {
            font-family: var(--font-pixel);
            font-size: 0.8em; /* Ukuran port bisa sedikit lebih kecil */
            color: #81C784;
            display: block; /* Pastikan di baris baru */
            margin-top: 5px; /* Sedikit jarak dari IP */
        }


        .copy-button {
            background-color: var(--primary-color);
            color: var(--background-color);
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.5);
            position: relative;
            z-index: 10;
        }

        .copy-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.7);
        }

        .copy-button:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.5);
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
            .ip-display {
                font-size: 0.8em;
                padding: 8px 12px;
                margin: 0 auto 8px auto;
            }
            .server-port-text {
                font-size: 0.75em;
            }
            .copy-button {
                padding: 8px 15px;
                font-size: 0.85em;
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

        <div class="server-section">
            <h2>Status Server</h2>

            <div class="server-type-status">
                <div class="type-box">
                    <h3>Minecraft Java Edition</h3>
                    <?php if ($status_java): ?>
                        <div class="status-indicator status-online">Online</div>
                        <div class="info-item">
                            <strong>Pemain:</strong> <?php echo $status_java['Players']; ?> / <?php echo $status_java['MaxPlayers']; ?>
                        </div>
                        <div class="info-item">
                            <strong>Versi:</strong> <?php echo htmlspecialchars($status_java['Version']); ?>
                        </div>
                        <div class="info-item">
                            <strong>Ping:</strong> <?php echo $ping_java_ms; ?>ms
                        </div>
                        <div class="info-item">
                            <strong>Port:</strong> <?php echo $server_port_java; ?>
                        </div>
                        <?php if (!empty($status_java['HostName'])): ?>
                            <div class="motd"><?php echo htmlspecialchars($status_java['HostName']); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="status-indicator status-offline">Offline</div>
                        <div class="info-item">Server sedang tidak tersedia.</div>
                        <div class="info-item">
                            <strong>Port:</strong> <?php echo $server_port_java; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ip-copy-group">
                        <span class="ip-display" id="javaIpDisplay" data-ip="<?php echo $server_ip_java; ?>">
                            <?php echo highlightServerAddress($server_ip_java); ?>
                        </span>
                        <span class="server-port-text">:<?php echo $server_port_java; ?></span>
                        <button class="copy-button" onclick="copyIp('javaIpDisplay')">Salin IP Java</button>
                    </div>
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

                    <div class="ip-copy-group">
                        <span class="ip-display" id="bedrockIpDisplay" data-ip="<?php echo $server_ip_bedrock; ?>">
                            <?php echo highlightServerAddress($server_ip_bedrock); ?>
                        </span>
                        <span class="server-port-text">:<?php echo $server_port_bedrock; ?></span>
                        <button class="copy-button" onclick="copyIp('bedrockIpDisplay')">Salin IP Bedrock</button>
                    </div>
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
            <?php elseif ($status_java && $status_java['Players'] > 0 && empty($players_java)): ?>
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
            Dibuat dengan ❤️ untuk Milenin Craftthingy. | Terakhir diperbarui: <?php echo date('H:i:s, d-m-Y'); ?> WIB
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyIp(elementId) {
            const ipAddress = document.getElementById(elementId).getAttribute('data-ip');
            // Gabungkan IP dan port untuk disalin
            let fullIpToCopy;
            if (elementId === 'javaIpDisplay') {
                fullIpToCopy = ipAddress + ':<?php echo $server_port_java; ?>';
            } else if (elementId === 'bedrockIpDisplay') {
                fullIpToCopy = ipAddress + ':<?php echo $server_port_bedrock; ?>';
            } else {
                fullIpToCopy = ipAddress; // Fallback
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(fullIpToCopy).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disalin!',
                        text: 'IP Server: ' + fullIpToCopy + ' telah disalin ke clipboard Anda.',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                }).catch(err => {
                    console.error('Gagal menyalin IP:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyalin!',
                        text: 'Silakan salin IP secara manual: ' + fullIpToCopy,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                });
            } else {
                // Fallback untuk browser lama atau jika clipboard API tidak tersedia
                const textArea = document.createElement("textarea");
                textArea.value = fullIpToCopy;
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
                        text: 'IP Server: ' + fullIpToCopy + ' telah disalin ke clipboard Anda.',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                } catch (err) {
                    console.error('Gagal menyalin IP (fallback):', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyalin!',
                        text: 'Silakan salin IP secara manual: ' + fullIpToCopy,
                        customClass: {
                            popup: 'swal2-dark-mode'
                        }
                    });
                }
                document.body.removeChild(textArea);
            }
        }
    </script>
    <style>
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
    </style>
</body>
</html>