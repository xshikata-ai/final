<?php
$list_url = 'https://player.javpornsub.net/keyword/eng.txt';
$sitemap_file = 'eng.xml';

function fetchFromUrl($url, $default = []) {
    $content = @file_get_contents($url);
    if ($content !== false) {
        return array_filter(array_map('trim', explode("\n", $content)), 'strlen');
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($content !== false && $http_code == 200) {
            return array_filter(array_map('trim', explode("\n", $content)), 'strlen');
        }
    }

    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'follow_location' => 1,
        ]
    ];
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    if ($content !== false) {
        return array_filter(array_map('trim', explode("\n", $content)), 'strlen');
    }

    return $default;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_url = $protocol . $domain . ($script_dir === '/' ? '' : $script_dir);

$keywords = fetchFromUrl($list_url, ['xnxx']);

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$now = date('Y-m-d\TH:i:s+07:00'); // ISO 8601 format with WIB offset

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
$xml .= '  <url>' . PHP_EOL;
$xml .= '    <loc>' . htmlspecialchars($base_url . '/index.php') . '</loc>' . PHP_EOL;
$xml .= '    <lastmod>' . $now . '</lastmod>' . PHP_EOL;
$xml .= '    <changefreq>daily</changefreq>' . PHP_EOL;
$xml .= '  </url>' . PHP_EOL;

foreach ($keywords as $keyword) {
    $url = $base_url . '/index.php?id=' . urlencode($keyword);
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . $now . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>daily</changefreq>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
}

$xml .= '</urlset>' . PHP_EOL;

file_put_contents($sitemap_file, $xml);

header('Content-Type: text/plain');
echo "Sitemap generated successfully at $sitemap_file\n";
echo "Total URLs: " . (count($keywords) + 1) . "\n";
echo "Generated at: $now (WIB, Asia/Jakarta)\n";
?>
