<?php
session_start();

// Dynamic Meta Tags for Latest Video
$latestVideoFile = __DIR__ . '/latest-video.json';
$videoData = [
    'videoId' => '_zmNgTiLmWo',
    'title' => 'FikFak News Uitzending',
    'published' => '2026-01-05T00:00:00+00:00'
];
$recentVideos = [];
$channelId = 'UCkhqAfTIr2U5RU9ziyGfu_A';
$maxVideoCacheAgeSeconds = 6 * 3600;

function getLatestVideosFromYouTubeRss($channelId, $maxVideos = 10)
{
  $rssUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode((string) $channelId);
  $xml = @file_get_contents($rssUrl);
  if ($xml === false) {
    return null;
  }

  $feed = @simplexml_load_string($xml);
  if ($feed === false) {
    return null;
  }

  $feed->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
  $feed->registerXPathNamespace('yt', 'http://www.youtube.com/xml/schemas/2015');

  $entries = $feed->xpath('//atom:entry');
  if (!is_array($entries) || empty($entries)) {
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

    if (count($videos) >= $maxVideos) {
      break;
    }
  }

  if (empty($videos)) {
    return null;
  }

  return [
    'videoId' => $videos[0]['videoId'],
    'title' => $videos[0]['title'],
    'published' => $videos[0]['published'],
    'recentVideos' => array_slice($videos, 1),
    'lastUpdated' => date('c')
  ];
}

$decoded = null;
$cacheIsStale = true;

if (file_exists($latestVideoFile)) {
    $json = @file_get_contents($latestVideoFile);
    if ($json) {
    $decoded = @json_decode($json, true);
        if ($decoded && isset($decoded['videoId'])) {
            $videoData = [
                'videoId' => $decoded['videoId'],
                'title' => isset($decoded['title']) ? $decoded['title'] : 'FikFak News Uitzending',
                'published' => isset($decoded['published']) ? $decoded['published'] : date('c')
            ];

      if (!empty($decoded['lastUpdated'])) {
        $lastUpdated = strtotime((string) $decoded['lastUpdated']);
        if ($lastUpdated !== false) {
          $cacheIsStale = (time() - $lastUpdated) > $maxVideoCacheAgeSeconds;
        }
      }

      if (!empty($decoded['recentVideos']) && is_array($decoded['recentVideos'])) {
        $recentVideos = $decoded['recentVideos'];
      }
        }
    }
}

