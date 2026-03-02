<?php
// Load latest video info
$latest = json_decode(file_get_contents(__DIR__ . '/latest-video.json'), true);
$videoId = $latest['videoId'] ?? 'Rh2QPLyh-FU';
$title = $latest['title'] ?? 'FikFak News — Alternatief Nieuws van @fikfakmaster';
$description = '🎯 Bekijk de nieuwste FikFak News uitzending! Onafhankelijk nieuws, actuele analyse en kritische blik op media en politiek door journalist Dirk Theuns. Winnaar Beste Journalist 2025. 🔥';
$ogImage = "https://img.youtube.com/vi/$videoId/hqdefault.jpg";
$ogVideo = "https://www.youtube.com/embed/$videoId";
$published = $latest['published'] ?? date('c');
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
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="title" content="<?= htmlspecialchars($title) ?>" />
  <meta name="description" content="<?= htmlspecialchars($description) ?>" />
  <meta name="keywords" content="FikFak News, fikfakmaster, Dirk Theuns, alternatief nieuws, onafhankelijk nieuws, nieuws analyse, Nederlandse media, actueel, politiek, nieuwsuitzending, fikfak.news, beste journalist 2025, kritisch nieuws, media-analyse, onafhankelijke journalistiek" />
  <meta name="author" content="Dirk Theuns (@fikfakmaster)" />
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
  <meta name="language" content="Dutch" />
  <meta name="revisit-after" content="2 days" />
  <!-- Open Graph / Facebook / WhatsApp -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://go.fikfak.news/" />
  <meta property="og:site_name" content="FikFak News" />
  <meta property="og:title" content="<?= htmlspecialchars($title) ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($description) ?>" />
  <meta property="og:image" content="<?= $ogImage ?>" />
  <meta property="og:image:secure_url" content="<?= $ogImage ?>" />
  <meta property="og:image:type" content="image/jpeg" />
  <meta property="og:image:width" content="480" />
  <meta property="og:image:height" content="360" />
  <meta property="og:image:alt" content="FikFak News - Nieuwste uitzending" />
  <meta property="og:locale" content="nl_NL" />
  <meta property="og:locale:alternate" content="nl_BE" />
  <meta property="og:video" content="<?= $ogVideo ?>" />
  <meta property="og:video:type" content="text/html" />
  <meta property="og:video:width" content="1280" />
  <meta property="og:video:height" content="720" />
  <meta property="article:publisher" content="https://go.fikfak.news/" />
  <meta property="article:author" content="https://www.dirktheuns.be/" />
  <meta property="article:section" content="Nieuws" />
  <meta property="article:tag" content="Nieuws" />
  <meta property="article:tag" content="Politiek" />
  <meta property="article:tag" content="Media" />
  <meta property="article:modified_time" content="<?= $published ?>" />
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <!-- ...existing code... -->
</head>
<body>
<!-- ...existing code... -->
</body>
</html>
