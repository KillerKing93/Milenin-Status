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
function getGeoLocation($ip)
{
    // Gunakan IP-API.com (gratis untuk non-komersial, batasan 45 requests/menit)
    $url = "http://ip-api.com/json/$ip?fields=country,city,regionName";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Timeout 2 detik
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
    $Ping = new MinecraftPing($server_ip_java, $server_port_java, 3); // Timeout 3 detik
    $ping_result = $Ping->Query();
    if ($ping_result && isset($ping_result['latency'])) {
        $ping_java_ms = $ping_result['latency'];
    }
    $Ping->Close();

    // 2. Dapatkan Informasi Detail menggunakan MinecraftQuery
    $Query = new MinecraftQuery();
    $Query->Connect($server_ip_java, $server_port_java, 3); // Timeout 3 detik

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
        // Ambil IP aktual dari domain jika server_ip_java adalah domain
        $actual_server_ip = gethostbyname($server_ip_java);
        if ($actual_server_ip && filter_var($actual_server_ip, FILTER_VALIDATE_IP)) {
            $minecraft_server_geo_location = getGeoLocation($actual_server_ip);
        }
    }

    if ($players) {
        $players_java = $players;
    }
} catch (MinecraftPingException $e) {
    $error_java = 'Server offline atau tidak merespons. (Ping Error: ' . $e->getMessage() . ')';
    $status_java = null;
} catch (MinecraftQueryException $e) {
    $error_java = 'Server online, tapi gagal mendapatkan detail (Query Error: ' . $e->getMessage() . ').';
    if ($ping_java_ms !== 'N/A') {
        $status_java = ['online' => true];
        if (!isset($status_java['Players'])) $status_java['Players'] = 0;
        if (!isset($status_java['MaxPlayers'])) $status_java['MaxPlayers'] = 0;
        if (!isset($status_java['Version'])) $status_java['Version'] = 'Unknown';
    } else {
        $status_java = null;
    }
}


// --- Dapatkan Status Server Bedrock Edition (Basic Check) ---
$bedrock_online = false;
$fp = @fsockopen($server_ip_bedrock, $server_port_bedrock, $errno, $errstr, 2);
if ($fp) {
    $bedrock_online = true;
    fclose($fp);
}


