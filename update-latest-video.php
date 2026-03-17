<?php
/**
 * Automatic Video Update Script for FikFak News
 * This script checks YouTube Data API (instant) and RSS feed,
 * then updates latest-video.json only.
 * Social metadata is rendered dynamically in index.php.
 * Run via cron every 10-20 seconds on Sunday mornings around 8am.
 */

$channelId = 'UCkhqAfTIr2U5RU9ziyGfu_A';
$jsonFile = __DIR__ . '/latest-video.json';

// YouTube Data API Key (fallback to RSS when empty or failing)
$youtubeApiKey = 'AIzaSyChLF03TKUUlVZ9PdUNJ7upIswryBIyEc8';

function getLatestVideoFromAPI($channelId, $apiKey) {
    if (empty($apiKey)) {
        return null;
    }

    $url = "https://www.googleapis.com/youtube/v3/search?key={$apiKey}&channelId={$channelId}&part=snippet&order=date&maxResults=10&type=video";
    $json = @file_get_contents($url);

    if ($json === false) {
        error_log('Failed to fetch from YouTube API');
        return null;
    }

    $data = json_decode($json, true);
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        error_log('No videos found in API response');
        return null;
    }

    $videos = [];
    foreach ($data['items'] as $item) {
        if (!isset($item['id']['videoId'], $item['snippet']['title'], $item['snippet']['publishedAt'])) {
            continue;
        }

        $videos[] = [
            'videoId' => (string) $item['id']['videoId'],
            'title' => (string) $item['snippet']['title'],
            'published' => (string) $item['snippet']['publishedAt'],
        ];
    }

    if (empty($videos)) {
        error_log('No valid video items in API response');
        return null;
    }

    return [
        'videoId' => $videos[0]['videoId'],
        'title' => $videos[0]['title'],
        'published' => $videos[0]['published'],
        'recentVideos' => array_slice($videos, 1),
        'lastUpdated' => date('c'),
    ];
}

function getLatestVideoFromRSS($channelId) {
    $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}";
    $xml = @file_get_contents($rssUrl);

    if ($xml === false) {
        error_log('Failed to fetch RSS feed');
        return null;
    }

    $feed = @simplexml_load_string($xml);
    if ($feed === false) {
        error_log('Failed to parse RSS feed');
        return null;
    }

    $feed->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
    $feed->registerXPathNamespace('yt', 'http://www.youtube.com/xml/schemas/2015');

    $entries = $feed->xpath('//atom:entry');
    if (empty($entries)) {
        error_log('No entries found in RSS feed');
        return null;
    }

    $videos = [];
    foreach ($entries as $entry) {
        $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $entry->registerXPathNamespace('yt', 'http://www.youtube.com/xml/schemas/2015');

        $videoIdNode = $entry->xpath('yt:videoId');
        $titleNode = $entry->xpath('atom:title');
        $publishedNode = $entry->xpath('atom:published');

        if (empty($videoIdNode) || empty($titleNode) || empty($publishedNode)) {
            continue;
        }

        $videos[] = [
            'videoId' => (string) $videoIdNode[0],
            'title' => (string) $titleNode[0],
            'published' => (string) $publishedNode[0],
        ];
    }

    if (empty($videos)) {
        error_log('No valid video items in RSS feed');
        return null;
    }

    return [
        'videoId' => $videos[0]['videoId'],
        'title' => $videos[0]['title'],
        'published' => $videos[0]['published'],
        'recentVideos' => array_slice($videos, 1),
        'lastUpdated' => date('c'),
    ];
}

$currentData = null;
if (file_exists($jsonFile)) {
    $decodedCurrent = json_decode((string) file_get_contents($jsonFile), true);
    if (is_array($decodedCurrent)) {
        $currentData = $decodedCurrent;
    }
}

$latestVideo = null;
if (!empty($youtubeApiKey)) {
    echo "Checking YouTube Data API (instant)...\n";
    $latestVideo = getLatestVideoFromAPI($channelId, $youtubeApiKey);

    if ($latestVideo) {
        echo "✅ Got video from API: {$latestVideo['videoId']}\n";
    } else {
        echo "⚠️ API call failed or returned no data. Falling back to RSS...\n";
    }
}

if ($latestVideo === null) {
    echo "Checking RSS feed (5-15 min delay)...\n";
    $latestVideo = getLatestVideoFromRSS($channelId);
}

if ($latestVideo === null) {
    error_log('Could not fetch latest video from any source');
    exit(1);
}

$needsUpdate = false;
$updateReason = '';

if ($currentData === null) {
    $needsUpdate = true;
    $updateReason = 'Initial creation';
} elseif (($currentData['videoId'] ?? '') !== $latestVideo['videoId']) {
    $needsUpdate = true;
    $updateReason = 'New video detected';
} elseif (!isset($currentData['recentVideos']) || !is_array($currentData['recentVideos']) || empty($currentData['recentVideos'])) {
    $needsUpdate = true;
    $updateReason = 'Adding recentVideos to cache';
} elseif (count($currentData['recentVideos']) !== count($latestVideo['recentVideos'])) {
    $needsUpdate = true;
    $updateReason = 'Video count changed';
}

if ($needsUpdate) {
    file_put_contents($jsonFile, json_encode($latestVideo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    error_log("✅ Updated JSON: {$updateReason} - {$latestVideo['videoId']} - {$latestVideo['title']}");
    echo "SUCCESS: Updated JSON ({$updateReason}) - video {$latestVideo['videoId']}\n";
} else {
    echo "INFO: No change, still on video {$latestVideo['videoId']}\n";
}
