<?php

// --- Konfigurasi Server ---
$server_ip = 'milenin.craftthingy.com';
$server_port_java = 25565;
$server_port_bedrock = 19132;

// --- Konfigurasi Cache ---
$cache_file_java = 'minecraft_status_java_cache.json';
$cache_file_bedrock = 'minecraft_status_bedrock_cache.json';
$cache_time = 60; // Cache 1 menit

// --- Fungsi Helper ---
function get_api_data($url, $cache_file, $cache_time)
{
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        return json_decode(file_get_contents($cache_file), true);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MileninStatusPage/3.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    $data = json_decode($response, true);
    return json_last_error() === JSON_ERROR_NONE ? $data : null;
}

function get_ping($host, $port = 25565, $timeout = 2)
{
    $startTime = microtime(true);
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) return 'N/A';
    $endTime = microtime(true);
    fclose($socket);
    return round(($endTime - $startTime) * 1000);
}

// --- Ambil Data ---
$java_url = "https://api.mcsrvstat.us/3/{$server_ip}:{$server_port_java}";
$bedrock_url = "https://api.mcsrvstat.us/bedrock/3/{$server_ip}:{$server_port_bedrock}";

$java_data = get_api_data($java_url, $cache_file_java, $cache_time);
$bedrock_data = get_api_data($bedrock_url, $cache_file_bedrock, $cache_time);

// --- Proses Data untuk Tampilan ---
$api_error_java = is_null($java_data);
$api_error_bedrock = is_null($bedrock_data);

$is_java_online = !$api_error_java && ($java_data['online'] ?? false);
$is_bedrock_online = !$api_error_bedrock && ($bedrock_data['online'] ?? false);

$main_data = $is_java_online ? $java_data : ($is_bedrock_online ? $bedrock_data : null);
$api_error_main = is_null($main_data);

$server_icon = $java_data['icon'] ?? ($bedrock_data['icon'] ?? null);

$motd = !$api_error_main ? htmlspecialchars(implode("\n", $main_data['motd']['clean'] ?? ['Tidak Diketahui'])) : 'Gagal memuat MOTD.';
$players_online = !$api_error_main ? ($main_data['players']['online'] ?? 0) : 0;
$players_max = !$api_error_main ? ($main_data['players']['max'] ?? 0) : 0;
$ping = ($is_java_online || $is_bedrock_online) ? get_ping($server_ip, $server_port_java) : 'N/A';

$java_version = !$api_error_java ? htmlspecialchars($java_data['version'] ?? 'N/A') : 'N/A';
$java_software = !$api_error_java ? htmlspecialchars($java_data['software'] ?? 'N/A') : 'N/A';
$java_protocol = !$api_error_java ? htmlspecialchars($java_data['protocol']['version'] ?? 'N/A') : 'N/A';

