# 🚀 Quick Setup Guide

## Get Your Website Updated in 10-20 Seconds! ⚡

Instead of waiting 5-15 minutes for YouTube's RSS feed, your website can update within **10-20 seconds** of your video going live!

## Setup (5 minutes):

### 1. Get FREE YouTube API Key
- Go to: https://console.cloud.google.com/apis/credentials
- Create project → Enable "YouTube Data API v3" → Create API Key
- Copy the key

### 2. Add API Key
Edit `update-latest-video.php` line 13:
```php
$youtubeApiKey = 'PASTE_YOUR_API_KEY_HERE';
```

### 3. Set Up Cron Job
```bash
crontab -e
```

Add this (checks every 30 seconds on Sunday mornings):
```cron
* 7-8 * * 0 sleep 0 && /usr/bin/php /home/fikfak-go/htdocs/go.fikfak.news/update-latest-video.php >> /home/fikfak-go/htdocs/go.fikfak.news/cron.log 2>&1
* 7-8 * * 0 sleep 30 && /usr/bin/php /home/fikfak-go/htdocs/go.fikfak.news/update-latest-video.php >> /home/fikfak-go/htdocs/go.fikfak.news/cron.log 2>&1
```

### 4. Test It
```bash
cd /home/fikfak-go/htdocs/go.fikfak.news
php update-latest-video.php
```

Should see: `✅ Got video from API`

## Done! 🎉

Now every Sunday:
- **8:00:00** - Video goes public
- **8:00:20** - Website automatically updated!
- **Email subscribers** get the link and video is ready instantly!

Full details in: [CRON_SETUP.md](CRON_SETUP.md)