if (!$decoded || !isset($decoded['videoId']) || $cacheIsStale) {
  $rssData = getLatestVideosFromYouTubeRss($channelId);
  if (is_array($rssData) && isset($rssData['videoId'])) {
    $videoData = [
      'videoId' => (string) $rssData['videoId'],
      'title' => isset($rssData['title']) ? (string) $rssData['title'] : 'FikFak News Uitzending',
      'published' => isset($rssData['published']) ? (string) $rssData['published'] : date('c')
    ];
    $recentVideos = !empty($rssData['recentVideos']) && is_array($rssData['recentVideos']) ? $rssData['recentVideos'] : [];

    @file_put_contents($latestVideoFile, json_encode($rssData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }
}

$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$requestHostRaw = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
$requestHostSafe = preg_match('/^[A-Za-z0-9.-]+$/', $requestHostRaw) ? strtolower($requestHostRaw) : '';
$baseUrl = $requestHostSafe !== '' ? ($requestScheme . '://' . $requestHostSafe . '/') : 'https://go.fikfak.news/';
$requestedVideoId = isset($_GET['v']) ? trim((string) $_GET['v']) : '';
$selectedVideo = $videoData;

if ($requestedVideoId !== '' && preg_match('/^[A-Za-z0-9_-]{11}$/', $requestedVideoId)) {
  if ($requestedVideoId === (string) $videoData['videoId']) {
    $selectedVideo = $videoData;
  } else {
    foreach ($recentVideos as $recentVideo) {
      if (!is_array($recentVideo) || !isset($recentVideo['videoId'])) {
        continue;
      }

      if ((string) $recentVideo['videoId'] === $requestedVideoId) {
        $selectedVideo = [
          'videoId' => $requestedVideoId,
          'title' => isset($recentVideo['title']) ? (string) $recentVideo['title'] : 'FikFak News Uitzending',
          'published' => isset($recentVideo['published']) ? (string) $recentVideo['published'] : date('c')
        ];
        break;
      }
    }

    if ((string) $selectedVideo['videoId'] !== $requestedVideoId) {
      $selectedVideo = [
        'videoId' => $requestedVideoId,
        'title' => 'FikFak News Uitzending',
        'published' => date('c')
      ];
    }
  }
}

$publishedTimestamp = strtotime($selectedVideo['published']);
if ($publishedTimestamp === false) {
  $publishedTimestamp = time();
}
$publishedIso = date('c', $publishedTimestamp);
$selectedVideoId = (string) $selectedVideo['videoId'];
$selectedTitle = trim((string) $selectedVideo['title']) !== '' ? (string) $selectedVideo['title'] : 'FikFak News Uitzending';
$accountUsername = trim((string) ($_SESSION['username'] ?? ''));
$isLoggedIn = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
$pageTitle = '📰 ' . $selectedTitle . ' | FikFak News';
$pageDescription = 'Bekijk de nieuwste FikFak News uitzending met onafhankelijke analyse en een kritische blik op media en politiek.';
$shareUrl = $baseUrl . '?v=' . rawurlencode($selectedVideoId);
$canonicalUrl = $shareUrl;
$thumbnailUrl = 'https://i.ytimg.com/vi/' . rawurlencode($selectedVideoId) . '/hqdefault.jpg';
$thumbnailShareUrl = $thumbnailUrl;
$embedUrl = 'https://www.youtube.com/embed/' . rawurlencode($selectedVideoId);
$watchUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($selectedVideoId);

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($requestedVideoId === '' && $requestMethod === 'GET') {
  header('Location: ' . $shareUrl, true, 302);
  exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  
  <!-- Google Analytics 4 -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-Z9XCT4V4RG"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-Z9XCT4V4RG', {
      'anonymize_ip': true,
      'cookie_flags': 'SameSite=None;Secure'
    });
  </script>
  
  <!-- Security Meta Tags -->
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
  <meta http-equiv="X-Content-Type-Options" content="nosniff">
  
  <!-- Primary Meta Tags -->
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="keywords" content="FikFak News, fikfakmaster, Dirk Theuns, alternatief nieuws, onafhankelijk nieuws, nieuws analyse, Nederlandse media, actueel, politiek, nieuwsuitzending, fikfak.news, beste journalist 2025, kritisch nieuws, media-analyse, onafhankelijke journalistiek" />
  <meta name="author" content="Dirk Theuns (@fikfakmaster)" />
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
  <meta name="language" content="Dutch" />
  <meta name="revisit-after" content="2 days" />
  
  <!-- Open Graph / Facebook / WhatsApp -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="<?php echo htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:site_name" content="FikFak News" />
  <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:image" content="<?php echo htmlspecialchars($thumbnailShareUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($thumbnailShareUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:image:type" content="image/jpeg" />
  <meta property="og:image:width" content="480" />
  <meta property="og:image:height" content="360" />
  <meta property="og:image:alt" content="<?php echo htmlspecialchars($selectedTitle . ' - FikFak News', ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:locale" content="nl_NL" />
  <meta property="og:locale:alternate" content="nl_BE" />
  <meta property="og:video" content="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:video:url" content="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:video:secure_url" content="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:video:type" content="text/html" />
  <meta property="og:video:width" content="1280" />
  <meta property="og:video:height" content="720" />
  <meta property="og:video:tag" content="FikFak News" />
  <meta property="og:video:tag" content="Nieuws" />
  <meta property="og:video:tag" content="Journalistiek" />
  <meta property="article:publisher" content="https://www.fikfak.news/" />
  <meta property="article:author" content="https://www.dirktheuns.be/" />
  <meta property="article:section" content="Nieuws" />
  <meta property="article:tag" content="Nieuws" />
  <meta property="article:tag" content="Politiek" />
  <meta property="article:tag" content="Media" />
  <meta property="article:published_time" content="<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="article:modified_time" content="<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:video:release_date" content="<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>" />
  
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:site" content="@dirktheuns" />
  <meta name="twitter:creator" content="@dirktheuns" />
  <meta name="twitter:domain" content="<?php echo htmlspecialchars($requestHostSafe !== '' ? $requestHostSafe : 'go.fikfak.news', ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:url" content="<?php echo htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:image" content="<?php echo htmlspecialchars($thumbnailShareUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:image:src" content="<?php echo htmlspecialchars($thumbnailShareUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($selectedTitle . ' - FikFak News', ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:player" content="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta name="twitter:player:width" content="1280" />
  <meta name="twitter:player:height" content="720" />
  
  <!-- WhatsApp Specific Optimization -->
  <meta property="og:updated_time" content="<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>" />
  <meta property="og:see_also" content="https://fikfak.news/" />
  <meta property="og:see_also" content="https://www.youtube.com/@fikfakmaster" />
  
  <!-- Telegram -->
  <meta name="telegram:channel" content="@fikfakmaster" />
  
  <!-- Canonical URL -->
  <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>" />
  
  <!-- Favicon & App Icons -->
  <link rel="icon" type="image/png" href="assets/favicons/favicon.png" />
  <link rel="manifest" href="assets/favicons/site.webmanifest" />
  
  <!-- Android Chrome Icons -->
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="application-name" content="FikFak News" />
  
  <!-- Theme Color -->
  <meta name="theme-color" content="#1c63cf" />
  <meta name="msapplication-TileColor" content="#1c63cf" />
  <meta name="msapplication-TileImage" content="android-chrome-192x192.png" />
  
  <!-- Performance Optimization -->
  <link rel="preconnect" href="https://www.youtube.com" crossorigin />
  <link rel="preconnect" href="https://i.ytimg.com" crossorigin />
  <link rel="preconnect" href="https://img.youtube.com" crossorigin />
  <link rel="preconnect" href="https://fikfak.news" crossorigin />
  <link rel="preconnect" href="https://i.imgur.com" crossorigin />
  <link rel="preconnect" href="https://videos.fikfak.news" crossorigin />
  <link rel="dns-prefetch" href="https://www.youtube.com" />
  <link rel="dns-prefetch" href="https://i.ytimg.com" />
  <link rel="dns-prefetch" href="https://img.youtube.com" />
  <link rel="dns-prefetch" href="https://fonts.googleapis.com" />
  <!-- Preload YouTube IFrame API for faster video loading -->
  <link rel="preload" as="script" href="https://www.youtube.com/iframe_api" />
  <link rel="dns-prefetch" href="https://fikfak.news" />
  <link rel="dns-prefetch" href="https://i.imgur.com" />
  <link rel="dns-prefetch" href="https://videos.fikfak.news" />
  
  <!-- Additional SEO -->
  <meta name="rating" content="General" />
  <meta name="distribution" content="Global" />
  <meta name="coverage" content="Worldwide" />
  <meta name="target" content="all" />
  <meta name="HandheldFriendly" content="True" />
  <meta name="MobileOptimized" content="320" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="format-detection" content="telephone=no" />
  
  <!-- Geographic Tags -->
  <meta name="geo.region" content="NL" />
  <meta name="geo.placename" content="Netherlands" />
  
  <!-- Additional SEO Meta Tags -->
  <meta name="news_keywords" content="FikFak News, Dirk Theuns, alternatief nieuws, onafhankelijke journalistiek, beste journalist 2025" />
  <meta name="category" content="News and Media" />
  <meta name="publisher" content="FikFak News" />
  
  <!-- Schema.org JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "WebSite",
        "@id": "https://www.fikfak.news/#website",
        "url": "https://www.fikfak.news/",
        "name": "FikFak News",
        "alternateName": "FikFak",
        "description": "Officiële landingspagina van FikFak News met de nieuwste video van Dirk Theuns (@fikfakmaster). Onafhankelijk nieuws en actuele analyse.",
        "publisher": {
          "@id": "https://www.fikfak.news/#organization"
        },
        "inLanguage": "nl-NL",
        "about": {
          "@type": "Thing",
          "name": "Nieuws en Actualiteiten"
        }
      },
      {
        "@type": "Organization",
        "@id": "https://www.fikfak.news/#organization",
        "name": "FikFak News",
        "url": "https://fikfak.news/",
        "logo": {
          "@type": "ImageObject",
          "@id": "https://www.fikfak.news/#logo",
          "url": "https://www.fikfak.news/logo.png",
          "width": 512,
          "height": 512,
          "caption": "FikFak News Logo"
        },
        "image": {
          "@id": "https://www.fikfak.news/#logo"
        },
        "description": "Onafhankelijk nieuwsplatform met alternatieve kijk op actuele gebeurtenissen",
        "sameAs": [
          "https://www.youtube.com/@fikfakmaster",
          "https://twitter.com/dirktheuns",
          "https://www.dirktheuns.be/",
          "https://fikfak.news/"
        ]
      },
      {
        "@type": "Person",
        "@id": "https://www.fikfak.news/#creator",
        "name": "Dirk Theuns",
        "alternateName": "FikFakMaster",
        "url": "https://www.dirktheuns.be/",
        "description": "Winnaar Beste Journalist 2025. Journalist en maker van FikFak News. Brengt onafhankelijk nieuws met een kritische blik op media en politiek.",
        "image": {
          "@type": "ImageObject",
          "url": "https://www.fikfak.news/dirk-theuns.jpg",
          "caption": "Dirk Theuns - Winnaar Beste Journalist 2025"
        },
        "sameAs": [
          "https://www.youtube.com/@fikfakmaster",
          "https://twitter.com/dirktheuns",
          "https://fikfak.news/"
        ],
        "jobTitle": "Journalist",
        "award": "Beste Journalist 2025 - tScheldt",
        "worksFor": {
          "@id": "https://www.fikfak.news/#organization"
        }
      },
      {
        "@type": "WebPage",
        "@id": "https://www.fikfak.news/#webpage",
        "url": "<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>",
        "name": "<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>",
        "description": "<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>",
        "isPartOf": {
          "@id": "https://www.fikfak.news/#website"
        },
        "about": {
          "@type": "Thing",
          "name": "Nieuws en Actualiteiten"
        },
        "primaryImageOfPage": {
          "@type": "ImageObject",
          "@id": "https://www.fikfak.news/#primaryimage",
          "url": "<?php echo htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8'); ?>",
          "width": 480,
          "height": 360,
          "caption": "<?php echo htmlspecialchars($selectedTitle, ENT_QUOTES, 'UTF-8'); ?>"
        },
        "datePublished": "<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>",
        "dateModified": "<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>",
        "inLanguage": "nl-NL",
        "potentialAction": [
          {
            "@type": "WatchAction",
            "target": ["<?php echo htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8'); ?>"]
          }
        ]
      },
      {
        "@type": "VideoObject",
        "@id": "https://www.fikfak.news/#video",
        "name": "<?php echo htmlspecialchars($selectedTitle, ENT_QUOTES, 'UTF-8'); ?>",
        "description": "<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>",
        "thumbnailUrl": "<?php echo htmlspecialchars($thumbnailUrl, ENT_QUOTES, 'UTF-8'); ?>",
        "uploadDate": "<?php echo htmlspecialchars($publishedIso, ENT_QUOTES, 'UTF-8'); ?>",
        "contentUrl": "<?php echo htmlspecialchars($watchUrl, ENT_QUOTES, 'UTF-8'); ?>",
        "embedUrl": "<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>",
        "publisher": {
          "@id": "https://www.fikfak.news/#organization"
        },
        "creator": {
          "@id": "https://www.fikfak.news/#creator"
        },
        "author": {
          "@id": "https://www.fikfak.news/#creator"
        },
        "genre": "Nieuws",
        "inLanguage": "nl-NL"
      },
      {
        "@type": "BreadcrumbList",
        "@id": "https://www.fikfak.news/#breadcrumb",
        "itemListElement": [
          {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "https://www.fikfak.news/"
          }
        ]
      },
      {
        "@type": "NewsMediaOrganization",
        "@id": "https://www.fikfak.news/#newsmedia",
        "name": "FikFak News",
        "url": "https://fikfak.news/",
        "logo": {
          "@id": "https://www.fikfak.news/#logo"
        },
        "description": "Alternatief nieuwsplatform voor onafhankelijk nieuws",
        "founder": {
          "@id": "https://www.fikfak.news/#creator"
        },
        "inLanguage": "nl-NL"
      }
    ]
  }
  </script>

  <style>
        /* Invert colors of the newsletter iframe for option2 */
        .newsletter-card.option2 .iframe-wrap iframe {
          filter: invert(1) hue-rotate(180deg) !important;
          background: transparent !important;
        }
    :root{
      --bg:#0f1724;
      --card:#0b1220;
      --accent:#1c63cf;
      --muted:#9aa4b2;
      --max-width:980px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background:linear-gradient(180deg,#071028 0%, #071b2b 100%);
      color:#e6eef6;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      display:flex;
      min-height:100vh;
      align-items:center;
      justify-content:center;
      padding:32px 16px;
    }
    .container{
      width:100%;
      max-width:var(--max-width);
      margin:0 auto;
    }
    header{
      display:flex;
      align-items:center;
      gap:16px;
      margin-bottom:18px;
      flex-wrap:wrap;
    }
    .logo{
      width:64px;
      height:64px;
      border-radius:12px;
      background:linear-gradient(135deg,var(--accent),#ffb86b);
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:700;
      color:#071428;
      font-size:20px;
      box-shadow:0 6px 18px rgba(0,0,0,.4);
    }
    h1{font-size:20px;margin:0}
    p.lead{margin:4px 0 0;color:var(--muted);font-size:14px}
    .main-grid{
      display:grid;
      grid-template-columns: 1fr 320px;
      gap:20px;
      align-items:start;
    }
    @media (max-width:880px){
      .main-grid{grid-template-columns:1fr}
      .sidebar{order:2}
    }
    .video-card{
      background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      border:1px solid rgba(255,255,255,0.03);
      padding:12px;
      border-radius:12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 4px 16px rgba(28,99,207,0.15), 0 0 1px rgba(255,255,255,0.1);
    }
    .event-promo{
      width:100%;
      max-width:var(--max-width);
      margin:0 auto 20px;
      padding:24px;
      border-radius:18px;
      background:
        radial-gradient(circle at top left, rgba(255,255,255,0.16), transparent 36%),
        linear-gradient(135deg, rgba(28,99,207,0.22), rgba(8,20,35,0.95) 55%, rgba(255,184,107,0.16));
      border:1px solid rgba(255,255,255,0.08);
      box-shadow:0 18px 42px rgba(2,6,23,0.32);
      overflow:hidden;
    }
    .event-promo-grid{
      display:grid;
      grid-template-columns:minmax(0, 1.05fr) minmax(0, 0.95fr);
      gap:18px;
      align-items:stretch;
    }
    .event-promo-media{
      position:relative;
      display:block;
      min-height:100%;
      border-radius:14px;
      overflow:hidden;
      background:#0a1424;
      box-shadow:0 16px 34px rgba(0,0,0,0.28);
    }
    .event-promo-media img{
      width:100%;
      height:100%;
      display:block;
      object-fit:cover;
    }
    .event-promo-copy{
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:14px;
    }
    .event-kicker{
      display:inline-flex;
      align-items:center;
      width:fit-content;
      padding:7px 12px;
      border-radius:999px;
      background:rgba(255,255,255,0.1);
      color:#f5f7fb;
      font-size:12px;
      font-weight:800;
      letter-spacing:0.12em;
      text-transform:uppercase;
    }
    .event-promo-copy h3{
      margin:0;
      font-size:30px;
      line-height:1.05;
      color:#ffffff;
    }
    .event-promo-copy p{
      margin:0;
      color:#d8e1ee;
      font-size:15px;
      line-height:1.65;
    }
    .event-meta-list{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:10px;
      margin:0;
      padding:0;
      list-style:none;
    }
    .event-meta-item{
      min-height:78px;
      padding:12px 14px;
      border-radius:14px;
      background:rgba(255,255,255,0.08);
      border:1px solid rgba(255,255,255,0.08);
    }
    .event-meta-label{
      display:block;
      margin-bottom:6px;
      color:#c0d0e4;
      font-size:11px;
      font-weight:700;
      letter-spacing:0.1em;
      text-transform:uppercase;
    }
    .event-meta-value{
      color:#ffffff;
      font-size:17px;
      font-weight:800;
      line-height:1.3;
    }
    .event-cta-row{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      align-items:center;
    }
    .event-cta-row .btn{
      min-width:180px;
    }
    .event-cta-row .btn-outline{
      color:#ffffff;
      border-color:rgba(255,255,255,0.2);
      background:rgba(255,255,255,0.05);
    }
    .event-cta-row .btn-outline:hover{
      color:#ffffff;
      border-color:rgba(255,255,255,0.32);
      background:rgba(255,255,255,0.1);
    }
    .event-footnote{
      color:#cbd5e1;
      font-size:12px;
    }
    .iframe-wrap{
      position:relative;
      padding-top:56.25%;
      border-radius:10px;
      overflow:hidden;
      background:linear-gradient(180deg,#081423,#071428);
    }
    /* The player area will be dynamically created inside #player-wrap */
    #player, .fallback-thumb { position:absolute; inset:0; width:100%; height:100%; display:block; border:0; }
    .fallback-thumb { display:flex; align-items:center; justify-content:center; gap:14px; background-size:cover; background-position:center; color:#fff; text-shadow:0 2px 6px rgba(0,0,0,.8); padding:18px; box-sizing:border-box; }
    .fallback-overlay { background:linear-gradient(180deg,rgba(0,0,0,0.15),rgba(0,0,0,0.45)); padding:12px; border-radius:8px; max-width:85%; text-align:left; }
    .meta{
      display:flex;
      justify-content:space-between;
      gap:12px;
      margin-top:12px;
      align-items:center;
    }
    .video-title{font-weight:600;font-size:16px}
    .channel-actions{display:flex;gap:8px;align-items:center}
    .share-panel{margin-top:12px;padding:12px;border:1px solid rgba(255,255,255,0.08);border-radius:10px;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));}
    .share-title{font-size:14px;font-weight:700;margin-bottom:10px;color:#fff}
    .share-actions{display:flex;flex-wrap:wrap;gap:8px}
    .share-actions .btn{font-size:13px;padding:8px 12px;min-height:40px}
    .share-feedback{margin-top:8px;min-height:18px;font-size:12px;color:var(--muted)}
    .btn{
      background:var(--accent);
      color:#fff;
      padding:8px 12px;
      border-radius:8px;
      font-weight:700;
      text-decoration:none;
      display:inline-block;
      min-height:44px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      touch-action:manipulation;
      transition:all 0.3s ease;
      cursor:pointer;
    }
    .btn:hover{
      transform:translateY(-2px);
      box-shadow:0 12px 32px rgba(28,99,207,0.5);
      filter:brightness(1.1);
    }
    .btn:active{
      transform:translateY(0);
    }
    .muted{color:var(--muted);font-size:13px}
    .sidebar{
      display:flex;
      flex-direction:column;
      gap:12px;
    }
    .thumb{
      display:flex;
      gap:10px;
      align-items:center;
      background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.005));
      padding:8px;
      border-radius:8px;
      cursor:pointer;
      border:1px solid rgba(255,255,255,0.02);
      margin-bottom:12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3), 0 2px 6px rgba(28,99,207,0.1);
    }
    .thumb img{width:120px;height:68px;object-fit:cover;border-radius:6px}
    .thumb .tmeta{display:flex;flex-direction:column}
    .thumb .tmeta .t-title{font-size:13px;font-weight:600}
    .thumb .tmeta .t-time{font-size:12px;color:var(--muted)}
    .footer-note{margin-top:14px;color:var(--muted);font-size:13px}
    .error{color:#ffb2b2;background:rgba(255,80,80,0.06);padding:10px;border-radius:8px;border:1px solid rgba(255,80,80,0.08)}

    /* Mobile responsiveness tweaks 🔧 */
    #yt-player-inner, #player, iframe { width:100%; height:100%; }

    /* Smaller screens: tighten paddings, reduce thumb sizes and font sizes */
    @media (max-width: 640px) {
      body { padding:16px 8px; }
      .container { max-width: 100%; padding: 0; }
      header { gap:12px; margin-bottom:12px; }
      .logo { width:48px; height:48px; font-size:16px; }
      h1 { font-size:18px; }
      p.lead { font-size:13px; }
      .video-card { padding:10px; }
      .iframe-wrap { padding-top:56.25%; border-radius:8px; }
      .video-title { font-size:15px; }
      .btn { padding:10px 14px; font-size:14px; min-height:44px; }
      .thumb img { width:88px; height:50px; }
      .thumb { gap:8px; padding:6px; }
      .sidebar { gap:10px; }
      .meta { flex-direction:column; align-items:flex-start; gap:10px; }
      .channel-actions { width:100%; }
      .channel-actions .btn { width:100%; }
      .share-actions .btn { flex:1 1 calc(50% - 8px); min-width:0; }
      .event-promo { padding:16px 12px; border-radius:16px; }
      .event-promo-grid { grid-template-columns:1fr; }
      .event-promo-copy h3 { font-size:24px; }
      .event-meta-list { grid-template-columns:1fr; }
      .event-cta-row { flex-direction:column; align-items:stretch; }
      .event-cta-row .btn { width:100%; min-width:0; }
    }

    /* Ultra small screens - keep things compact */
    @media (max-width: 380px) {
      body { padding:12px 6px; }
      .logo { width:40px; height:40px; font-size:14px; }
      h1 { font-size:16px; }
      .video-title { font-size:14px; }
      .thumb img { width:76px; height:44px; }
      .muted { font-size:12px; }
      .btn { padding:8px 12px; font-size:13px; }
      .event-promo-copy h3 { font-size:21px; }
      .event-meta-item { min-height:0; }
      .event-meta-value { font-size:16px; }
    }

    /* Steun Fikfak News - support section styling (two-column) */
    .support-card{
      margin-top:14px;
      padding:24px;
      background:linear-gradient(135deg,rgba(28,99,207,0.08),rgba(28,99,207,0.03));
      border:2px solid rgba(28,99,207,0.2);
      border-radius:12px;
      color:#e6eef6;
      box-shadow:0 12px 40px rgba(28,99,207,0.15);
    }
    .support-card h2, .support-card h3, .support-card h4{
      line-height:1.3;
    }
    .support-card h3{margin:0 0 12px;font-size:18px;font-weight:700}
    .support-body{display:flex;gap:24px;align-items:flex-start}
    .support-left{flex:0 0 60%}
    .support-left p{margin:0 0 8px;color:#e6eef6;font-size:15px;line-height:1.6}
    .support-right{flex:0 0 40%;display:flex;flex-direction:column;align-items:center;gap:20px;padding:20px}
    .support-right .payconiq{width:100%;max-width:240px;height:auto;border-radius:8px;border:1px solid rgba(255,255,255,0.03);background:#fff;padding:8px}
    .support-right .btn{width:100%;text-align:center;box-sizing:border-box;padding-left:16px;padding-right:16px}

    /* Medium screens: stack but keep QR reasonably sized */
    @media (max-width: 880px) {
      .support-body{flex-direction:column}
      .support-right{width:100%;align-items:flex-start;order:2;padding:12px 0 0}
      .support-left{order:1}
      .support-right .payconiq{max-width:220px;width:100%}
      .support-left p{font-size:13px}
    }
    /* Nieuwsbrief inschrijving - verbeterde inschrijvingskaart */
    .newsletter-card{margin-top:18px;padding:18px;background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.008));border:1px solid rgba(255,255,255,0.03);border-radius:12px;display:block}
    .newsletter-content{display:flex;flex-direction:column;gap:12px;align-items:center;text-align:center}
    .newsletter-content h3{margin:0;font-size:20px}
    .newsletter-content p{margin:0;color:var(--muted)}
    .newsletter-benefits{margin:8px 0 0 0;color:var(--muted);font-size:13px;line-height:1.4;display:inline-block;text-align:left}
    .newsletter-form{width:100%;display:flex;flex-direction:column;gap:10px;margin-top:6px;align-items:stretch}
    .newsletter-input{padding:12px 14px;border-radius:20px;border:2px solid rgba(255,255,255,0.1);background:rgba(20,20,35,0.8);color:#ffffff;font-size:15px;outline:none;max-width:520px;width:100%;min-height:44px;touch-action:manipulation;-webkit-appearance:none;appearance:none;}
    .newsletter-input::placeholder{color:rgba(255,255,255,0.6)}
    .newsletter-input:focus{box-shadow:0 6px 18px rgba(0,0,0,0.4);border-color:var(--accent);background:rgba(20,20,35,0.95)}
    .newsletter-input::-webkit-autofill,
    .newsletter-input::-webkit-autofill:hover,
    .newsletter-input::-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 30px rgba(20,20,35,0.8) inset !important;
      -webkit-text-fill-color: #ffffff !important;
    }
    /* Use the same accent color as other buttons and make the button larger */
    .newsletter-btn{align-self:center;padding:14px 24px;border-radius:10px;background:var(--accent);color:#071428;font-weight:700;border:none;cursor:pointer;font-size:16px;min-width:220px;min-height:48px;box-shadow:0 6px 18px rgba(2,6,23,0.25);transition:transform .12s ease,filter .12s ease;touch-action:manipulation;-webkit-tap-highlight-color:transparent;}
    .newsletter-btn:hover{filter:brightness(0.98);transform:translateY(-1px)}
    /* Outline variant for secondary buttons that should match the same size */
    .btn-outline{background:transparent;color:var(--muted);border:1px solid rgba(255,255,255,0.06);box-shadow:none}
    .btn-outline:hover{color:var(--accent);border-color:rgba(255,255,255,0.09)}
    .newsletter-meta{display:flex;flex-direction:column;gap:6px;align-items:center;margin-top:6px}
    #newsletter-msg{font-weight:600}
    #newsletter-msg.success{color:#a7ffb2}
    #newsletter-msg.error{color:#ffb2b2}
    .newsletter-privacy{font-size:12px;color:var(--muted)}
    /* Ensure embedded iframe shows form fields: give it a sensible min-height and responsive fallbacks */
    .newsletter-card .iframe-wrap{padding-top:0;margin-bottom:-160px;min-height:420px;overflow:hidden;border-radius:10px;width:100%;background:transparent}
    /* Try to force dark text inside embed (best effort for cross-origin iframes) */
    .newsletter-card iframe{background:transparent !important;color:#ffffff00 !important;}
    .newsletter-card .iframe-wrap{position:relative}
    .newsletter-card.submitted{border-color:rgba(167,255,178,0.12);box-shadow:0 8px 30px rgba(86, 203, 102, 0.03)}
    @media (max-width:880px){.newsletter-card{padding:14px}.newsletter-form{width:100%}.newsletter-btn{width:100%;align-self:stretch;min-width:0}}
    @media (max-width:520px){.newsletter-card .iframe-wrap{min-height:420px}}
    @media (max-width:380px){.newsletter-card .iframe-wrap{min-height:360px}}
    /* Small screens / mobile: make QR full-width and center everything */
    @media (max-width: 520px) {
      .support-body{gap:16px}
      .support-card { padding:16px 12px; }
      .support-card h3 { font-size:16px; text-align:center; }
      .support-left { text-align:left; }
      /* remove side padding on the right column so the QR can expand */
      .support-right{width:100%;align-items:center;padding:0;margin:0}
      /* make the image occupy the card's inner width (no extra white padding)
         and avoid horizontal overflow */
      .support-right .payconiq{display:block;max-width:280px;width:100%;padding:0;margin:0 auto;border-radius:6px;border:none;background:transparent}
      .support-right .btn{font-size:15px;padding:12px 16px; width:100%; max-width:280px;}
      .support-left p{font-size:14px}
      .support-left ul { font-size:13px; }
    }

    /* Newsletter banner image hover effect */
    .newsletter-banner-link{display:inline-block;transition:transform 0.3s ease, box-shadow 0.3s ease}
    .newsletter-banner-link:hover{transform:scale(1.03);box-shadow:0 8px 24px rgba(28,99,207,0.25)}
    .newsletter-banner-link img{display:block;transition:filter 0.3s ease}
    .newsletter-banner-link:hover img{filter:brightness(1.08)}

    /* Site footer */
    .site-footer{margin-top:22px;padding:20px 18px;border-top:1px solid rgba(255,255,255,0.03);border-radius:8px;color:var(--muted)}
    .footer-grid{display:flex;gap:18px;align-items:center;justify-content:space-between;max-width:var(--max-width);margin:0 auto}
    .footer-col{flex:1;min-width:0}
    .footer-col .footer-link{color:var(--muted);text-decoration:none;font-weight:700;display:inline-flex;flex-direction:column;align-items:center;gap:8px}
    .footer-col .footer-link:hover{color:var(--accent)}
    .footer-text{color:var(--muted);font-size:14px}
    .footer-social{text-align:right}
    .footer-logo{max-height:48px;display:block;width:auto;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,0.12)}
    /* Make Webtechnics logo larger and place it above the credit text */
    .webtechnics-logo{max-height:56px;display:inline-block;margin:0 auto 10px;width:auto;background:#fff;padding:6px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,0.06)}
    .footer-credit{display:flex;flex-direction:column;align-items:center;text-align:center}
    .footer-link-text{font-weight:700;color:inherit}
    .social-link{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.01);margin-left:8px;color:var(--muted);text-decoration:none;min-width:44px;min-height:44px;touch-action:manipulation;}
    .social-link svg{width:18px;height:18px;fill:currentColor}
    .social-link:hover{background:rgba(255,255,255,0.02);color:var(--accent)}
    .account-nav-panel{display:inline-flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:nowrap;padding:7px 9px;border-radius:999px;background:linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));border:1px solid rgba(255,255,255,0.08);box-shadow:0 10px 24px rgba(0,0,0,0.24);width:fit-content;max-width:100%;margin-left:auto}
    .account-nav-label{color:#d7e5f7;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;white-space:nowrap}
    .account-nav-user{color:#ffffff;font-size:13px;font-weight:600;white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis}
    .account-nav-links{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:nowrap}
    .account-nav-link{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:10px 14px;border-radius:999px;text-decoration:none;font-size:13px;font-weight:700;transition:transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;white-space:nowrap}
    .account-nav-link:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(0,0,0,0.2)}
    .account-nav-link-primary{background:linear-gradient(135deg,var(--accent),#4ea0ff);color:#fff}
    .account-nav-link-secondary{background:rgba(255,255,255,0.06);color:#e6eef6;border:1px solid rgba(255,255,255,0.08)}
    .account-nav-link-ghost{color:#e6eef6;border:1px solid rgba(255,255,255,0.1);background:transparent}
    @media (max-width:640px){.footer-grid{flex-direction:column;align-items:center;gap:8px}.footer-social{text-align:center}.social-link{margin-left:6px;margin-right:6px}.footer-logo{max-height:40px}.webtechnics-logo{max-height:40px;padding:4px}}

    /* Bank transfer modal */
    .modal-overlay{position:fixed;inset:0;background:rgba(2,6,23,0.6);display:flex;align-items:center;justify-content:center;z-index:1200;padding:16px;}
    .modal-overlay[hidden]{display:none}
    .modal{background:linear-gradient(180deg, #071428, #081423);padding:18px;border-radius:12px;max-width:560px;width:92%;box-shadow:0 18px 60px rgba(2,6,23,0.6);border:1px solid rgba(255,255,255,0.03);color:#e6eef6;max-height:90vh;overflow-y:auto;}
    .modal h3{margin:0 0 8px;font-size:18px}
    .modal p.modal-intro{color:var(--muted);font-size:13px;margin-bottom:12px;line-height:1.4}
    .modal label{display:block;margin-top:12px}
    .modal label:first-of-type{margin-top:6px}
    .modal .modal-input{width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);background:rgba(255,255,255,0.02);color:inherit;margin-top:6px;font-size:16px;min-height:44px;touch-action:manipulation;-webkit-appearance:none;appearance:none;}
    .modal textarea.modal-input{resize:vertical;min-height:80px}
    .modal .modal-actions{display:flex;gap:10px;align-items:center;justify-content:center;margin-top:16px;flex-wrap:wrap;}
    .modal .modal-actions .btn { min-height:44px; padding:10px 20px; }
    /* copy rows inside modal */
    .copy-row{display:flex;gap:10px;align-items:center;background:rgba(255,255,255,0.02);padding:8px;border-radius:8px;margin-top:8px;flex-wrap:wrap;}
    .copy-row code{background:transparent;padding:2px 6px;border-radius:4px;color:inherit;word-break:break-all;} 
    .modal .modal-close, .modal .modal-close-contact{display:none}
    .modal .muted{font-weight:600}
    .modal .muted.success{color:#a7ffb2}
    .modal .muted.error{color:#ffb2b2}
    @media (max-width:480px){.modal{padding:14px;width:95%;}.modal h3{font-size:16px;}.modal .modal-actions{flex-direction:column;width:100%;}.modal .modal-actions .btn{width:100%;}}
    /* --- NEWSLETTER CARD STYLE OPTIONS --- */
    /* Option 1: Recommended (dark, transparent, light text, dark button) */
    .newsletter-card.option1 {
      background: transparent !important;
      border-color: rgba(255,255,255,0.03) !important;
      box-shadow: none !important;
    }
    .newsletter-card.option1 h3,
    .newsletter-card.option1 p,
    .newsletter-card.option1 .newsletter-privacy,
    .newsletter-card.option1 .muted {
      color: #e6eef6 !important;
    }
    .newsletter-card.option1 .newsletter-btn {
      background: #071428 !important;
      color: #fff !important;
      border: 1px solid rgba(255,255,255,0.06) !important;
      box-shadow: 0 6px 18px rgba(2,6,23,0.25) !important;
    }
    .newsletter-card.option1 .btn-outline {
      border-color: rgba(255,255,255,0.06) !important;
      color: #e6eef6 !important;
      background: transparent !important;
    }

    /* Option 2: Inverted (light overlay, dark text, light button) */
    .newsletter-card.option2 {
      background: rgba(255,255,255,0.06) !important;
      border-color: rgba(255,255,255,0.08) !important;
      box-shadow: 0 8px 30px rgba(167,255,178,0.03) !important;
      backdrop-filter: blur(6px);
    }
    .newsletter-card.option2 h3,
    .newsletter-card.option2 p,
    .newsletter-card.option2 .newsletter-privacy,
    .newsletter-card.option2 .muted {
      color: #071428 !important;
    }
    .newsletter-card.option2 .newsletter-btn {
      background: #fff !important;
      color: #071428 !important;
      border: 1px solid rgba(7,20,40,0.06) !important;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08) !important;
    }
    .newsletter-card.option2 .btn-outline {
      background: transparent !important;
      color: #071428 !important;
      border-color: rgba(7,20,40,0.06) !important;
    }
    /* --- END NEWSLETTER CARD STYLE OPTIONS --- */

    /* Loading Screen */
    #loading-screen{position:fixed;inset:0;background:linear-gradient(135deg,#071028 0%,#0a1a2f 50%,#071b2b 100%);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:1;transition:opacity 0.6s ease,visibility 0.6s ease}
    #loading-screen.loaded{opacity:0;visibility:hidden}
    .loading-content{text-align:center;position:relative}
    .loading-logo{width:140px;height:140px;margin:0 auto 30px;position:relative;animation:logoSpin 3s ease-in-out infinite}
    .loading-logo img{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 8px 32px rgba(28,99,207,0.5))}
    @keyframes logoSpin{0%{transform:rotate(0deg) scale(1)}25%{transform:rotate(5deg) scale(1.05)}50%{transform:rotate(0deg) scale(1.08)}75%{transform:rotate(-5deg) scale(1.05)}100%{transform:rotate(0deg) scale(1)}}
    .loading-text{color:#e6eef6;font-size:24px;font-weight:700;margin-bottom:12px;letter-spacing:1px}
    .loading-subtext{color:#9aa4b2;font-size:14px;margin-bottom:30px;font-weight:500}
    .loading-bar-container{width:240px;height:3px;background:rgba(255,255,255,0.05);border-radius:10px;overflow:hidden;margin:0 auto;position:relative}
    .loading-bar{height:100%;background:linear-gradient(90deg,#1c63cf,#1557b8,#1c63cf);background-size:200% 100%;animation:loadingProgress 1.5s ease-in-out infinite;border-radius:10px}
    @keyframes loadingProgress{0%{width:0%;background-position:0% 50%}50%{width:70%;background-position:100% 50%}100%{width:100%;background-position:0% 50%}}
    .loading-dots{display:inline-flex;gap:6px;margin-top:20px}
    .loading-dot{width:8px;height:8px;background:#1c63cf;border-radius:50%;animation:dotPulse 1.4s ease-in-out infinite}
    .loading-dot:nth-child(2){animation-delay:0.2s}
    .loading-dot:nth-child(3){animation-delay:0.4s}
    @keyframes dotPulse{0%,100%{opacity:0.3;transform:scale(0.8)}50%{opacity:1;transform:scale(1.1)}}
    .news-ticker{position:absolute;bottom:60px;left:0;right:0;background:rgba(28,99,207,0.08);padding:8px;border-top:2px solid rgba(28,99,207,0.2);border-bottom:2px solid rgba(28,99,207,0.2);overflow:hidden}
    .news-ticker-text{color:#1c63cf;font-size:13px;font-weight:600;white-space:nowrap;animation:tickerScroll 15s linear infinite}
    @keyframes tickerScroll{0%{transform:translateX(100%)}100%{transform:translateX(-100%)}}
    
    /* Mobile Navigation Improvements */
    nav ul { padding:0 8px; }
    nav ul li a { padding:8px 4px; min-height:44px; display:inline-flex; align-items:center; }
    
    /* Logo in navigation */
    nav ul li picture img { max-width:180px; }
    
    @media (max-width: 768px) {
      /* Stack navigation for tablets/mobile */
      nav ul { flex-direction:column; gap:16px; }
      nav ul li:first-child { order:2; justify-content:center; }
      nav ul li:nth-child(2) { order:1; } /* Logo first */
      nav ul li:nth-child(2) picture img { height:40px; }
      nav ul li:last-child { order:3; text-align:center; flex:none; }
    }

    
    @media (max-width: 640px) {
      nav ul li:first-child { gap:12px; }
      .social-link { margin:0 4px; }
      .account-nav-panel { justify-content:center; }
      .account-nav-links { justify-content:center; width:100%; }
    }
    
    @media (max-width: 420px) {
      nav ul li:nth-child(2) picture img { height:35px; }
      nav ul li:first-child { gap:8px; }
    }
    
    /* Accessibility: Skip Navigation Link */
    .skip-link {
      position:absolute;
      top:-40px;
      left:0;
      background:var(--accent);
      color:#fff;
      padding:8px 16px;
      text-decoration:none;
      border-radius:0 0 8px 0;
      font-weight:700;
      z-index:9999;
      transition:top 0.2s ease;
    }
    .skip-link:focus {
      top:0;
      outline:3px solid #ffb86b;
      outline-offset:2px;
    }
    
    /* Accessibility: Focus Indicators */
    *:focus-visible {
      outline:3px solid #ffb86b;
      outline-offset:2px;
      border-radius:4px;
    }
    button:focus-visible, a:focus-visible, input:focus-visible, textarea:focus-visible {
      outline:3px solid #ffb86b;
      outline-offset:2px;
    }
    
    /* Accessibility: High Contrast for Links */
    a {
      color:#5fa3ff;
      text-decoration:underline;
    }
    a:hover {
      color:#8fc3ff;
    }
    
    /* Screen reader only content */
    .sr-only {
      position:absolute;
      width:1px;
      height:1px;
      padding:0;
      margin:-1px;
      overflow:hidden;
      clip:rect(0,0,0,0);
      white-space:nowrap;
      border-width:0;
    }

    /* Cookie Banner Styles */
    .cookie-banner {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      border-top: 3px solid var(--accent);
      padding: 20px;
      box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
      z-index: 9998;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from { transform: translateY(100%); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .cookie-banner-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .cookie-banner-text {
      flex: 1;
      min-width: 250px;
      color: #fff;
    }

    .cookie-banner-text h3 {
      margin: 0 0 8px;
      font-size: 16px;
      font-weight: 700;
    }

    .cookie-banner-text p {
      margin: 0;
      font-size: 13px;
      color: #ccc;
      line-height: 1.4;
    }

    .cookie-banner-text a {
      color: var(--accent);
      text-decoration: underline;
    }

    .cookie-banner-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .cookie-btn {
      padding: 10px 16px;
      border: none;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      white-space: nowrap;
    }

    .cookie-btn-primary {
      background: linear-gradient(135deg, var(--accent), #1e7eeb);
      color: white;
    }

    .cookie-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(28, 99, 207, 0.4);
    }

    .cookie-btn-outline {
      background: transparent;
      color: #ccc;
      border: 1px solid #666;
    }

    .cookie-btn-outline:hover {
      background: rgba(255,255,255,0.1);
      border-color: var(--accent);
      color: var(--accent);
    }

    @media (max-width: 768px) {
      .cookie-banner-content {
        flex-direction: column;
        align-items: stretch;
      }
      .cookie-banner-buttons {
        flex-direction: column;
      }
      .cookie-btn {
        width: 100%;
      }
    }

    /* Cookie Modal Styles */
    .cookie-modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.3s ease;
    }

    .cookie-modal.active {
      display: flex;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .cookie-modal-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.7);
    }

    .cookie-modal-content {
      position: relative;
      background: #1a1a2e;
      border-radius: 12px;
      padding: 40px;
      max-width: 500px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }

    .cookie-modal-close {
      position: absolute;
      top: 12px;
      right: 12px;
      background: none;
      border: none;
      font-size: 28px;
      cursor: pointer;
      color: #ccc;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
    }

    .cookie-modal-close:hover {
      color: var(--accent);
    }

    .cookie-modal h2 {
      margin: 0 0 24px;
      color: #fff;
      font-size: 20px;
    }

    .cookie-category {
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #333;
    }

    .cookie-category:last-of-type {
      border-bottom: none;
    }

    .cookie-checkbox {
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      user-select: none;
      color: #fff;
      font-size: 14px;
    }

    .cookie-checkbox input {
      display: none;
    }

    .checkmark {
      width: 20px;
      height: 20px;
      border: 2px solid #666;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
      flex-shrink: 0;
    }

    .cookie-checkbox input:checked + .checkmark {
      background: var(--accent);
      border-color: var(--accent);
    }

    .cookie-checkbox input:checked + .checkmark::after {
      content: '✓';
      color: #1a1a2e;
      font-size: 12px;
      font-weight: bold;
    }

    .cookie-checkbox input:disabled + .checkmark {
      background: #444;
      border-color: #666;
      cursor: not-allowed;
      opacity: 0.7;
    }

    .cookie-description {
      margin: 6px 0 0 32px;
      font-size: 12px;
      color: #999;
      line-height: 1.4;
    }

    .cookie-modal-buttons {
      display: flex;
      gap: 10px;
      margin-top: 28px;
      flex-direction: column;
    }

    .cookie-modal-buttons .btn {
      width: 100%;
      padding: 12px;
    }

    @media (max-width: 600px) {
      .cookie-modal-content {
        padding: 24px;
        max-height: 90vh;
        margin: 20px;
      }
    }
  </style>
</head>
<body>
  <!-- Skip Navigation Link for Screen Readers -->
  <a href="#main-content" class="skip-link">Spring naar hoofdinhoud</a>
  
  <!-- Loading Screen -->
  <div id="loading-screen" role="status" aria-live="polite" aria-label="Pagina wordt geladen">
    <div class="loading-content">
      <div class="loading-logo" aria-hidden="true">
        <picture>
          <source type="image/webp" srcset="assets/images/logo-fikfak.webp">
          <img src="assets/images/logo fikfak.png" alt="" loading="eager">
        </picture>
      </div>
      <div class="loading-text">FIKFAK NEWS</div>
      <div class="loading-subtext">Onafhankelijk Nieuws Laden...</div>
      <div class="loading-bar-container" aria-hidden="true">
        <div class="loading-bar"></div>
      </div>
      <div class="loading-dots" aria-hidden="true">
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
        <div class="loading-dot"></div>
      </div>
    </div>
    <div class="news-ticker" aria-hidden="true">
      <div class="news-ticker-text">BREAKING NEWS: Laden van de nieuwste FikFak News uitzending • Onafhankelijk nieuws en actuele analyse • Nagels met koppen slaan! 🔥</div>
    </div>
  </div>

  <div class="container">
    <!-- Navigation Menu -->
    <nav role="navigation" aria-label="Hoofdnavigatie" style="background:transparent;padding:12px 20px;margin-bottom:20px;">
      <ul style="list-style:none;margin:0;padding:0;display:flex;justify-content:space-between;align-items:center;gap:20px;" role="list">
        <!-- Left: Social Icons -->
        <li style="display:flex;gap:8px;flex:1;">
          <a href="https://www.facebook.com/groups/vriendenvandirktheuns" target="_blank" rel="noopener" class="social-link" aria-label="Facebook" title="Facebook">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22 12.07C22 6.48 17.52 2 12 2S2 6.48 2 12.07C2 17.09 5.66 21.25 10.44 21.95v-6.99h-3.14v-2.8h3.14V9.41c0-3.1 1.84-4.8 4.66-4.8 1.35 0 2.76.24 2.76.24v3.03h-1.55c-1.53 0-2.01.96-2.01 1.95v2.32h3.42l-.55 2.8h-2.87v6.99C18.34 21.25 22 17.09 22 12.07z"/></svg>
          </a>
          <a href="https://x.com/dirktheuns" target="_blank" rel="noopener" class="social-link" aria-label="X" title="X">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <a href="https://www.youtube.com/@fikfakmaster" target="_blank" rel="noopener" class="social-link" aria-label="YouTube" title="YouTube">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M23.5 6.2s-.2-1.6-.8-2.3c-.8-.9-1.7-.9-2.1-1C16.9 2.4 12 2.4 12 2.4h-.1s-4.9 0-7.5.5c-.4.1-1.3.1-2.1 1C1.6 4.6 1.5 6.2 1.5 6.2S1 8.1 1 10v1.9c0 1.9.5 3.8.5 3.8s.2 1.6.8 2.3c.8.9 1.9.8 2.4.9 1.7.2 7 .5 7.4.5h.1s4.9 0 7.5-.5c.4-.1 1.3-.1 2.1-1 .6-.6.8-2.3.8-2.3s.5-1.9.5-3.8V10c0-1.9-.5-3.8-.5-3.8zM9.8 15.1V8.9l6.1 3.1-6.1 3.1z"/></svg>
          </a>
        </li>
        <!-- Center: Logo -->
        <li style="flex-shrink:0;">
          <a href="#main-content" aria-label="FikFak News - Terug naar boven" style="display:flex;align-items:center;text-decoration:none;" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">
            <picture>
              <source type="image/webp" srcset="assets/images/logo-fikfak.webp">
              <img src="assets/images/logo fikfak.png" alt="FikFak News Logo" style="height:50px;width:auto;display:block;transition:transform 0.3s ease,filter 0.3s ease;" onmouseover="this.style.transform='scale(1.05)';this.style.filter='brightness(1.1)';" onmouseout="this.style.transform='scale(1)';this.style.filter='brightness(1)';" loading="eager">
            </picture>
          </a>
        </li>
        <!-- Right: Account + Contact -->
        <li style="flex:1;display:flex;justify-content:flex-end;">
          <div class="account-nav-panel">
            <?php if ($isLoggedIn): ?>
              <span class="account-nav-label">Account</span>
              <span class="account-nav-user"><?php echo htmlspecialchars($accountUsername !== '' ? $accountUsername : 'Ingelogd'); ?></span>
              <div class="account-nav-links">
                <a href="php/dashboard.php" class="account-nav-link account-nav-link-primary">Mijn account</a>
                <a href="php/logout.php" class="account-nav-link account-nav-link-secondary">Uitloggen</a>
              </div>
            <?php else: ?>
              <span class="account-nav-label">Leden</span>
              <div class="account-nav-links">
                <a href="php/login.php" class="account-nav-link account-nav-link-primary">Inloggen</a>
                <a href="php/register.php" class="account-nav-link account-nav-link-secondary">Registreren</a>
              </div>
            <?php endif; ?>
          </div>
        </li>
      </ul>
    </nav>
    
    <header role="banner">
      <h1 class="sr-only">FikFak News - Onafhankelijk Nieuws van Dirk Theuns</h1>
    </header>

    <main id="main-content" role="main">
    <div class="main-grid">
      <div class="video-card">
        <section aria-labelledby="current-video-heading">
          <h2 id="current-video-heading" class="sr-only">Huidige Video</h2>
        <div id="video-area">
          <div class="iframe-wrap" id="player-wrap" role="region" aria-label="Video speler">
            <!-- De IFrame Player API maakt de speler hier.
                 Als insluiten geblokkeerd is, tonen we een thumbnail + link. -->
            <div id="player"></div>
          </div>

          <div class="meta">
            <div>
              <div class="video-title" id="video-title" role="heading" aria-level="3" aria-live="polite">Bezig met laden van nieuwste video...</div>
              <div class="muted" id="video-date" aria-live="polite"></div>
            </div>
            <div class="channel-actions">
          <a class="btn" id="subscribe-btn" href="#" target="_blank" rel="noopener" style="background:linear-gradient(135deg,#ff0000,#cc0000);font-weight:800;padding:10px 20px;font-size:15px;" aria-label="Abonneer op FikFak News YouTube kanaal">🔔 Abonneren op YouTube</a>
            </div>
          </div>

          <section class="share-panel" aria-label="Deel deze uitzending">
            <div class="share-title">Deel deze uitzending</div>
            <div class="share-actions">
              <button type="button" class="btn" id="share-native-btn" aria-label="Delen via toestel">📲 Delen</button>
              <a class="btn btn-outline" id="share-whatsapp" href="#" target="_blank" rel="noopener noreferrer" aria-label="Deel via WhatsApp">WhatsApp</a>
              <a class="btn btn-outline" id="share-telegram" href="#" target="_blank" rel="noopener noreferrer" aria-label="Deel via Telegram">Telegram</a>
              <a class="btn btn-outline" id="share-x" href="#" target="_blank" rel="noopener noreferrer" aria-label="Deel via X">X</a>
              <a class="btn btn-outline" id="share-facebook" href="#" target="_blank" rel="noopener noreferrer" aria-label="Deel via Facebook">Facebook</a>
              <button type="button" class="btn btn-outline" id="share-copy-btn" aria-label="Kopieer deellink">🔗 Kopieer link</button>
            </div>
            <div id="share-feedback" class="share-feedback" aria-live="polite"></div>
          </section>
        </div>

        <div class="footer-note" id="status-note" role="status" aria-live="polite">
          Feed ophalen...
        </div>
        </section>

        <!-- support section moved below -->
      </div>

      <aside class="sidebar" role="complementary" aria-labelledby="recent-videos-heading">
        <h2 id="recent-videos-heading" class="sr-only">Recente uploads</h2>
        <div id="recent-list" role="list" aria-label="Lijst met recente video uploads">
          <!-- thumbnails komen hier -->
        </div>

        <div style="margin-top:auto">
          <div style="height:8px"></div>
        </div>
      </aside>
    </div>
    </main>
        <!-- Standen.info Banner -->
    <div style="text-align:center;margin-bottom:20px;display:flex;justify-content:center;">
      <a href="https://www.opdatemetjezelf.be/shop" target="_blank" rel="noopener noreferrer" class="newsletter-banner-link" aria-label="Bezoek Op Dat Met Jezelf shop - FikFak News sponsor" style="display:block;max-width:90%;width:100%;">
        <picture>
          <!-- Mobile: Square banner for screens smaller than 768px -->
          <source media="(max-width: 767px)" type="image/webp" srcset="assets/images/gadgets_fikfak_leaderboard_square.webp">
          <source media="(max-width: 767px)" srcset="assets/images/gadgets_fikfak_leaderboard_square.png">
          <!-- Desktop: Landscape banner for screens 768px and larger -->
          <source media="(min-width: 768px)" type="image/webp" srcset="assets/images/gadgets_fikfak_leaderboard.webp">
          <source media="(min-width: 768px)" srcset="assets/images/gadgets_fikfak_leaderboard.png">
          <!-- Fallback for older browsers -->
          <img src="assets/images/gadgets_fikfak_leaderboard.png" alt="FikFak News Leaderboard" style="border: solid 2px white;max-width:100%;height:auto;border-radius:8px;width:100%;display:block;" loading="lazy">
        </picture>
      </a>
    </div>
    <section class="event-promo" aria-labelledby="event-promo-heading">
      <div class="event-promo-grid">
        <a class="event-promo-media" href="https://www.opdatemetjezelf.be/shop" target="_blank" rel="noopener noreferrer" aria-label="Bekijk tickets voor het Fikfak Zomerfeest">
          <img src="assets/images/fikfak_zomerfeest_21_juni_BARN64.jpg" alt="Poster voor het Fikfak Zomerfeest met Mattias Desmet en Dirk Theuns" loading="lazy" width="768" height="768">
        </a>

        <div class="event-promo-copy">
          <span class="event-kicker">Live Event</span>
          <h3 id="event-promo-heading">Fikfak Zomerfeest met Mattias Desmet en Dirk Theuns</h3>
          <p>een live avond rond bewustzijn, ontmoeting en muziek.</p>

          <ul class="event-meta-list" aria-label="Praktische info Fikfak Zomerfeest">
            <li class="event-meta-item">
              <span class="event-meta-label">Datum</span>
              <span class="event-meta-value">Zondag 21 juni</span>
            </li>
            <li class="event-meta-item">
              <span class="event-meta-label">Startuur</span>
              <span class="event-meta-value">15u00</span>
            </li>
            <li class="event-meta-item">
              <span class="event-meta-label">Locatie</span>
              <span class="event-meta-value">Barn64, Brasschaat</span>
            </li>
            <li class="event-meta-item">
              <span class="event-meta-label">Tickets</span>
              <span class="event-meta-value">24 euro</span>
            </li>
          </ul>

          <div class="event-cta-row">
            <a class="btn" href="https://www.opdatemetjezelf.be/shop" target="_blank" rel="noopener noreferrer">Bestel Tickets</a>
            <a class="btn btn-outline" href="assets/images/fikfak_zomerfeest_21_juni_BARN64.jpg" target="_blank" rel="noopener noreferrer">Bekijk Poster</a>
          </div>

          <div class="event-footnote">Genieten, luisteren en dansen. Rechtstreeks gelinkt aan de shop op opdatemetjezelf.be.</div>
        </div>
      </div>
    </section>
    <!-- Steun Fikfak News (volledige breedte onder video's) -->
    <section class="support-card" id="support" aria-labelledby="support-heading">
      <h2 id="support-heading" style="text-align:center;margin:0 0 20px;font-size:28px;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:0.5px;">💰 Steun Fikfak News</h2>
      <div class="support-body">
        <div class="support-left">
          <h3 style="font-size:18px;margin:0 0 12px;color:#fff;">🎯 Waarom jouw steun belangrijk is</h3>
          <p><strong>Geniet je van het Fikfaknieuws? Help mee zodat het kan blijven bestaan.</strong></p>
          
          <h4 style="font-size:16px;margin:16px 0 8px;color:var(--accent);">📹 Achter de schermen</h4>
          <p>Ik maak dit programma helemaal zelf, van redactie en presentatie tot opname en montage. Gemiddeld kost elke aflevering veel tijd: ongeveer 2,5 dag per week aan werk.</p>
          
          <h4 style="font-size:16px;margin:16px 0 8px;color:var(--accent);">🆓 Blijft gratis en onafhankelijk</h4>
          <p>Het Fikfaknieuws blijft gratis en onafhankelijk. Met een kleine bijdrage van <strong style="color:#ffb86b;font-size:18px;">5 €/maand (≈1 €/uitzending)</strong> draag je direct bij aan betere afleveringen, meer continuïteit en onafhankelijkheid — zonder reclame of externe invloed.</p>
          
          <h4 style="font-size:16px;margin:16px 0 8px;color:var(--accent);">✅ Wat je steun betekent:</h4>
          <ul style="margin:8px 0 16px 18px;color:var(--muted);line-height:1.8;">
            <li><strong>Ondersteun onafhankelijk, eerlijk nieuws</strong></li>
            <li><strong>Meer tijd voor kwaliteit en research</strong></li>
            <li><strong>Directe impact: continuïteit gegarandeerd bij voldoende steun</strong></li>
          </ul>
          <p style="margin-top:16px;font-weight:700;font-size:16px;color:#ffb86b;">Dankbaar — Dirk Theuns 🙏</p>
        </div>

        <div class="support-right">
          <!-- Payconiq QR (extern) -->
          <div style="text-align:center;margin-bottom:16px;">
            <h4 style="font-size:16px;margin:0 0 12px;color:#fff;">📱 Scan & Doneer</h4>
            <img src="https://fikfak.news/wp-content/uploads/2024/12/IMG-20241210-WA0001.jpg" class="payconiq" alt="Payconiq QR-code om FikFak News te ondersteunen met donatie - Scan met je smartphone" loading="lazy" width="400" height="400">
          </div>
          <a class="btn" href="#" style="width: 100%;max-width:280px;font-size:16px;padding:14px 20px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;background:linear-gradient(135deg,var(--accent),#1e7eeb);box-shadow:0 8px 24px rgba(28,99,207,0.4);" id="support-subscribe" role="button" aria-label="Klik om donatie via bankoverschrijving te doen">🏦 Bankoverschrijving</a>
        </div>
      </div>
    </section>
    <!-- AWARD FEATURE SECTION -->
    <section id="live-edition" class="video-card" style="margin:32px auto 24px auto;max-width:var(--max-width);background:transparent;border:none;box-shadow:none;">
      <div class="award-grid" style="position:relative;width:100%;background:transparent;border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;min-height:500px;">
        <a href="https://www.tscheldt.be/speech-van-dirk-theuns-fikfak-news-nav-de-uitreiking-eerste-prijs-voor-beste-journalist-2025/" target="_blank" rel="noopener noreferrer" style="display:block;width:100%;height:100%;">
        <picture>
          <!-- Mobile/Tablet: Square award image for screens smaller than 768px -->
          <source media="(max-width: 767px)" type="image/webp" srcset="assets/images/award-beste-journalist-2025-square.webp">
          <source media="(max-width: 767px)" srcset="assets/images/award-beste-journalist-2025-square.png">
          <!-- Desktop: Landscape award image for screens 768px and larger -->
          <source media="(min-width: 768px)" type="image/webp" srcset="assets/images/Dirk-Theuns-winnaar-beste-journalist-2025-tScheldt-7.webp">
          <source media="(min-width: 768px)" srcset="assets/images/Dirk-Theuns-winnaar-beste-journalist-2025-tScheldt-7.png">
          <!-- Fallback for older browsers -->
          <img src="assets/images/Dirk-Theuns-winnaar-beste-journalist-2025-tScheldt-7.png" alt="Dirk Theuns - Winnaar Beste Journalist 2025" style="width:100%;height:100%;display:block;border-radius:8px;object-fit:contain;padding:12px;" loading="lazy" width="1600" height="960">
        </picture>
        </a>
      </div>
      <style>
        @media (max-width: 768px) {
          #live-edition .award-grid {
            min-height: 400px !important;
          }
          #live-edition .award-text {
            padding: 20px 16px !important;
          }
          #live-edition .award-text h2 {
            font-size: 20px !important;
          }
        }
        @media (max-width: 520px) {
          #live-edition .award-grid {
            min-height: 350px !important;
          }
          #live-edition .award-text {
            padding: 16px 12px !important;
          }
          #live-edition .award-text h2 {
            font-size: 18px !important;
          }
          #live-edition .award-text p {
            font-size: 13px !important;
          }
        }
      </style>
    </section>
    <!-- Standen.info Banner -->
    <div style="text-align:center;margin-bottom:20px;display:flex;justify-content:center;">
      <a href="https://www.standen.info/?ref=fikfak" target="_blank" rel="noopener noreferrer" class="newsletter-banner-link" style="display:block;max-width:90%;width:100%;">
        <picture>
          <!-- Mobile: Square banner for screens smaller than 768px -->
          <source media="(max-width: 767px)" type="image/webp" srcset="assets/images/standen_info_square.webp">
          <source media="(max-width: 767px)" srcset="assets/images/standen_info_square.png">
          <!-- Desktop: Landscape banner for screens 768px and larger -->
          <source media="(min-width: 768px)" type="image/webp" srcset="assets/images/standen_info_leaderboard.webp">
          <source media="(min-width: 768px)" srcset="assets/images/standen_info_leaderboard.png">
          <!-- Fallback for older browsers -->
          <img src="assets/images/standen_info_leaderboard.png" alt="Standen.info Banner - FikFak News Partner" style="border: solid 2px white;max-width:100%;height:auto;border-radius:8px;width:100%;display:block;" loading="lazy">
        </picture>
      </a>
    </div>

<!-- Begin Brevo Form -->
<!-- START - We recommend to place the below code in head tag of your website html  -->
<style>
  @font-face {
    font-display: block;
    font-family: Roboto;
    src: url(https://assets.brevo.com/font/Roboto/Latin/normal/normal/7529907e9eaf8ebb5220c5f9850e3811.woff2) format("woff2"), url(https://assets.brevo.com/font/Roboto/Latin/normal/normal/25c678feafdc175a70922a116c9be3e7.woff) format("woff")
  }

  @font-face {
    font-display: fallback;
    font-family: Roboto;
    font-weight: 600;
    src: url(https://assets.brevo.com/font/Roboto/Latin/medium/normal/6e9caeeafb1f3491be3e32744bc30440.woff2) format("woff2"), url(https://assets.brevo.com/font/Roboto/Latin/medium/normal/71501f0d8d5aa95960f6475d5487d4c2.woff) format("woff")
  }

  @font-face {
    font-display: fallback;
    font-family: Roboto;
    font-weight: 700;
    src: url(https://assets.brevo.com/font/Roboto/Latin/bold/normal/3ef7cf158f310cf752d5ad08cd0e7e60.woff2) format("woff2"), url(https://assets.brevo.com/font/Roboto/Latin/bold/normal/ece3a1d82f18b60bcce0211725c476aa.woff) format("woff")
  }

  #sib-container input:-ms-input-placeholder {
    text-align: left;
    font-family: Helvetica, sans-serif;
    color: #c0ccda;
  }

  #sib-container input::placeholder {
    text-align: left;
    font-family: Helvetica, sans-serif;
    color: #c0ccda;
  }

  #sib-container textarea::placeholder {
    text-align: left;
    font-family: Helvetica, sans-serif;
    color: #c0ccda;
  }

  /* Style Brevo form inputs for better readability */
  #sib-container input[type="text"],
  #sib-container input[type="email"],
  #sib-container textarea {
    background-color: rgba(20,20,35,0.9) !important;
    color: #ffffff !important;
    border: 2px solid rgba(255,255,255,0.15) !important;
    border-radius: 16px !important;
    padding: 12px 16px !important;
    font-size: 15px !important;
    min-height: 44px !important;
  }

  #sib-container input[type="text"]::placeholder,
  #sib-container input[type="email"]::placeholder,
  #sib-container textarea::placeholder {
    color: rgba(255,255,255,0.6) !important;
  }

  #sib-container input[type="text"]:focus,
  #sib-container input[type="email"]:focus,
  #sib-container textarea:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(28,99,207,0.2) !important;
    background-color: rgba(20,20,35,0.95) !important;
  }

  /* Fix autofill colors */
  #sib-container input[type="text"]:-webkit-autofill,
  #sib-container input[type="email"]:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 30px rgba(20,20,35,0.9) inset !important;
    -webkit-text-fill-color: #ffffff !important;
  }

  #sib-container a {
    text-decoration: underline;
  }

  /* Brevo form container styling */
  #sib-form-container {
    background: transparent !important;
    padding: 0 !important;
  }

  #sib-container {
    max-width: 600px !important;
    margin: 0 auto !important;
    background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    border-radius: 20px !important;
    padding: 24px 20px !important;
  }

  #sib-container--large {
    padding: 24px !important;
    color: #2BB2FC;
  }