$bedrock_version = !$api_error_bedrock ? htmlspecialchars($bedrock_data['version'] ?? 'N/A') : 'N/A';
$bedrock_software = !$api_error_bedrock ? htmlspecialchars($bedrock_data['software'] ?? 'N/A') : 'N/A';
$bedrock_protocol = !$api_error_bedrock ? htmlspecialchars($bedrock_data['protocol']['version'] ?? 'N/A') : 'N/A';
$bedrock_gamemode = !$api_error_bedrock ? htmlspecialchars($bedrock_data['gamemode'] ?? 'N/A') : 'N/A';
$bedrock_serverid = !$api_error_bedrock ? htmlspecialchars($bedrock_data['serverid'] ?? 'N/A') : 'N/A';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Server Milenin Craftthingy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --bg-gradient-start: #1a1a2e;
            --bg-gradient-end: #16213e;
            --card-color: #2c2f33;
            --card-border: rgba(255, 255, 255, 0.08);
            --text-light: #ffffff;
            --text-muted: #a7a9be;
            --text-dark: #121212;
            --primary-green: #4CAF50;
            --offline-red: #F44336;
            --error-yellow: #FFC107;
            --accent-yellow: #FFEB3B;
            --font-pixel: 'Press Start 2P', cursive;
            --font-main: 'Montserrat', sans-serif;
            --shadow: rgba(0, 0, 0, 0.4);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            color: var(--text-light);
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
            background: var(--card-color);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            box-shadow: 0 8px 16px var(--shadow);
        }

        .server-icon {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            image-rendering: pixelated;
            background-color: rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .header-main {
            flex-grow: 1;
        }

        .header-main h1 {
            font-family: var(--font-pixel);
            color: var(--primary-green);
            font-size: 1.8em;
            margin: 0 0 10px 0;
            text-shadow: 2px 2px 4px var(--shadow);
        }

        .header-main .ip-copy {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-block;
        }

        .header-main .ip-copy:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .header-ping {
            text-align: right;
            margin-left: auto;
        }

        .header-ping .ping-label {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .header-ping .ping-value {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-green);
        }

        .header-ping .ping-value .unit {
            font-size: 0.6em;
            color: var(--text-muted);
        }

        .header-details {
            width: 100%;
            display: flex;
            justify-content: space-between;
            /* Kunci perubahan tata letak */
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            /* Jarak jika wrap ke baris baru */
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--card-border);
            color: var(--text-muted);
            font-size: 0.9em;
        }

        .header-details strong {
            color: var(--text-light);
        }

        .main-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .card {
            background: var(--card-color);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow);
        }

        .card-header {
            font-size: 1.5em;
            font-weight: 700;
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .card-header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            text-transform: uppercase;
        }

        .online {
            background-color: var(--primary-green);
            color: var(--text-dark);
        }

        .offline {
            background-color: var(--offline-red);
            color: var(--text-light);
        }

        .error {
            background-color: var(--error-yellow);
            color: var(--text-dark);
        }

        .info-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .info-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--card-border);
            word-break: break-all;
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-list .label {
            color: var(--text-muted);
            padding-right: 10px;
        }

        .info-list .value {
            font-weight: 600;
            text-align: right;
        }

        .motd-card pre {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: monospace;
            color: var(--accent-yellow);
            text-shadow: 1px 1px 2px var(--shadow);
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: var(--text-muted);
            font-size: 0.9em;
        }

        .swal2-popup {
            background: var(--card-color) !important;
            color: var(--text-light) !important;
        }

        .swal2-title {
            color: var(--primary-green) !important;
        }

        .swal2-success-circular-line-left,
        .swal2-success-circular-line-right,
        .swal2-success-fix {
            background-color: var(--card-color) !important;
        }

        .swal2-success-line-tip,
        .swal2-success-line-long {
            background-color: var(--primary-green) !important;
        }

        @media (max-width: 768px) {
            .header {
                text-align: center;
            }

            .server-icon,
            .header-main,
            .header-ping {
                width: 100%;
                margin: 0 auto;
                text-align: center;
            }

            .header-main {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <?php if ($server_icon): ?>
                <img src="<?= htmlspecialchars($server_icon) ?>" alt="Ikon Server" class="server-icon">
            <?php else: ?>
                <div class="server-icon"></div>
            <?php endif; ?>
            <div class="header-main">
                <h1>Milenin Craftthingy</h1>
                <div class="ip-copy" onclick="copyToClipboard('<?= $server_ip ?>')">
                    <i class="fas fa-copy"></i> <?= $server_ip ?>
                </div>
            </div>
            <div class="header-ping">
                <div class="ping-label">Ping</div>
                <div class="ping-value"><?= $ping ?><span class="unit"> ms</span></div>
            </div>
            <div class="header-details">
                <?php if (!$api_error_bedrock && $is_bedrock_online && $bedrock_serverid != 'N/A'): ?>
                    <div class="server-id">Server ID: <strong><?= $bedrock_serverid ?></strong></div>
                <?php endif; ?>
                <?php if (!$api_error_main): ?>
                    <div class="player-count">Pemain: <strong><?= $players_online ?> / <?= $players_max ?></strong></div>
                <?php endif; ?>
            </div>
        </header>

        <main class="main-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fab fa-java"></i> Java Edition</div>
                </div>
                <?php if ($api_error_java): ?>
                    <p><span class="status-indicator error" style="margin-left: 0;">Error:</span> Gagal memuat data dari API.</p>
                <?php else: ?>
                    <ul class="info-list">
                        <li><span class="label">Status</span> <span class="status-indicator <?= $is_java_online ? 'online' : 'offline' ?>"><?= $is_java_online ? 'Online' : 'Offline' ?></span></li>
                        <li><span class="label">Versi</span> <span class="value"><?= $java_version ?></span></li>
                        <li><span class="label">Port</span> <span class="value"><?= $server_port_java ?></span></li>
                        <li><span class="label">Software</span> <span class="value"><?= $java_software ?></span></li>
                        <li><span class="label">Protokol</span> <span class="value"><?= $java_protocol ?></span></li>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-header-title"><i class="fas fa-mobile-alt"></i> Bedrock Edition</div>
                </div>
                <?php if ($api_error_bedrock): ?>
                    <p><span class="status-indicator error" style="margin-left: 0;">Error:</span> Gagal memuat data dari API.</p>
                <?php else: ?>
                    <ul class="info-list">
                        <li><span class="label">Status</span><span class="status-indicator <?= $is_bedrock_online ? 'online' : 'offline' ?>"><?= $is_bedrock_online ? 'Online' : 'Offline' ?></span></li>
                        <li><span class="label">Versi</span> <span class="value"><?= $bedrock_version ?></span></li>
                        <li><span class="label">Port</span> <span class="value"><?= $server_port_bedrock ?></span></li>
                        <li><span class="label">Software</span> <span class="value"><?= $bedrock_software ?></span></li>
                        <li><span class="label">Protokol</span> <span class="value"><?= $bedrock_protocol ?></span></li>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card full-width">
                <h2 class="card-header"><i class="fas fa-globe"></i> Info Umum</h2>
                <?php if ($api_error_main): ?>
                    <p>Gagal memuat data umum karena kedua API tidak merespons.</p>
                <?php else: ?>
                    <ul class="info-list">
                        <li>
                            <span class="label">MOTD</span>
                            <div class="motd-card">
                                <pre><?= $motd ?></pre>
                            </div>
                        </li>
                        <?php if (!$api_error_bedrock && $is_bedrock_online): ?>
                            <li><span class="label">Gamemode</span> <span class="value"><?= $bedrock_gamemode ?></span></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            Dibuat dengan ❤️ untuk Milenin Craftthingy
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disalin!',
                        text: 'Alamat IP server: ' + text,
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }).catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyalin',
                        text: 'Tidak dapat menyalin. Periksa izin clipboard di browser Anda.'
                    });
                });
            } else {
                let textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.top = "-999999px";
                textArea.style.left = "-999999px";

                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    document.execCommand('copy');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disalin!',
                        text: 'Alamat IP server: ' + text,
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyalin',
                        text: 'Browser Anda tidak mendukung fitur ini.'
                    });
                } finally {
                    document.body.removeChild(textArea);
                }
            }
        }
    </script>
</body>

</html>