# 📰 FikFak News - Landing Page

Modern, fast, and secure landing page for FikFak News (@fikfakmaster) - Alternative news by journalist Dirk Theuns, winner of "Beste Journalist 2025".

## 🚀 Features

- **YouTube Integration**: Automatic latest video feed from channel
- **Responsive Design**: Mobile-first, optimized for all devices
- **Performance**: Lazy loading, GZIP compression, browser caching
- **Security**: HTTPS enforced, CSP headers, HSTS, rate limiting
- **Accessibility**: WCAG 2.1 AA compliant with ARIA labels
- **SEO Optimized**: Comprehensive meta tags, Open Graph, structured data, Twitter Cards
- **Social Sharing**: Dynamic meta tags ensure latest video always displays on Facebook, WhatsApp, Twitter, Telegram
- **GDPR Compliant**: Cookie consent banner with granular controls
- **Analytics**: Google Analytics 4 with event tracking

## 📁 Structure

```
go.fikfak.news/
├── index.php               # Main landing page (dynamic latest video + social metadata)
├── latest-video.json       # Cached latest/recent YouTube videos
├── update-latest-video.php # Cron updater for latest-video.json
├── privacy-policy.html     # Privacy policy page
├── .htaccess              # Apache configuration (caching, security)
├── assets/
│   ├── images/            # Logo, banners, images
│   └── favicons/          # Favicon and web manifest
├── php/
│   ├── send_contact.php   # Contact form handler
│   └── send_bank.php      # Bank transfer form handler
└── archive/               # Archive section (if needed)
```

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 8.x (for forms)
- **Server**: Apache/LiteSpeed with .htaccess
- **APIs**: YouTube RSS Feed, Google Analytics 4
- **Forms**: Brevo (Sendinblue) newsletter integration

## ⚙️ Setup

### Basic Installation
1. **Clone the repository:**
   ```bash
   git clone https://github.com/web-technics/fikfak-news.git
   cd fikfak-news
   ```

2. **Configure PHP forms:**
   - Edit `php/send_contact.php` and `php/send_bank.php`
   - Set your email address for form submissions

3. **Deploy:**
   - Upload to your web server's document root
   - Ensure Apache mod_rewrite, mod_headers, mod_deflate are enabled
   - HTTPS/SSL certificate required

### Automatic Video Updates (Cron)
To keep the latest video metadata current for social sharing:

1. **Set up a cron job** to run `update-latest-video.php` periodically:
   ```bash
   */10 * * * 0 8-9 /usr/bin/php /path/to/update-latest-video.php
   ```
   This updates every 10 minutes on Sunday mornings (8-9am CET) before the 8am broadcast.

2. **YouTube API Key (Optional):**
   - Add your YouTube Data API key to `update-latest-video.php` for faster updates
   - Falls back to YouTube RSS feed if API key is missing or fails

3. **Verify operation:**
   - Check `latest-video.json` file was updated with your latest video
   - File should contain: videoId, title, published date, and recentVideos array

## 🔒 Security Features

- HTTPS enforcement with HSTS
- Content Security Policy (CSP)
- X-Frame-Options, X-XSS-Protection headers
- Rate limiting on forms (60-second cooldown)
- Honeypot spam protection
- Secure cookie flags (SameSite=Strict)

## 📊 Analytics

Google Analytics 4 tracking includes:
- Video selection events
- Form submissions
- Newsletter signups
- Contact/donation modal interactions

## 📱 Social Sharing Optimization

Every broadcast automatically optimizes for social media sharing:

**Dynamic Meta Tags:**
- Automatically detects latest video from `latest-video.json`
- Serves correct Open Graph tags (og:title, og:description, og:image, og:video)
- Includes video-specific metadata: duration, release date, tags
- Supports all major video metadata standards

**Platform-Specific Support:**
- **Facebook & WhatsApp**: Rich preview with video thumbnail and description
- **Twitter**: Summary card with large image
- **Telegram**: Video link preview with metadata
- **Discord**: Embedded video preview with proper dimensions
- **LinkedIn**: Article-style preview with video link

**Auto-Redirect for Social Crawlers:**
- Root URL (`/`) redirects social crawlers to latest video URL with `?v=[videoId]`
- Ensures consistent metadata in social shares
- Happens server-side before JavaScript execution (crawler-friendly)

**Implementation Details:**
- Latest video stored in `latest-video.json` (updated by `update-latest-video.php` cron)
- Client-side meta tag updates when users manually switch videos
- All timestamps in ISO 8601 format (article:published_time, og:video:release_date)
- Thumbnail URLs from YouTube CDN (high resolution: 480x360px)
- Embed URLs use YouTube's iframe API

## 📊 Analytics

## 🍪 GDPR Compliance

- Cookie consent banner on first visit
- Privacy policy page with full disclosure
- Granular cookie controls (Essential, Analytics, Marketing)
- Compliant with GDPR, Dutch AVG, and Belgian privacy law

## 🚀 Performance

- **Load Time**: < 2 seconds
- **Video Feed**: 1-5 seconds (parallel API race)
- **Caching**: 1-year for assets, 1-hour for HTML
- **Compression**: GZIP enabled
- **Lazy Loading**: Images load on demand

## 📝 License

© 2026 FikFak News / Dirk Theuns. All rights reserved.

## 🔗 Links

- **Website**: https://go.fikfak.news
- **Old WordPress Site**: https://fikfak.news (legacy subscriptions only)
- **YouTube**: [@fikfakmaster](https://www.youtube.com/@fikfakmaster)
- **Facebook**: [Vrienden van Dirk Theuns](https://www.facebook.com/groups/vriendenvandirktheuns)
- **X/Twitter**: [@dirktheuns](https://x.com/dirktheuns)

## 📧 Contact

For technical issues or inquiries, use the contact form on the website.

---

**Status**: ✅ Production Ready | Last Updated: February 2026