</style>
<link rel="stylesheet" href="https://sibforms.com/forms/end-form/build/sib-styles.css">
<!--  END - We recommend to place the above code in head tag of your website html -->

<!-- START - We recommend to place the below code where you want the form in your website html  -->
<div class="sib-form" style="text-align: center;
         background-color: transparent;                                 ">
  <div id="sib-form-container" class="sib-form-container">
    <div id="error-message" class="sib-form-message-panel" style="font-size:16px; text-align:left; font-family:Helvetica, sans-serif; color:#661d1d; background-color:#ffeded; border-radius:3px; border-color:#ff4949;max-width:540px;">
      <div class="sib-form-message-panel__text sib-form-message-panel__text--center">
        <svg viewBox="0 0 512 512" class="sib-icon sib-notification__icon">
          <path d="M256 40c118.621 0 216 96.075 216 216 0 119.291-96.61 216-216 216-119.244 0-216-96.562-216-216 0-119.203 96.602-216 216-216m0-32C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm-11.49 120h22.979c6.823 0 12.274 5.682 11.99 12.5l-7 168c-.268 6.428-5.556 11.5-11.99 11.5h-8.979c-6.433 0-11.722-5.073-11.99-11.5l-7-168c-.283-6.818 5.167-12.5 11.99-12.5zM256 340c-15.464 0-28 12.536-28 28s12.536 28 28 28 28-12.536 28-28-12.536-28-28-28z" />
        </svg>
        <span class="sib-form-message-panel__inner-text">
                          Your subscription could not be saved. Please try again.
                      </span>
      </div>
    </div>
    <div></div>
    <div id="success-message" class="sib-form-message-panel" style="font-size:16px; text-align:left; font-family:Helvetica, sans-serif; color:#085229; background-color:#e7faf0; border-radius:3px; border-color:#13ce66;max-width:540px;">
      <div class="sib-form-message-panel__text sib-form-message-panel__text--center">
        <svg viewBox="0 0 512 512" class="sib-icon sib-notification__icon">
          <path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 464c-118.664 0-216-96.055-216-216 0-118.663 96.055-216 216-216 118.664 0 216 96.055 216 216 0 118.663-96.055 216-216 216zm141.63-274.961L217.15 376.071c-4.705 4.667-12.303 4.637-16.97-.068l-85.878-86.572c-4.667-4.705-4.637-12.303.068-16.97l8.52-8.451c4.705-4.667 12.303-4.637 16.97.068l68.976 69.533 163.441-162.13c4.705-4.667 12.303-4.637 16.97.068l8.451 8.52c4.668 4.705 4.637 12.303-.068 16.97z" />
        </svg>
        <span class="sib-form-message-panel__inner-text">
                          Your subscription has been successful.
                      </span>
      </div>
    </div>
    <div></div>
    <div id="sib-container" class="sib-container--large sib-container--horizontal" style="text-align:center; background-color:transparent; max-width:100%; width:100%; border-radius:2px; border-width:0px; border-color:#C0CCD9; border-style:solid; direction:ltr">
      <form id="sib-form" method="POST" action="https://540f130c.sibforms.com/serve/MUIFAGtIitgNodIRm4uBwXuYKHHhhqMVQ5l6h7b9pBrbqG4Qw4Rfcy80H_IKDr_Yi0AnWHFWGzw18D8d2WuA-dJZp1gb3VKTmSbS4eqNN9lFCDeEFhwXNEb7xF4aNSYapxjRFYqTeLfbPL4w9TWQGALCNjPKsTBABtdMFb56W5SbwN-dL3r69UibJS3Lq3WlCAMDwafsnTwy0I4M" data-type="subscription">
        <div style="padding: 12px 0;">
          <div class="sib-form-block" style="font-size:28px; text-align:center; font-weight:800; font-family:Helvetica, sans-serif; color:#ffffff; background-color:transparent; text-align:center;text-transform:uppercase;letter-spacing:0.5px;">
            <p>📧 Ontvang Fikfak Nieuws in je mailbox</p>
          </div>
        </div>
        <div style="padding: 8px 0;">
          <div class="sib-form-block sib-divider-form-block">
            <div style="border: 0; border-bottom: 2px solid var(--accent);"></div>
          </div>
        </div>
        <div style="padding: 12px 0;">
          <div class="sib-form-block" style="font-size:17px; text-align:center; font-family:Helvetica, sans-serif; color:#eeeff0; background-color:transparent; text-align:center">
            <div class="sib-text-form-block">
              <p style="line-height:1.6;margin-bottom:12px;"><strong>🎯 Mis nooit meer een aflevering!</strong></p>
              <p>Meld je aan voor onze nieuwsbrief en ontvang:</p>
              <ul style="list-style:none;padding:0;margin:12px 0;line-height:1.8;">
                <li>✅ <strong>Nieuwe afleveringen direct in je inbox</strong></li>
                <li>✅ <strong>Exclusieve updates en achtergronden</strong></li>
                <li>✅ <strong>Gratis en zonder spam</strong></li>
              </ul>
            </div>
          </div>
        </div>
        <div style="padding: 8px 0;">
          <div class="sib-input sib-form-block">
            <div class="form__entry entry_block">
              <div class="form__label-row form__label-row--horizontal">

                <div class="entry__field">
                  <input class="input " type="text" id="EMAIL" name="EMAIL" autocomplete="off" placeholder="📧 Jouw e-mailadres" data-required="true" required style="font-size:16px;padding:14px;" />
                </div>
              </div>

              <label class="entry__error entry__error--primary" style="font-size:16px; text-align:left; font-family:Helvetica, sans-serif; color:#661d1d; background-color:#ffeded; border-radius:3px; border-color:#ff4949;">
              </label>
            </div>
          </div>
        </div>
        <div style="padding: 12px 0;">
          <div class="sib-form-block" style="text-align: center">
            <button class="sib-form-block__button sib-form-block__button-with-loader" style="font-size:18px; text-align:center; font-weight:800; font-family:Helvetica, sans-serif; color:#fff; background:linear-gradient(135deg,var(--accent),#1e7eeb); border-radius:10px; border-width:0px; padding:14px 32px; min-width:240px;min-height:52px;text-transform:uppercase;letter-spacing:0.5px;box-shadow:0 8px 24px rgba(28,99,207,0.4);" form="sib-form" type="submit">
              <svg class="icon clickable__icon progress-indicator__icon sib-hide-loader-icon" viewBox="0 0 512 512" style="display:none;">
                <path d="M460.116 373.846l-20.823-12.022c-5.541-3.199-7.54-10.159-4.663-15.874 30.137-59.886 28.343-131.652-5.386-189.946-33.641-58.394-94.896-95.833-161.827-99.676C261.028 55.961 256 50.751 256 44.352V20.309c0-6.904 5.808-12.337 12.703-11.982 83.556 4.306 160.163 50.864 202.11 123.677 42.063 72.696 44.079 162.316 6.031 236.832-3.14 6.148-10.75 8.461-16.728 5.01z" />
              </svg>
              🚀 Inschrijven Nu!
            </button>
          </div>
        </div>

        <input type="text" name="email_address_check" value="" class="input--hidden">
        <input type="hidden" name="locale" value="en">
      </form>
    </div>
  </div>
