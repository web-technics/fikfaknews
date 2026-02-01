# 📰 FikFak News - Landing Page

Modern, fast, and secure landing page for FikFak News (@fikfakmaster) - Alternative news by journalist Dirk Theuns, winner of "Beste Journalist 2025".

## 🚀 Features

- **YouTube Integration**: Automatic latest video feed from channel
- **Responsive Design**: Mobile-first, optimized for all devices
- **Performance**: Lazy loading, GZIP compression, browser caching
- **Security**: HTTPS enforced, CSP headers, HSTS, rate limiting
- **Accessibility**: WCAG 2.1 AA compliant with ARIA labels
- **SEO Optimized**: Meta tags, Open Graph, structured data
- **GDPR Compliant**: Cookie consent banner with granular controls
- **Analytics**: Google Analytics 4 with event tracking

## 📁 Structure

```
go.fikfak.news/
├── index.html              # Main landing page
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

1. **Clone the repository:**
   ```bash
   git clone https://github.com/YOUR_USERNAME/go-fikfak-news.git
   cd go-fikfak-news
   ```

2. **Configure PHP forms:**
   - Edit `php/send_contact.php` and `php/send_bank.php`
   - Set your email address for form submissions

3. **Deploy:**
   - Upload to your web server's document root
   - Ensure Apache mod_rewrite, mod_headers, mod_deflate are enabled
   - HTTPS/SSL certificate required

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
