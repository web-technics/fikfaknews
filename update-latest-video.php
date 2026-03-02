<?php
/**
 * Automatic Video Update Script for FikFak News
 * This script checks YouTube Data API (instant) and RSS feed
 * Run via cron every 10-20 seconds on Sunday mornings around 8am
 */

// Configuration
$channelId = 'UCkhqAfTIr2U5RU9ziyGfu_A';
$jsonFile = __DIR__ . '/latest-video.json';

// YouTube Data API Key (get from https://console.cloud.google.com)
// Without this, falls back to RSS feed (5-15 min delay)
$youtubeApiKey = 'AIzaSyChLF03TKUUlVZ9PdUNJ7upIswryBIyEc8'; // ADD YOUR API KEY HERE for instant updates!

// Function to get latest video via YouTube Data API (INSTANT - no delay!)
function getLatestVideoFromAPI($channelId, $apiKey) {
    if (empty($apiKey)) {
        return null;
    }
    
    // Fetch multiple videos (10) for sidebar
    $url = "https://www.googleapis.com/youtube/v3/search?key={$apiKey}&channelId={$channelId}&part=snippet&order=date&maxResults=10&type=video";
    
    $json = @file_get_contents($url);
    if ($json === false) {
        error_log("Failed to fetch from YouTube API");
        return null;
    }
    
    $data = json_decode($json, true);
    if (!isset($data['items'][0])) {
        error_log("No videos found in API response");
        return null;
    }
    
    // Return array of videos
    $videos = [];
    foreach ($data['items'] as $item) {
        $videos[] = [
            'videoId' => $item['id']['videoId'],
            'title' => $item['snippet']['title'],
            'published' => $item['snippet']['publishedAt']
        ];
    }
    
    return [
        'videoId' => $videos[0]['videoId'],
        'title' => $videos[0]['title'],
        'published' => $videos[0]['published'],
        'recentVideos' => array_slice($videos, 1), // Skip first video (already in player)
        'lastUpdated' => date('c')
    ];
}

// Function to fetch and parse RSS feed (fallback, 5-15 min delay)
function getLatestVideoFromRSS($channelId) {
    $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}";
    $xml = @file_get_contents($rssUrl);
    if ($xml === false) {
        error_log("Failed to fetch RSS feed");
        return null;
    }
    
    $feed = @simplexml_load_string($xml);
    if ($feed === false) {
        error_log("Failed to parse RSS feed");
        return null;
    }
    
    // Register namespace
    $feed->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
    $feed->registerXPathNamespace('yt', 'http://www.youtube.com/xml/schemas/2015');
    
    // Get all entries (typically 15)
    $entries = $feed->xpath('//atom:entry');
    if (empty($entries)) {
        error_log("No entries found in RSS feed");
        return null;
    }
    
    // Parse multiple videos for sidebar
    $videos = [];
    foreach ($entries as $entry) {
        $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $entry->registerXPathNamespace('yt', 'http://www.youtube.com/xml/schemas/2015');
        
        $videoId = (string)$entry->xpath('yt:videoId')[0];
        $title = (string)$entry->xpath('atom:title')[0];
        $published = (string)$entry->xpath('atom:published')[0];
        
        $videos[] = [
            'videoId' => $videoId,
            'title' => $title,
            'published' => $published
        ];
    }
    
    return [
        'videoId' => $videos[0]['videoId'],
        'title' => $videos[0]['title'],
        'published' => $videos[0]['published'],
        'recentVideos' => array_slice($videos, 1), // Skip first video (already in player)
        'lastUpdated' => date('c')
    ];
}

// Read current JSON file
$currentData = null;
if (file_exists($jsonFile)) {
    $currentData = json_decode(file_get_contents($jsonFile), true);
}

// Try YouTube Data API first (instant!)
$latestVideo = null;
if (!empty($youtubeApiKey)) {
    echo "Checking YouTube Data API (instant)...\n";
    $latestVideo = getLatestVideoFromAPI($channelId, $youtubeApiKey);
    if ($latestVideo) {
        echo "✅ Got video from API: {$latestVideo['videoId']}\n";
    } else {
        echo "⚠️  API call failed or returned no data. Checking error...\n";
        // Try to get more details
        $testUrl = "https://www.googleapis.com/youtube/v3/search?key={$youtubeApiKey}&channelId={$channelId}&part=snippet&order=date&maxResults=1&type=video";
        $response = @file_get_contents($testUrl);
        if ($response === false) {
            echo "❌ API request failed - check network/firewall\n";
        } else {
            $data = json_decode($response, true);
            if (isset($data['error'])) {
                echo "❌ API Error: {$data['error']['message']}\n";
                echo "   Code: {$data['error']['code']}\n";
            } else {
                echo "📋 API Response: " . substr($response, 0, 200) . "...\n";
            }
        }
    }
}