</div>
<!-- END - We recommend to place the above code where you want the form in your website html  -->

<!-- START - We recommend to place the below code in footer or bottom of your website html  -->
<script>
  window.REQUIRED_CODE_ERROR_MESSAGE = 'Please choose a country code';
  window.LOCALE = 'en';
  window.EMAIL_INVALID_MESSAGE = window.SMS_INVALID_MESSAGE = "The information provided is invalid. Please review the field format and try again.";

  window.REQUIRED_ERROR_MESSAGE = "This field cannot be left blank. ";

  window.GENERIC_INVALID_MESSAGE = "The information provided is invalid. Please review the field format and try again.";




  window.translation = {
    common: {
      selectedList: '{quantity} list selected',
      selectedLists: '{quantity} lists selected',
      selectedOption: '{quantity} selected',
      selectedOptions: '{quantity} selected',
    }
  };

  var AUTOHIDE = Boolean(0);
</script>

<script defer src="https://sibforms.com/forms/end-form/build/main.js"></script>


<!-- END - We recommend to place the above code in footer or bottom of your website html  -->
<!-- End Brevo Form -->

    <footer class="site-footer" role="contentinfo">
      <div class="footer-grid">
        <div class="footer-col">
          <a href="https://www.dirktheuns.be" class="footer-link" target="_blank" rel="noopener">
            <img src="https://fikfak.news/wp-content/webp-express/webp-images/uploads/2024/10/USP-logo-Custom.jpg.webp" alt="Dirk Theuns Logo - Bezoek DirkTheuns.be voor meer informatie" class="footer-logo" loading="lazy" width="200" height="80">
            <span class="footer-link-text">www.dirktheuns.be</span>
          </a>
        </div>
        <div class="footer-col footer-credit">
          <a href="https://www.web-technics.services" target="_blank" rel="noopener noreferrer">
            <img src="https://videos.fikfak.news/wp-content/uploads/2024/11/web-technics-trans-logo-Custom.png" alt="Webtechnics Logo - Professionele webontwikkeling services" class="webtechnics-logo" loading="lazy" width="200" height="80">
          </a>
          <div class="footer-text">Met liefde gemaakt voor en door fikfakkers</div>
        </div>
        <div class="footer-col footer-social">
          <a href="#contact" class="social-link" aria-label="Contact" title="Contact">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 6.75A2.75 2.75 0 0 1 5.75 4h12.5A2.75 2.75 0 0 1 21 6.75v10.5A2.75 2.75 0 0 1 18.25 20H5.75A2.75 2.75 0 0 1 3 17.25V6.75zm2.2-.75 6.8 5.2 6.8-5.2H5.2zm13.8 2.26-6.4 4.9a1 1 0 0 1-1.2 0l-6.4-4.9v8.99c0 .41.34.75.75.75h12.5c.41 0 .75-.34.75-.75V8.26z"/></svg>
          </a>
          <a href="https://www.facebook.com/groups/vriendenvandirktheuns" target="_blank" rel="noopener" class="social-link" aria-label="Facebook" title="Facebook">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22 12.07C22 6.48 17.52 2 12 2S2 6.48 2 12.07C2 17.09 5.66 21.25 10.44 21.95v-6.99h-3.14v-2.8h3.14V9.41c0-3.1 1.84-4.8 4.66-4.8 1.35 0 2.76.24 2.76.24v3.03h-1.55c-1.53 0-2.01.96-2.01 1.95v2.32h3.42l-.55 2.8h-2.87v6.99C18.34 21.25 22 17.09 22 12.07z"/></svg>
          </a>
          <a href="https://x.com/dirktheuns" target="_blank" rel="noopener" class="social-link" aria-label="X" title="X">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <a href="https://www.youtube.com/@fikfakmaster" target="_blank" rel="noopener" class="social-link" aria-label="YouTube" title="YouTube">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M23.5 6.2s-.2-1.6-.8-2.3c-.8-.9-1.7-.9-2.1-1C16.9 2.4 12 2.4 12 2.4h-.1s-4.9 0-7.5.5c-.4.1-1.3.1-2.1 1C1.6 4.6 1.5 6.2 1.5 6.2S1 8.1 1 10v1.9c0 1.9.5 3.8.5 3.8s.2 1.6.8 2.3c.8.9 1.9.8 2.4.9 1.7.2 7 .5 7.4.5h.1s4.9 0 7.5-.5c.4-.1 1.3-.1 2.1-1 .6-.6.8-2.3.8-2.3s.5-1.9.5-3.8V10c0-1.9-.5-3.8-.5-3.8zM9.8 15.1V8.9l6.1 3.1-6.1 3.1z"/></svg>
          </a>
          <a href="https://github.com/web-technics/fikfak-news" target="_blank" rel="noopener" class="social-link" aria-label="GitHub" title="GitHub">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.865 8.17 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.34-3.369-1.34-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C19.138 20.167 22 16.418 22 12c0-5.523-4.477-10-10-10z"/></svg>
          </a>
        </div>
      </div>
    </footer>

    <!-- Contact modal -->
    <div id="contact-modal" class="modal-overlay" hidden aria-hidden="true">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="contact-modal-title">
        <button class="modal-close-contact" aria-label="Sluiten">&times;</button>
        <h3 id="contact-modal-title">📧 Contact FikFak News</h3>
        <div class="modal-intro">
          <p>Vul het formulier in en we nemen zo snel mogelijk contact met je op.</p>
        </div> 
        <form id="contact-form" class="bank-form" novalidate autocomplete="on" method="post">
          <input type="hidden" name="hp" value="" autocomplete="off">
          <label>Naam<input name="name" type="text" required aria-label="Naam" class="modal-input" autocomplete="name"></label>
          <label>E-mail<input name="email" type="email" required class="modal-input" autocomplete="email"></label>
          <label>Onderwerp<input name="subject" type="text" required class="modal-input" autocomplete="off"></label>
          <label>Bericht<textarea name="message" rows="5" required class="modal-input" autocomplete="off"></textarea></label>
          <div class="modal-actions">
            <button type="submit" class="btn newsletter-btn">Verzenden</button>
            <button type="button" class="btn newsletter-btn btn-outline" id="contact-cancel">Annuleren</button>
          </div> 
          <div id="contact-msg" class="muted" aria-live="polite" style="margin-top:8px;text-align:center"></div>
        </form>
      </div>
    </div>

    <!-- Bank transfer modal -->
    <div id="bank-modal" class="modal-overlay" hidden aria-hidden="true">
      <div class="modal" role="dialog" aria-modal="true" aria-labelledby="bank-modal-title">
        <button class="modal-close" aria-label="Sluiten">&times;</button>
        <h3 id="bank-modal-title">Bankoverschrijving - bevestig je donatie</h3>
        <div class="modal-intro">
          <p>Betaal met een overschrijving en vul de gegevens in. Na controle wordt dit bevestigd..</p>
          <div class="copy-row" style="margin-top:8px">
            <div class="copy-label" style="min-width:80px;font-weight:700">IBAN</div>
            <code id="iban-value">BE91645104919376</code>
          </div>
          <div class="copy-row">
            <div class="copy-label" style="min-width:80px;font-weight:700">Ten name van</div>
            <code id="name-value">Rucar BV</code>
          </div>
          <div class="copy-row">
            <div class="copy-label" style="min-width:80px;font-weight:700">Vermelding</div>
            <code id="note-value">donatie fikfak + uw gebruikersnaam of email</code>
          </div>
        </div> 
        <form id="bank-form" class="bank-form" novalidate autocomplete="on" method="post">
          <input type="hidden" name="hp" value="" autocomplete="off">
          <label>Transactie-ID (bijv. referentie)<input name="txid" type="text" required aria-label="Transactie ID" class="modal-input" autocomplete="transaction-id"></label>
          <label>Banknaam<input name="bank" type="text" required class="modal-input" autocomplete="off"></label>
          <label>Rekeninghouder<input name="holder" type="text" required class="modal-input" autocomplete="name"></label>
          <label>Mededeling<textarea name="note" rows="3" required class="modal-input" autocomplete="off"></textarea></label>
          <div class="modal-actions">
            <button type="submit" class="btn newsletter-btn">Verzenden</button>
            <button type="button" class="btn newsletter-btn btn-outline" id="bank-cancel">Annuleren</button>
          </div> 
          <div id="bank-msg" class="muted" aria-live="polite" style="margin-top:8px;text-align:center"></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Cookie Consent Banner -->
  <div id="cookie-banner" class="cookie-banner" role="region" aria-label="Cookie consent" aria-live="polite">
    <div class="cookie-banner-content">
      <div class="cookie-banner-text">
        <h3>🍪 Cookie Voorkeuren</h3>
        <p>We gebruiken cookies voor analytics en functionaliteit. Lees ons <a href="/privacy-policy.html" target="_blank" rel="noopener">privacybeleid</a> voor meer informatie.</p>
      </div>
      <div class="cookie-banner-buttons">
        <button id="cookie-reject" class="cookie-btn cookie-btn-outline" aria-label="Alleen essentiële cookies accepteren">
          ❌ Alleen essentieel
        </button>
        <button id="cookie-accept" class="cookie-btn cookie-btn-primary" aria-label="Alle cookies accepteren">
          ✅ Alles accepteren
        </button>
        <button id="cookie-manage" class="cookie-btn cookie-btn-outline" aria-label="Cookie instellingen beheren">
          ⚙️ Instellingen
        </button>
      </div>
    </div>
  </div>

  <!-- Cookie Settings Modal -->
  <div id="cookie-modal" class="cookie-modal" role="dialog" aria-modal="true" aria-labelledby="cookie-modal-title">
    <div class="cookie-modal-overlay"></div>
    <div class="cookie-modal-content">
      <button class="cookie-modal-close" aria-label="Sluiten">&times;</button>
      <h2 id="cookie-modal-title">Cookie Instellingen</h2>
      
      <div class="cookie-category">
        <label class="cookie-checkbox">
          <input type="checkbox" id="cookie-essential" checked disabled>
          <span class="checkmark"></span>
          <strong>Essentiële Cookies</strong> (Altijd actief)
        </label>
        <p class="cookie-description">Nodig voor basisfunctionaliteit, beveiligde formulieren en sessiegegevens.</p>
      </div>

      <div class="cookie-category">
        <label class="cookie-checkbox">
          <input type="checkbox" id="cookie-analytics">
          <span class="checkmark"></span>
          <strong>Analytics Cookies</strong>
        </label>
        <p class="cookie-description">Google Analytics - Helpt ons begrijpen hoe bezoekers de site gebruiken (anoniem).</p>
      </div>

      <div class="cookie-category">
        <label class="cookie-checkbox">
          <input type="checkbox" id="cookie-marketing">
          <span class="checkmark"></span>
          <strong>Marketing Cookies</strong>
        </label>
        <p class="cookie-description">YouTube, sociale media - Functioneert voor embedded video's en social features.</p>
      </div>

      <div class="cookie-modal-buttons">
        <button id="cookie-save" class="btn" style="background:linear-gradient(135deg,var(--accent),#1e7eeb);">💾 Instellingen Opslaan</button>
        <button id="cookie-modal-close" class="btn btn-outline">Annuleren</button>
      </div>
    </div>
  </div>

  <script>
    // Cookie Management System (GDPR Compliant)
    class CookieManager {
      constructor() {
        this.COOKIE_NAME = 'fikfak_cookie_consent';
        this.COOKIE_EXPIRY = 365 * 24 * 60 * 60 * 1000; // 1 year
        this.preferences = this.getPreferences();
        this.banner = document.getElementById('cookie-banner');
        this.modal = document.getElementById('cookie-modal');
        this.init();
      }

      getPreferences() {
        const cookie = this.getCookie(this.COOKIE_NAME);
        if (cookie) {
          try {
            return JSON.parse(cookie);
          } catch (e) {
            return { essential: true, analytics: false, marketing: false };
          }
        }
        return null;
      }

      getCookie(name) {
        const nameEQ = name + '=';
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
          let c = cookies[i].trim();
          if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length);
        }
        return null;
      }

      setCookie(name, value) {
        const date = new Date();
        date.setTime(date.getTime() + this.COOKIE_EXPIRY);
        const expires = 'expires=' + date.toUTCString();
        document.cookie = name + '=' + value + '; ' + expires + '; path=/; Secure; SameSite=Strict';
      }

      savePreferences(prefs) {
        this.preferences = prefs;
        this.setCookie(this.COOKIE_NAME, JSON.stringify(prefs));
        this.applyPreferences(prefs);
      }

      applyPreferences(prefs) {
        // Load analytics if consent given
        if (prefs.analytics && typeof gtag !== 'undefined') {
          gtag('consent', 'update', {
            'analytics_storage': 'granted'
          });
        } else if (!prefs.analytics && typeof gtag !== 'undefined') {
          gtag('consent', 'update', {
            'analytics_storage': 'denied'
          });
        }
      }

      init() {
        const elements = {
          banner: this.banner,
          modal: this.modal,
          acceptBtn: document.getElementById('cookie-accept'),
          rejectBtn: document.getElementById('cookie-reject'),
          manageBtn: document.getElementById('cookie-manage'),
          saveBtn: document.getElementById('cookie-save'),
          closeBtn: document.getElementById('cookie-modal-close'),
          modalCloseBtn: this.modal?.querySelector('.cookie-modal-close'),
          essentialCheck: document.getElementById('cookie-essential'),
          analyticsCheck: document.getElementById('cookie-analytics'),
          marketingCheck: document.getElementById('cookie-marketing')
        };

        // If preferences exist, hide banner
        if (this.preferences) {
          if (elements.banner) elements.banner.style.display = 'none';
          this.applyPreferences(this.preferences);
        } else {
          if (elements.banner) elements.banner.style.display = 'flex';
        }

        // Event listeners
        if (elements.acceptBtn) {
          elements.acceptBtn.addEventListener('click', () => {
            this.savePreferences({ essential: true, analytics: true, marketing: true });
            if (elements.banner) elements.banner.style.display = 'none';
          });
        }

        if (elements.rejectBtn) {
          elements.rejectBtn.addEventListener('click', () => {
            this.savePreferences({ essential: true, analytics: false, marketing: false });
            if (elements.banner) elements.banner.style.display = 'none';
          });
        }

        if (elements.manageBtn) {
          elements.manageBtn.addEventListener('click', () => {
            this.openModal(elements);
          });
        }

        if (elements.saveBtn) {
          elements.saveBtn.addEventListener('click', () => {
            this.saveFromModal(elements);
          });
        }

        if (elements.closeBtn) {
          elements.closeBtn.addEventListener('click', () => {
            this.closeModal(elements);
          });
        }

        if (elements.modalCloseBtn) {
          elements.modalCloseBtn.addEventListener('click', () => {
            this.closeModal(elements);
          });
        }

        if (elements.modal) {
          elements.modal.addEventListener('click', (e) => {
            if (e.target === elements.modal) this.closeModal(elements);
          });
        }
      }

      openModal(elements) {
        if (elements.modal) {
          elements.modal.classList.add('active');
          // Load current preferences
          if (elements.analyticsCheck) elements.analyticsCheck.checked = this.preferences?.analytics || false;
          if (elements.marketingCheck) elements.marketingCheck.checked = this.preferences?.marketing || false;
        }
      }

      closeModal(elements) {
        if (elements.modal) elements.modal.classList.remove('active');
      }

      saveFromModal(elements) {
        const prefs = {
          essential: true,
          analytics: elements.analyticsCheck?.checked || false,
          marketing: elements.marketingCheck?.checked || false
        };
        this.savePreferences(prefs);
        this.closeModal(elements);
        if (elements.banner) elements.banner.style.display = 'none';
      }
    }

    // Initialize cookie manager on page load
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        new CookieManager();
      });
    } else {
      new CookieManager();
    }
  </script>

  <script>
    // Wait for DOM to be ready before executing video code
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initializeVideoPlayer);
    } else {
      initializeVideoPlayer();
    }

    function initializeVideoPlayer() {
      const CHANNEL_HANDLE = 'UCkhqAfTIr2U5RU9ziyGfu_A';
      const YOUTUBE_API_KEY = ''; 

      const raw = CHANNEL_HANDLE.replace(/^@/, '').trim();
      const isChannelId = /^UC[A-Za-z0-9_-]{20,}$/i.test(raw);
      const feedParam = isChannelId ? 'channel_id=' + raw : 'user=' + raw;
      const cacheBust = '&_cb=' + Date.now();
      const rssUrl = 'https://www.youtube.com/feeds/videos.xml?' + feedParam + cacheBust;
      const rssToJson = 'https://api.rss2json.com/v1/api.json?rss_url=' + encodeURIComponent(rssUrl);
      const allOriginsRaw = 'https://api.allorigins.win/raw?url=' + encodeURIComponent(rssUrl);
      const RECENT_COUNT = 5; // aantal recente uploads in de zijbalk

      // DOM
      const playerContainer = document.getElementById('player');
      const titleEl = document.getElementById('video-title');
      const dateEl = document.getElementById('video-date');
      const statusNote = document.getElementById('status-note');
      const recentList = document.getElementById('recent-list');
      const watchOnYouTube = document.getElementById('watch-on-youtube');
      const subscribeBtn = document.getElementById('subscribe-btn');
      const playerWrap = document.getElementById('player-wrap');
      const shareNativeBtn = document.getElementById('share-native-btn');
      const shareWhatsApp = document.getElementById('share-whatsapp');
      const shareTelegram = document.getElementById('share-telegram');
      const shareX = document.getElementById('share-x');
      const shareFacebook = document.getElementById('share-facebook');
      const shareCopyBtn = document.getElementById('share-copy-btn');
      const shareFeedback = document.getElementById('share-feedback');
      const basePageUrl = <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
      const initialVideoData = {
        videoId: <?php echo json_encode($selectedVideoId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        title: <?php echo json_encode($selectedTitle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        published: <?php echo json_encode($publishedIso, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
      };
      const requestedVideoId = (() => {
        try {
          const value = new URL(window.location.href).searchParams.get('v') || '';
          return /^[A-Za-z0-9_-]{11}$/.test(value) ? value : '';
        } catch (error) {
          return '';
        }
      })();

      // subscribe-link
      if (isChannelId) {
        subscribeBtn.href = 'https://www.youtube.com/channel/' + raw;
        subscribeBtn.setAttribute('aria-label', 'Abonneer op kanaal ' + raw);
      } else {
        subscribeBtn.href = 'https://www.youtube.com/@' + raw;
        subscribeBtn.setAttribute('aria-label', 'Abonneer op @' + raw);
      }

      // helpers
      function toNLDate(pub) {
        return pub ? new Date(pub).toLocaleString('nl-NL') : '';
      }

      function extractVideoIdFromLink(link) {
        if (!link) return null;
        try {
          const url = new URL(link);
          return url.searchParams.get('v') || (link.match(/\/embed\/([^?]+)/) || [])[1] || null;
        } catch(e) {
          const m = link.match(/v=([^&]+)/);
          return m ? m[1] : null;
        }
      }

      function buildSharePageUrl(videoId) {
        return videoId ? basePageUrl + '?v=' + encodeURIComponent(videoId) : basePageUrl;
      }

      function setShareFeedback(message) {
        if (!shareFeedback) {
          return;
        }
        shareFeedback.textContent = message || '';
      }

      async function copyShareUrl(url) {
        if (!url) {
          return;
        }

        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(url);
          return;
        }

        const fallbackInput = document.createElement('input');
        fallbackInput.type = 'text';
        fallbackInput.value = url;
        fallbackInput.setAttribute('readonly', 'readonly');
        fallbackInput.style.position = 'absolute';
        fallbackInput.style.left = '-9999px';
        document.body.appendChild(fallbackInput);
        fallbackInput.select();
        const copied = document.execCommand('copy');
        document.body.removeChild(fallbackInput);

        if (!copied) {
          throw new Error('copy_failed');
        }
      }

      function updateShareActions(videoId, title) {
        if (!videoId) {
          return;
        }

        const pageUrl = buildSharePageUrl(videoId);
        const cleanTitle = (title || 'FikFak News uitzending').trim();
        const shareTitle = '📰 ' + cleanTitle + ' | FikFak News';
        const shareText = 'Bekijk deze FikFak News uitzending: ' + cleanTitle;
        const combined = shareText + ' ' + pageUrl;

        if (shareWhatsApp) {
          shareWhatsApp.href = 'https://wa.me/?text=' + encodeURIComponent(combined);
        }
        if (shareTelegram) {
          shareTelegram.href = 'https://t.me/share/url?url=' + encodeURIComponent(pageUrl) + '&text=' + encodeURIComponent(shareText);
        }
        if (shareX) {
          shareX.href = 'https://x.com/intent/tweet?url=' + encodeURIComponent(pageUrl) + '&text=' + encodeURIComponent(shareText);
        }
        if (shareFacebook) {
          shareFacebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(pageUrl);
        }
        if (shareNativeBtn) {
          shareNativeBtn.dataset.url = pageUrl;
          shareNativeBtn.dataset.title = shareTitle;
          shareNativeBtn.dataset.text = shareText;
        }
      }

      if (shareNativeBtn && typeof navigator.share !== 'function') {
        shareNativeBtn.style.display = 'none';
      }

      if (shareNativeBtn) {
        shareNativeBtn.addEventListener('click', async () => {
          const pageUrl = shareNativeBtn.dataset.url || window.location.href;
          const shareTitle = shareNativeBtn.dataset.title || document.title;
          const shareText = shareNativeBtn.dataset.text || 'Bekijk deze FikFak News uitzending';

          try {
            if (typeof navigator.share === 'function') {
              await navigator.share({
                title: shareTitle,
                text: shareText,
                url: pageUrl
              });
              setShareFeedback('Succesvol gedeeld.');
            } else {
              await copyShareUrl(pageUrl);
              setShareFeedback('Link gekopieerd.');
            }
          } catch (error) {
            if (error && error.name === 'AbortError') {
              return;
            }
            setShareFeedback('Delen mislukt. Gebruik Kopieer link.');
          }
        });
      }

      if (shareCopyBtn) {
        shareCopyBtn.addEventListener('click', async () => {
          const pageUrl = (shareNativeBtn && shareNativeBtn.dataset.url) ? shareNativeBtn.dataset.url : window.location.href;
          try {
            await copyShareUrl(pageUrl);
            setShareFeedback('Link gekopieerd naar klembord.');
          } catch (error) {
            setShareFeedback('Kopieren mislukt.');
          }
        });
      }

      function syncBrowserUrl(videoId) {
        const nextUrl = new URL(window.location.href);
        if (videoId) {
          nextUrl.searchParams.set('v', videoId);
        } else {
          nextUrl.searchParams.delete('v');
        }
        window.history.replaceState({ videoId }, '', nextUrl.toString());
      }

      function resolveRequestedVideo(latestData) {
        if (!requestedVideoId) {
          return null;
        }

        if (latestData && latestData.videoId === requestedVideoId) {
          return latestData;
        }

        if (latestData && Array.isArray(latestData.recentVideos)) {
          const matched = latestData.recentVideos.find((video) => video && video.videoId === requestedVideoId);
          if (matched) {
            return matched;
          }
        }

        if (initialVideoData.videoId === requestedVideoId) {
          return initialVideoData;
        }

        return {
          videoId: requestedVideoId,
          title: initialVideoData.title || 'FikFak News Uitzending',
          published: initialVideoData.published || new Date().toISOString()
        };
      }

      function addRecentItem(videoId, title, published) {
        if (!videoId) return;
        const div = document.createElement('div');
        div.className = 'thumb';
        div.setAttribute('role', 'button');
        div.setAttribute('tabindex', '0');
        div.setAttribute('aria-label', 'Klik om video af te spelen: ' + title);
        div.innerHTML = '<img loading="lazy" src="https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg" alt="Video thumbnail: ' + title.replace(/"/g, '&quot;') + '">' +
                        '<div class="tmeta"><div class="t-title">' + (title.length>60?title.slice(0,57)+'...':title) + '</div>' +
                        '<div class="t-time">' + toNLDate(published) + '</div></div>';
        const clickHandler = () => {
          loadVideoById(videoId, title, published);
          window.scrollTo({top:0, behavior:'smooth'});
        };
        div.addEventListener('click', clickHandler);
        div.addEventListener('keypress', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            clickHandler();
          }
        });
        recentList.appendChild(div);
      }

      let ytPlayer = null;
      let currentVideoId = null;

      // Detect Safari on Mac
      function isSafariMac() {
        const ua = navigator.userAgent;
        const isSafari = /Safari/.test(ua) && !/Chrome|Chromium|Edge|Firefox|OPR/.test(ua);
        const isMac = /Macintosh|Mac OS X/.test(ua);
        return isSafari && isMac;
      }

      // Simple iframe embed for Safari Mac (more reliable than YouTube IFrame API)
      function createSimpleIframeEmbed(videoId, title, published) {
        playerContainer.innerHTML = '';
        statusNote.textContent = 'Video wordt geladen...';
        
        const iframe = document.createElement('iframe');
        iframe.id = 'yt-embed-iframe';
        iframe.width = '100%';
        iframe.height = '100%';
        iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=0&rel=0&modestbranding=1&fs=1&controls=1&enablejsapi=1&origin=' + encodeURIComponent(window.location.origin);
        iframe.frameBorder = '0';
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen');
        iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        iframe.setAttribute('title', 'FikFak News: ' + title);
        iframe.setAttribute('aria-label', 'Video speler voor: ' + title);
        iframe.setAttribute('playsinline', '');
        iframe.style.position = 'absolute';
        iframe.style.top = '0';
        iframe.style.left = '0';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        
        // Add load and error handlers
        let loadTimeout = setTimeout(() => {
          console.warn('Video iframe took too long to load, may have connectivity issues');
          statusNote.innerHTML = '<div class="warning" style="color: #ff9800;">Video laadt langzaam... Even geduld. <a href="https://www.youtube.com/watch?v=' + videoId + '" target="_blank" rel="noopener" style="color: #ff9800; text-decoration: underline;">Open op YouTube</a></div>';
        }, 8000);
        
        iframe.addEventListener('load', () => {
          clearTimeout(loadTimeout);
          statusNote.textContent = 'Video geladen';
          console.log('✅ Video iframe loaded successfully');
          // Hide status note after a moment
          setTimeout(() => {
            if (statusNote.textContent === 'Video geladen') {
              statusNote.textContent = '';
            }
          }, 2000);
        });
        
        iframe.addEventListener('error', (e) => {
          clearTimeout(loadTimeout);
          console.error('Video iframe failed to load:', e);
          statusNote.innerHTML = '<div class="error">Video kon niet worden geladen. <a href="https://www.youtube.com/watch?v=' + videoId + '" target="_blank" rel="noopener">Open op YouTube</a></div>';
        });
        
        playerContainer.appendChild(iframe);
        
        titleEl.textContent = title || 'Nieuwste video';
        dateEl.textContent = toNLDate(published);
        if (watchOnYouTube) watchOnYouTube.href = 'https://www.youtube.com/watch?v=' + videoId;
        currentVideoId = videoId;
        
        // Update social meta tags
        updateSocialMetaTags(videoId, title, published);
      }

      // Fallback UI wanneer insluiten geblokkeerd is
      function showEmbedBlockedFallback(videoId, title, published) {
        if (ytPlayer && typeof ytPlayer.destroy === 'function') {
          ytPlayer.destroy();
          ytPlayer = null;
        }
        playerContainer.innerHTML = ''; // reset
        const thumbUrl = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg';
        const fallback = document.createElement('div');
        fallback.className = 'fallback-thumb';
        fallback.style.backgroundImage = 'linear-gradient(rgba(0,0,0,0.25), rgba(0,0,0,0.45)), url("' + thumbUrl + '")';
        fallback.innerHTML = '<div class="fallback-overlay"><div style="font-weight:700;margin-bottom:6px;">' + (title || 'Video niet beschikbaar voor insluiten') + '</div>' +
                             '<div style="margin-bottom:8px;color:var(--muted);font-size:13px;">' + toNLDate(published) + '</div>' +
                             '<a class="btn" href="https://www.youtube.com/watch?v=' + videoId + '" target="_blank" rel="noopener">Open op YouTube</a>' +
                             '</div>';
        playerContainer.appendChild(fallback);
        titleEl.textContent = title || 'Video (insluiten geblokkeerd)';
        dateEl.textContent = toNLDate(published);
        if (watchOnYouTube) watchOnYouTube.href = 'https://www.youtube.com/watch?v=' + videoId;
        statusNote.innerHTML = '<div class="error">Insluiten van deze video lijkt te zijn geblokkeerd door de uploader (eigenaarsbeperking). Open op YouTube om te bekijken.</div>';
      }

      // Als insluiten werkt, vullen we de speler via de YouTube IFrame API
      function createYTPlayer(videoId, title, published) {
        // Use simple iframe embed (much faster and more reliable than YT.Player API)
        createSimpleIframeEmbed(videoId, title, published);
      }

      // laad de IFrame API asynchroon
      function loadYouTubeIframeAPI(cb) {
        if (window.YT && window.YT.Player) {
          cb();
          return;
        }
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        tag.async = true;
        window.onYouTubeIframeAPIReady = function() {
          cb();
        };
        document.head.appendChild(tag);
      }

      // Update social media meta tags for sharing
      function updateSocialMetaTags(videoId, title, published) {
        const embedUrl = 'https://www.youtube.com/embed/' + videoId;
        // Use hqdefault for best compatibility and add stable version key per episode.
        const publishedStamp = published ? String(published).replace(/[^0-9]/g, '').slice(0, 14) : '';
        const thumbBaseUrl = 'https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg';
        const thumbUrl = publishedStamp ? thumbBaseUrl + '?v=' + encodeURIComponent(publishedStamp) : thumbBaseUrl;
        const pageUrl = buildSharePageUrl(videoId);
        const shareTitle = '📰 ' + title + ' | FikFak News';
        const shareDescription = '🎯 ' + title + ' - Bekijk de nieuwste FikFak News uitzending!';
        
        syncBrowserUrl(videoId);
        updateShareActions(videoId, title);

        let metaTitle = document.querySelector('meta[name="title"]');
        if (metaTitle) metaTitle.setAttribute('content', shareTitle);

        let metaDescription = document.querySelector('meta[name="description"]');
        if (metaDescription) metaDescription.setAttribute('content', shareDescription);

        let canonical = document.querySelector('link[rel="canonical"]');
        if (canonical) canonical.setAttribute('href', pageUrl);

        let ogUrl = document.querySelector('meta[property="og:url"]');
        if (ogUrl) ogUrl.setAttribute('content', pageUrl);

        // Update og:title
        let ogTitle = document.querySelector('meta[property="og:title"]');
        if (ogTitle) ogTitle.setAttribute('content', shareTitle);
        
        // Update og:description
        let ogDesc = document.querySelector('meta[property="og:description"]');
        if (ogDesc) ogDesc.setAttribute('content', shareDescription);
        
        // Update og:image
        let ogImage = document.querySelector('meta[property="og:image"]');
        if (ogImage) ogImage.setAttribute('content', thumbUrl);
        let ogImageSecure = document.querySelector('meta[property="og:image:secure_url"]');
        if (ogImageSecure) ogImageSecure.setAttribute('content', thumbUrl);
        
        // Update image type and dimensions for YouTube thumbnail
        let ogImageType = document.querySelector('meta[property="og:image:type"]');
        if (ogImageType) ogImageType.setAttribute('content', 'image/jpeg');
        let ogImageWidth = document.querySelector('meta[property="og:image:width"]');
        if (ogImageWidth) ogImageWidth.setAttribute('content', '480');
        let ogImageHeight = document.querySelector('meta[property="og:image:height"]');
        if (ogImageHeight) ogImageHeight.setAttribute('content', '360');
        let ogImageAlt = document.querySelector('meta[property="og:image:alt"]');
        if (ogImageAlt) ogImageAlt.setAttribute('content', title + ' - FikFak News');

        let articlePublished = document.querySelector('meta[property="article:published_time"]');
        if (articlePublished && published) articlePublished.setAttribute('content', published);
        let articleModified = document.querySelector('meta[property="article:modified_time"]');
        if (articleModified && published) articleModified.setAttribute('content', published);
        let ogUpdated = document.querySelector('meta[property="og:updated_time"]');
        if (ogUpdated && published) ogUpdated.setAttribute('content', published);
        let ogVideoRelease = document.querySelector('meta[property="og:video:release_date"]');
        if (ogVideoRelease && published) ogVideoRelease.setAttribute('content', published);
        
        // Update og:video
        let ogVideo = document.querySelector('meta[property="og:video"]');
        if (ogVideo) ogVideo.setAttribute('content', embedUrl);
        let ogVideoUrl = document.querySelector('meta[property="og:video:url"]');
        if (ogVideoUrl) ogVideoUrl.setAttribute('content', embedUrl);
        let ogVideoSecure = document.querySelector('meta[property="og:video:secure_url"]');
        if (ogVideoSecure) ogVideoSecure.setAttribute('content', embedUrl);
        
        // Update Twitter card
        let twitterUrl = document.querySelector('meta[name="twitter:url"]');
        if (twitterUrl) twitterUrl.setAttribute('content', pageUrl);
        let twitterTitle = document.querySelector('meta[name="twitter:title"]');
        if (twitterTitle) twitterTitle.setAttribute('content', shareTitle);
        let twitterDesc = document.querySelector('meta[name="twitter:description"]');
        if (twitterDesc) twitterDesc.setAttribute('content', shareDescription);
        let twitterImage = document.querySelector('meta[name="twitter:image"]');
        if (twitterImage) twitterImage.setAttribute('content', thumbUrl);
        let twitterImageSrc = document.querySelector('meta[name="twitter:image:src"]');
        if (twitterImageSrc) twitterImageSrc.setAttribute('content', thumbUrl);
        let twitterImageAlt = document.querySelector('meta[name="twitter:image:alt"]');
        if (twitterImageAlt) twitterImageAlt.setAttribute('content', title + ' - FikFak News');
        let twitterPlayer = document.querySelector('meta[name="twitter:player"]');
        if (twitterPlayer) twitterPlayer.setAttribute('content', embedUrl);
        
        // Update page title
        document.title = shareTitle;
        
        console.log('✅ Social meta tags updated for:', title);
        console.log('   Thumbnail:', thumbUrl);
      }

      // Hoofdlogica: haal feed op en maak speler
      function initFromFeed() {
        statusNote.textContent = 'Feed ophalen...';

        // First, try to load from latest-video.json (updated by cron job) with longer timeout
        const fetchWithTimeout = (url, timeout = 8000) => {
          return Promise.race([
            fetch(url),
            new Promise((_, reject) => 
              setTimeout(() => reject(new Error('latest-video.json timeout')), timeout)
            )
          ]);
        };
        
        fetchWithTimeout('latest-video.json?' + Date.now())
          .then(r => r.ok ? r.json() : null)
          .then(latestData => {
            if (latestData && latestData.videoId) {
              console.log('✅ Loaded from latest-video.json:', latestData.videoId);
              const selectedVideo = resolveRequestedVideo(latestData) || latestData;
              
              // Load main video
              loadMainVideo(selectedVideo.videoId, selectedVideo.title, selectedVideo.published);
              
              // Populate sidebar from cached recentVideos if available
              if (latestData.recentVideos && Array.isArray(latestData.recentVideos) && latestData.recentVideos.length > 0) {
                console.log('✅ Populating sidebar from cached data (' + latestData.recentVideos.length + ' videos)');
                recentList.innerHTML = '';
                let count = 0;
                for (const video of latestData.recentVideos) {
                  if (video.videoId && video.videoId !== selectedVideo.videoId) {
                    addRecentItem(video.videoId, video.title, video.published);
                    count++;
                    if (count >= RECENT_COUNT) break;
                  }
                }
                statusNote.textContent = '';
              } else {
                // Fallback: fetch RSS for sidebar if recentVideos not in cache
                console.log('⚠️ No recentVideos in cache, fetching RSS for sidebar');
                fetchRSSForSidebar();
              }
            } else {
              console.warn('latest-video.json returned invalid data, using RSS');
              // Fallback to RSS feed
              fetchRSSFeed();
            }
          })
          .catch(err => {
            console.warn('latest-video.json not available or timed out, using RSS:', err.message);
            fetchRSSFeed();
          });
      }

      // Load main video
      function loadMainVideo(videoId, title, published) {
        loadYouTubeIframeAPI(() => createYTPlayer(videoId, title, published));
      }

      // Fetch RSS feed for sidebar only
      function fetchRSSForSidebar() {
        const handleItems = (items) => {
          if (!items || items.length === 0) {
            console.warn('No items received for sidebar');
            return;
          }
          recentList.innerHTML = '';
          items.slice(0, RECENT_COUNT).forEach(it => {
            const vid = extractVideoIdFromLink(it.link) || (it.guid && it.guid.split(':').pop());
            if (vid) {
              addRecentItem(vid, it.title, it.pubDate);
            }
          });
          console.log('✅ Sidebar loaded with', items.slice(0, RECENT_COUNT).length, 'videos');
        };

        console.log('📥 Fetching RSS data for sidebar...');
        fetchRSSData()
          .then(handleItems)
          .catch(err => {
            console.error('❌ Could not load recent videos for sidebar:', err);
            // Show a message in the sidebar area
            if (recentList) {
              recentList.innerHTML = '<div style="padding: 12px; color: var(--muted); font-size: 13px; text-align: center;">Recent videos konden niet worden geladen.<br><a href="https://www.youtube.com/@fikfakmaster/videos" target="_blank" rel="noopener" style="color: var(--primary); text-decoration: underline;">Bekijk op YouTube</a></div>';
            }
          });
      }

      // Fetch RSS feed (full flow)
      function fetchRSSFeed() {
        const handleItems = (items) => {
          if (!items || items.length === 0) throw new Error('Geen items in feed');
          
          const first = items[0];
          const id = extractVideoIdFromLink(first.link) || (first.guid && first.guid.split(':').pop());
          if (!id) throw new Error('Kon video-id niet uit feed halen');
          
          console.log('📺 RSS feed: loading', first.title);

          // recente items (limit) - skip first video (already in player)
          recentList.innerHTML = '';
          items.slice(1, RECENT_COUNT + 1).forEach(it => {
            const vid = extractVideoIdFromLink(it.link) || (it.guid && it.guid.split(':').pop());
            addRecentItem(vid, it.title, it.pubDate);
          });

          // Load video
          loadYouTubeIframeAPI(() => createYTPlayer(id, first.title, first.pubDate));
        };

        fetchRSSData().then(handleItems).catch(err => {
          console.error('RSS feed failed after all retries:', err);
          statusNote.innerHTML = '<div class="error">Kon de feed niet ophalen. <a href="https://www.youtube.com/@fikfakmaster" target="_blank" rel="noopener">Bezoek het YouTube-kanaal direct</a></div>';
        });
      }

      // Helper function to fetch RSS data with retry logic
      function fetchRSSData(retryCount = 0, maxRetries = 3) {
        console.log(`🔄 fetchRSSData called (attempt ${retryCount + 1}/${maxRetries + 1})`);
        
        // Helper: add timeout to fetch with longer timeout for reliability
        const fetchWithTimeout = (url, timeout = 10000) => {
          return Promise.race([
            fetch(url),
            new Promise((_, reject) => 
              setTimeout(() => reject(new Error('Timeout')), timeout)
            )
          ]);
        };

        const fetchXmlFeed = () => {
          console.log('🔷 Trying AllOrigins XML feed...');
          return fetchWithTimeout(allOriginsRaw, 10000)
            .then(r => {
              if (!r.ok) throw new Error('allorigins ophalen mislukt: ' + r.status);
              return r.text();
            })
            .then(xmlText => {
              const parser = new DOMParser();
              const xml = parser.parseFromString(xmlText, 'application/xml');
              const entries = Array.from(xml.querySelectorAll('entry'));
              if (entries.length === 0) throw new Error('Geen items in XML-feed');
              console.log('✅ AllOrigins returned', entries.length, 'entries');
              return entries.map(e => {
                const link = e.querySelector('link')?.getAttribute('href') || '';
                const title = e.querySelector('title')?.textContent || '';
                const pub = e.querySelector('published')?.textContent || '';
                return { link, title, pubDate: pub };
              });
            });
        };

        const fetchJsonFeed = () => {
          console.log('🔶 Trying RSS2JSON feed...');
          return fetchWithTimeout(rssToJson, 10000)
            .then(r => {
              if (!r.ok) throw new Error('rss2json ophalen mislukt: ' + r.status);
              return r.json();
            })
            .then(data => {
              if (!data || !data.items) throw new Error('Geen items in JSON-feed');
              console.log('✅ RSS2JSON returned', data.items.length, 'items');
              return data.items;
            });
        };

        // Race both APIs - whoever responds first wins
        return Promise.race([
          fetchXmlFeed().catch(err => {
            console.info('AllOrigins failed:', err.message);
            throw err;
          }),
          fetchJsonFeed().catch(err => {
            console.info('RSS2JSON failed:', err.message);
            throw err;
          })
        ])
          .catch(() => {
            // If both fail in race, try them sequentially as fallback
            console.info('Both APIs slow/failed, trying sequential fallback...');
            return fetchJsonFeed()
              .catch(err => {
                return fetchXmlFeed();
              });
          })
          .catch(err => {
            // Retry logic with exponential backoff
            if (retryCount < maxRetries) {
              const delay = Math.min(1000 * Math.pow(2, retryCount), 5000);
              console.warn(`Retry ${retryCount + 1}/${maxRetries} after ${delay}ms...`);
              return new Promise(resolve => setTimeout(resolve, delay))
                .then(() => fetchRSSData(retryCount + 1, maxRetries));
            }
            throw err;
          });
      }

      // Publieke functie voor klikken op recente items
      window.loadVideoById = function(videoId, title, published) {
        if (!videoId) return;
        
        // Track video selection
        if (typeof gtag !== 'undefined') {
          gtag('event', 'select_content', {
            'content_type': 'video',
            'content_id': videoId,
            'event_category': 'engagement',
            'event_label': title || 'Video'
          });
        }
        
        // For Safari Mac, use simple iframe embed
        if (isSafariMac()) {
          createSimpleIframeEmbed(videoId, title, published);
          window.scrollTo({top:0, behavior:'smooth'});
          return;
        }
        
        if (ytPlayer && typeof ytPlayer.loadVideoById === 'function') {
          try {
            ytPlayer.loadVideoById(videoId);
            titleEl.textContent = title || 'Video';
            dateEl.textContent = toNLDate(published);
            if (watchOnYouTube) watchOnYouTube.href = 'https://www.youtube.com/watch?v=' + videoId;
            statusNote.textContent = 'Geselecteerde video afspelen';
            currentVideoId = videoId;
            updateSocialMetaTags(videoId, title || 'FikFak News Uitzending', published);
          } catch(e) {
            loadYouTubeIframeAPI(() => createYTPlayer(videoId, title, published));
          }
        } else {
          loadYouTubeIframeAPI(() => createYTPlayer(videoId, title, published));
        }
      };

      // init
      initFromFeed();

      // console info (ontwikkelaar)
      console.info('FikFak landing: feed', rssUrl, 'isChannelId=', isChannelId);

      // (Optional) Ensure layout re-evaluates on rotate/resize
      window.addEventListener('resize', () => {
        // force reflow for player container if needed
        const wrap = document.querySelector('.iframe-wrap');
        if (wrap) wrap.style.paddingTop = getComputedStyle(wrap).paddingTop;
      });

      // Newsletter signup (client-side)
      const newsletterForm = document.getElementById('newsletter-form');
      const newsletterMsg = document.getElementById('newsletter-msg');
      const newsletterCard = document.getElementById('newsletter');
      if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          const submitBtn = this.querySelector('button[type="submit"]');
          const emailInput = this.querySelector('input[name="EMAIL"]') || this.querySelector('input[type="email"]');
          const email = emailInput ? emailInput.value.trim() : '';
          if (newsletterMsg) { newsletterMsg.classList.remove('error','success'); newsletterMsg.textContent = ''; }
          if (newsletterCard) newsletterCard.classList.remove('submitted');

          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            if (newsletterMsg) { newsletterMsg.textContent = 'Voer een geldig e-mailadres in.'; newsletterMsg.classList.add('error'); }
            if (emailInput) emailInput.focus();
            return;
          }

          if (submitBtn) submitBtn.disabled = true;

          const action = (this.action || '').trim();

          // Test mode: placeholder Mailchimp action — simulate submission locally
          if (!action || action.includes('example.list-manage.com') || action.includes('YOUR_U') || action.includes('YOUR_ID')) {
            console.info('Newsletter submission simulated (test mode). Action:', action, 'payload:', { EMAIL: email });
            this.reset();
            if (newsletterMsg) {
              newsletterMsg.textContent = '🎉 Testmodus: inschrijving gesimuleerd — vervang de form-actie met je Mailchimp URL om echte inschrijvingen op te slaan.';
              newsletterMsg.classList.add('success');
            }
            if (newsletterCard) newsletterCard.classList.add('submitted');
            if (submitBtn) submitBtn.disabled = false;
            return;
          }

          // Try to POST via fetch (may be subject to CORS when using Mailchimp embedded form)
          try {
            const response = await fetch(action, { method: 'POST', body: new FormData(this), mode: 'cors' });
            if (response.ok || response.type === 'opaque') {
              this.reset();
              if (newsletterMsg) { newsletterMsg.textContent = '✅ Dankjewel! Je inschrijving is ontvangen.'; newsletterMsg.classList.add('success'); }
              if (newsletterCard) newsletterCard.classList.add('submitted');
              
              // Track newsletter signup
              if (typeof gtag !== 'undefined') {
                gtag('event', 'sign_up', {
                  'event_category': 'newsletter',
                  'event_label': 'newsletter_subscription',
                  'value': 1
                });
              }
            } else {
              // fallback attempt (no-cors) — this will send the request but gives no readable response
              await fetch(action, { method: 'POST', body: new FormData(this), mode: 'no-cors' });
              this.reset();
              if (newsletterMsg) { newsletterMsg.textContent = '✅ Dankjewel! (Controleer je Mailchimp-lijst — respons was niet leesbaar.)'; newsletterMsg.classList.add('success'); }
              if (newsletterCard) newsletterCard.classList.add('submitted');
            }
          } catch (err) {
            console.warn('Nieuwsbrief verzending mislukt:', err);
            if (newsletterMsg) { newsletterMsg.textContent = 'Er is iets misgegaan; inschrijving gesimuleerd. Controleer je internetverbinding of gebruik de embedded Mailchimp-actie.'; newsletterMsg.classList.add('error'); }
          } finally {
            if (submitBtn) submitBtn.disabled = false;
          }
        });
      }

      // Contact modal interaction
      const contactModal = document.getElementById('contact-modal');
      const contactForm = document.getElementById('contact-form');
      const contactMsg = document.getElementById('contact-msg');
      const contactCancel = document.getElementById('contact-cancel');
      const contactModalClose = contactModal && contactModal.querySelector('.modal-close-contact');
      const contactLinks = document.querySelectorAll('a[href="#contact"]');

      function openContactModal() {
        if (!contactModal) return;
        contactModal.hidden = false;
        contactModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const first = contactModal.querySelector('.modal-input');
        if (first) first.focus();
        contactModal.addEventListener('keydown', trapContactFocus);
        
        // Track contact modal open
        if (typeof gtag !== 'undefined') {
          gtag('event', 'view_item', {
            'event_category': 'engagement',
            'event_label': 'contact_modal_opened'
          });
        }
      }

      function closeContactModal() {
        if (!contactModal) return;
        contactModal.hidden = true;
        contactModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        contactModal.removeEventListener('keydown', trapContactFocus);
      }

      function trapContactFocus(e) {
        if (e.key !== 'Tab') return;
        const focusable = contactModal.querySelectorAll('a, button, input, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length -1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }

      contactLinks.forEach(link => {
        link.addEventListener('click', function(e){ e.preventDefault(); openContactModal(); });
      });
      if (contactCancel) contactCancel.addEventListener('click', closeContactModal);
      if (contactModalClose) contactModalClose.addEventListener('click', closeContactModal);
      contactModal && contactModal.addEventListener('click', function(e){ if (e.target === contactModal) closeContactModal(); });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && contactModal && !contactModal.hidden) closeContactModal(); });

      if (contactForm) {
        contactForm.addEventListener('submit', function(e){
          e.preventDefault();
          if (contactMsg) { contactMsg.textContent = ''; contactMsg.classList.remove('success','error'); }
          const formData = new FormData(this);
          const name = (formData.get('name') || '').toString().trim();
          const email = (formData.get('email') || '').toString().trim();
          const subject = (formData.get('subject') || '').toString().trim();
          const message = (formData.get('message') || '').toString().trim();
          if (!name || !email || !subject || !message) {
            if (contactMsg) { contactMsg.textContent = 'Vul alle velden in.'; contactMsg.classList.add('error'); }
            return;
          }
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          if (contactMsg) { contactMsg.textContent = 'Verzenden...'; contactMsg.classList.remove('success','error'); }
          
          const endpoint = 'php/send_contact.php';
          fetch(endpoint, { method: 'POST', body: formData })
            .then(async res => {
              let data = { success: false, message: 'Onbekende fout' };
              try { data = await res.json(); } catch (err) { }
              if (res.ok && data.success) {
                this.reset();
                if (contactMsg) { contactMsg.textContent = 'Bedankt! Je bericht is verzonden. We nemen zo snel mogelijk contact op.'; contactMsg.classList.add('success'); }
                
                // Track successful contact form submission
                if (typeof gtag !== 'undefined') {
                  gtag('event', 'form_submission', {
                    'event_category': 'engagement',
                    'event_label': 'contact_form',
                    'value': 1
                  });
                }
                
                setTimeout(closeContactModal, 2500);
              } else {
                const msg = data && data.message ? data.message : 'Er is iets misgegaan bij het verzenden; probeer het later.';
                if (contactMsg) { contactMsg.textContent = msg; contactMsg.classList.add('error'); }
              }
            })
            .catch(err => {
              console.error('Contact form submit error:', err);
              if (contactMsg) { contactMsg.textContent = 'Er is iets misgegaan bij het verzenden; probeer het later.'; contactMsg.classList.add('error'); }
            })
            .finally(() => { if (submitBtn) submitBtn.disabled = false; });
        });
      }

      // Bank transfer modal interaction
      const bankModal = document.getElementById('bank-modal');
      const bankForm = document.getElementById('bank-form');
      const bankMsg = document.getElementById('bank-msg');
      const supportBtn = document.getElementById('support-subscribe');
      const bankCancel = document.getElementById('bank-cancel');
      const modalClose = bankModal && bankModal.querySelector('.modal-close');

      function openBankModal() {
        if (!bankModal) return;
        bankModal.hidden = false;
        bankModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const first = bankModal.querySelector('.modal-input');
        if (first) first.focus();
        bankModal.addEventListener('keydown', trapFocus);
        
        // Track bank modal open
        if (typeof gtag !== 'undefined') {
          gtag('event', 'view_item', {
            'event_category': 'donation',
            'event_label': 'bank_modal_opened'
          });
        }
      }

      function closeBankModal() {
        if (!bankModal) return;
        bankModal.hidden = true;
        bankModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (supportBtn) supportBtn.focus();
        bankModal.removeEventListener('keydown', trapFocus);
      }

      function trapFocus(e) {
        if (e.key !== 'Tab') return;
        const focusable = bankModal.querySelectorAll('a, button, input, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length -1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }

      if (supportBtn) {
        supportBtn.addEventListener('click', function(e){ e.preventDefault(); openBankModal(); });
      }
      if (bankCancel) bankCancel.addEventListener('click', closeBankModal);
      if (modalClose) modalClose.addEventListener('click', closeBankModal);
      bankModal && bankModal.addEventListener('click', function(e){ if (e.target === bankModal) closeBankModal(); });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && bankModal && !bankModal.hidden) closeBankModal(); });

      if (bankForm) {
        bankForm.addEventListener('submit', function(e){
          e.preventDefault();
          if (bankMsg) { bankMsg.textContent = ''; bankMsg.classList.remove('success','error'); }
          const formData = new FormData(this);
          const txid = (formData.get('txid') || '').toString().trim();
          const bank = (formData.get('bank') || '').toString().trim();
          const holder = (formData.get('holder') || '').toString().trim();
          const note = (formData.get('note') || '').toString().trim();
          if (!txid || !bank || !holder || !note) {
            if (bankMsg) { bankMsg.textContent = 'Vul alle velden in.'; bankMsg.classList.add('error'); }
            return;
          }
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) submitBtn.disabled = true;
          // send to server-side PHP endpoint
          if (bankMsg) { bankMsg.textContent = 'Verzenden...'; bankMsg.classList.remove('success','error'); }
          
          const endpoint = 'php/send_bank.php';
          fetch(endpoint, { method: 'POST', body: formData })
            .then(async res => {
              let data = { success: false, message: 'Onbekende fout' };
              try { data = await res.json(); } catch (err) { }
              if (res.ok && data.success) {
                this.reset();
                if (bankMsg) { bankMsg.textContent = 'Dankjewel! We hebben je betaling ontvangen en zullen deze controleren.'; bankMsg.classList.add('success'); }
                
                // Track successful bank transfer submission
                if (typeof gtag !== 'undefined') {
                  gtag('event', 'form_submission', {
                    'event_category': 'donation',
                    'event_label': 'bank_transfer',
                    'value': 1
                  });
                }
                
                setTimeout(closeBankModal, 2500);
              } else {
                const msg = data && data.message ? data.message : 'Er is iets misgegaan bij het verzenden; probeer het later.';
                if (bankMsg) { bankMsg.textContent = msg; bankMsg.classList.add('error'); }
              }
            })
            .catch(err => {
              console.error('Bank form submit error:', err);
              if (bankMsg) { bankMsg.textContent = 'Er is iets misgegaan bij het verzenden; probeer het later.'; bankMsg.classList.add('error'); }
            })
            .finally(() => { if (submitBtn) submitBtn.disabled = false; });
        });
      }
    }
  </script>

  <!-- Loading Screen Script -->
  <script>
    window.addEventListener('load', function() {
      const loadingScreen = document.getElementById('loading-screen');
      if (loadingScreen) {
        // Display for 1.5 seconds before fading out
        setTimeout(function() {
          loadingScreen.classList.add('loaded');
          // Remove from DOM after fade out
          setTimeout(function() {
            loadingScreen.remove();
          }, 600);
        }, 1500);
      }
    });
  </script>

</body>
</html>