// --- Fungsi untuk menyorot IP dan Port ---
function highlightServerAddress($ip, $port)
{
    return "<span class='server-ip'>$ip</span>:<span class='server-port'>$port</span>";
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Server Milenin Craftthingy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            /* Hijau Minecraft */
            --secondary-color: #388E3C;
            --background-color: #2c2f33;
            /* Dark Grey */
            --card-background: #36393f;
            /* Slightly lighter dark grey */
            --text-color: #ffffff;
            --offline-color: #F44336;
            /* Merah */
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
            max-width: 900px;
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
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.3);
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
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.05);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 0.8;
            }
        }

        h2 {
            font-family: var(--font-pixel);
            color: var(--primary-color);
            margin-top: 0;
            font-size: 1.5em;
            margin-bottom: 20px;
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

        .status-offline,
        .status-error {
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
            background-color: rgba(0, 0, 0, 0.2);
            padding: 10px 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-style: italic;
            color: #ccc;
            white-space: pre-wrap;
            /* Mempertahankan format baris dari MOTD */
            overflow-wrap: break-word;
            font-family: 'Courier New', monospace;
            border: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .players-list {
            background-color: rgba(0, 0, 0, 0.2);
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
            /* Bagi menjadi 2 kolom */
            -webkit-columns: 2;
            -moz-columns: 2;
        }

        .players-list li {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 5px 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .players-list li::before {
            content: "🎮 ";
            /* Icon kecil */
            margin-right: 5px;
            color: var(--primary-color);
        }


        .ip-display {
            font-family: var(--font-pixel);
            font-size: 1.4em;
            color: #FFE0B2;
            /* Orange cerah */
            background-color: rgba(0, 0, 0, 0.3);
            padding: 15px 25px;
            border-radius: 8px;
            margin: 25px auto;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .ip-display:hover {
            background-color: rgba(0, 0, 0, 0.4);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.6);
        }

        .ip-display .server-ip {
            color: var(--primary-color);
        }

        .ip-display .server-port {
            color: #81C784;
            /* Hijau muda */
        }

        .copy-button {
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
            margin-top: 20px;
            letter-spacing: 0.8px;
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
            background-color: rgba(0, 0, 0, 0.2);
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
            }

            h1 {
                font-size: 1.8em;
            }

            h2 {
                font-size: 1.2em;
            }

            .ip-display {
                font-size: 1.1em;
                padding: 12px 20px;
            }

            .copy-button {
                padding: 10px 20px;
                font-size: 1em;
            }

            .players-list ul {
                columns: 1;
                /* Di layar kecil, jadi 1 kolom */
                -webkit-columns: 1;
                -moz-columns: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Status Server Milenin Craftthingy</h1>

        <div class="server-section">
            <h2>Minecraft Java Edition</h2>
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
                <?php if (!empty($status_java['HostName'])): ?>
                    <div class="motd"><?php echo htmlspecialchars($status_java['HostName']); ?></div>
                <?php endif; ?>

                <?php if (!empty($players_java)): ?>
                    <div class="players-list">
                        <h3>Pemain Online:</h3>
                        <ul>
                            <?php foreach ($players_java as $player): ?>
                                <li><?php echo htmlspecialchars($player); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif ($status_java['Players'] > 0): ?>
                    <div class="players-list">
                        <h3>Pemain Online:</h3>
                        <p style="text-align: center; font-style: italic; color: #aaa;">Daftar pemain tidak tersedia (mungkin karena server tidak mengizinkan query daftar pemain).</p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="status-indicator status-offline">Offline</div>
                <div class="info-item">Server sedang tidak tersedia atau ada masalah.</div>
                <?php if ($error_java): ?>
                    <div class="info-item" style="color: #FFEB3B; font-size: 0.9em;">(Detail Error: <?php echo htmlspecialchars($error_java); ?>)</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="ip-display" id="javaIpDisplay" data-ip="<?php echo $server_ip_java . ':' . $server_port_java; ?>">
                <?php echo highlightServerAddress($server_ip_java, $server_port_java); ?>
            </div>
            <button class="copy-button" onclick="copyIp('javaIpDisplay')">Salin IP Java Edition</button>
        </div>

        <hr style="border: 0; height: 1px; background-image: linear-gradient(to right, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0)); margin: 40px auto;">

        <div class="server-section">
            <h2>Minecraft Bedrock Edition</h2>
            <?php if ($bedrock_online): ?>
                <div class="status-indicator status-online">Online</div>
                <div class="info-item">Server Bedrock Edition terdeteksi online.</div>
                <div class="info-item" style="font-size: 0.9em; color: #BBB;">(Pustaka ini tidak menyediakan detail pemain/MOTD untuk Bedrock secara langsung. Cek manual di game.)</div>
            <?php else: ?>
                <div class="status-indicator status-offline">Offline</div>
                <div class="info-item">Server Bedrock Edition sedang tidak tersedia atau ada masalah.</div>
            <?php endif; ?>

            <div class="ip-display" id="bedrockIpDisplay" data-ip="<?php echo $server_ip_bedrock . ':' . $server_port_bedrock; ?>">
                <?php echo highlightServerAddress($server_ip_bedrock, $server_port_bedrock); ?>
            </div>
            <button class="copy-button" onclick="copyIp('bedrockIpDisplay')">Salin IP Bedrock Edition</button>
        </div>

        <div class="server-locations">
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

    <script>
        function copyIp(elementId) {
            const ipAddress = document.getElementById(elementId).getAttribute('data-ip');
            navigator.clipboard.writeText(ipAddress).then(() => {
                alert('IP berhasil disalin: ' + ipAddress);
            }).catch(err => {
                console.error('Gagal menyalin IP:', err);
                alert('Gagal menyalin IP. Silakan salin secara manual: ' + ipAddress);
            });
        }
    </script>
</body>

</html>