// Fallback to RSS if API not available or failed
if ($latestVideo === null) {
    echo "Checking RSS feed (5-15 min delay)...\n";
    $latestVideo = getLatestVideoFromRSS($channelId);
}

if ($latestVideo === null) {
    error_log("Could not fetch latest video from any source");
    exit(1);
}

// Check if video ID has changed or if we need to update recentVideos
$needsUpdate = false;
$updateReason = '';

if ($currentData === null) {
    $needsUpdate = true;
    $updateReason = 'Initial creation';
} elseif ($currentData['videoId'] !== $latestVideo['videoId']) {
    $needsUpdate = true;
    $updateReason = 'New video detected';
} elseif (!isset($currentData['recentVideos']) || empty($currentData['recentVideos'])) {
    $needsUpdate = true;
    $updateReason = 'Adding recentVideos to cache';
} elseif (count($currentData['recentVideos']) !== count($latestVideo['recentVideos'])) {
    $needsUpdate = true;
    $updateReason = 'Video count changed';
}

if ($needsUpdate) {
    // New video detected or cache needs update - update JSON file
    file_put_contents($jsonFile, json_encode($latestVideo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log("✅ Updated JSON: {$updateReason} - {$latestVideo['videoId']} - {$latestVideo['title']}");
    
    // Also update the meta tags in index.html if video changed
    if ($currentData === null || $currentData['videoId'] !== $latestVideo['videoId']) {
        updateIndexHtmlMetaTags($latestVideo['videoId'], $latestVideo['title']);
    }
    
    echo "SUCCESS: Updated JSON ($updateReason) - video {$latestVideo['videoId']}\n";
} else {
    echo "INFO: No change, still on video {$latestVideo['videoId']}\n";
}

// Function to update meta tags in index.html
function updateIndexHtmlMetaTags($videoId, $title) {
    $indexFile = __DIR__ . '/index.html';
    if (!file_exists($indexFile)) {
        error_log("index.html not found");
        return;
    }
    
    $html = file_get_contents($indexFile);
    
    // Update og:image meta tags
    $html = preg_replace(
        '/<meta property="og:image" content="https:\/\/img\.youtube\.com\/vi\/[^\/]+\/hqdefault\.jpg"/',
        '<meta property="og:image" content="https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg"',
        $html
    );
    
    $html = preg_replace(
        '/<meta property="og:image:secure_url" content="https:\/\/img\.youtube\.com\/vi\/[^\/]+\/hqdefault\.jpg"/',
        '<meta property="og:image:secure_url" content="https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg"',
        $html
    );
    
    // Update og:video
    $html = preg_replace(
        '/<meta property="og:video" content="https:\/\/www\.youtube\.com\/embed\/[^"]+"/',
        '<meta property="og:video" content="https://www.youtube.com/embed/' . $videoId . '"',
        $html
    );
    
    // Update twitter:image
    $html = preg_replace(
        '/<meta name="twitter:image" content="https:\/\/img\.youtube\.com\/vi\/[^\/]+\/hqdefault\.jpg"/',
        '<meta name="twitter:image" content="https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg"',
        $html
    );
    
    // Update twitter:player
    $html = preg_replace(
        '/<meta name="twitter:player" content="https:\/\/www\.youtube\.com\/embed\/[^"]+"/',
        '<meta name="twitter:player" content="https://www.youtube.com/embed/' . $videoId . '"',
        $html
    );
    
    // Update JavaScript LATEST_VIDEO_ID
    $html = preg_replace(
        '/const LATEST_VIDEO_ID = \'[^\']+\';/',
        "const LATEST_VIDEO_ID = '{$videoId}';",
        $html
    );
    
    file_put_contents($indexFile, $html);
    error_log("✅ Updated index.html meta tags for video: {$videoId}");
}

?>
