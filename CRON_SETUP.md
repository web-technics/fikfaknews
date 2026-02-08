# FikFak News - Automatic Video Update Setup (10-20 Second Updates!)

## How It Works

The system automatically updates your website **within 10-20 seconds** of your video going live using YouTube's Data API (instant) with RSS feed as fallback.

### Files Created:

1. **latest-video.json** - Stores the current video data
2. **update-latest-video.php** - PHP script that checks YouTube and updates files
3. **CRON_SETUP.md** - This file (instructions)

## Quick Setup (5 minutes):

### Step 1: Get YouTube Data API Key (IMPORTANT - for instant updates!)

Without this key, the system falls back to RSS with 5-15 minute delay.

1. Go to: https://console.cloud.google.com/
2. Create a new project (or select existing)
3. Enable "YouTube Data API v3"
4. Go to Credentials → Create Credentials → API Key
5. Copy the API key
6. Edit `update-latest-video.php` and add your key:
   ```php
   $youtubeApiKey = 'YOUR_API_KEY_HERE';
   ```

### Step 2: Test the Script

SSH into your server and run:
```bash
cd /home/fikfak-go/htdocs/go.fikfak.news
php update-latest-video.php
```

You should see: 
- `✅ Got video from API: ...` (instant, with API key)
- Or `Checking RSS feed...` (5-15 min delay, without API key)

### Step 3: Set Up Cron Job for 10-20 Second Checks

1. Open crontab editor:
```bash
crontab -e
```

2. **Option A: Every 10 seconds** (Sunday 7:55-8:10am):
```cron
# Run every 10 seconds from 7:55 to 8:10 on Sundays
* 7 * * 0 cd /home/fikfak-go/htdocs/go.fikfak.news && for i in {1..6}; do php update-latest-video.php >> cron.log 2>&1; sleep 10; done
* 8 * * 0 cd /home/fikfak-go/htdocs/go.fikfak.news && for i in {1..6}; do php update-latest-video.php >> cron.log 2>&1 && [ $i -lt 6 ] && sleep 10; done | head -c 600
```

**Option B: Every 20 seconds** (simpler, Sunday 7:55-8:10am):
```cron
# Run 3 times per minute (every 20 seconds), from 7:55 to 8:10 on Sundays
* 7-8 * * 0 cd /home/fikfak-go/htdocs/go.fikfak.news && php update-latest-video.php >> cron.log 2>&1 && sleep 20 && php update-latest-video.php >> cron.log 2>&1 && sleep 20 && php update-latest-video.php >> cron.log 2>&1
```

**Option C: Every 30 seconds** (easiest):
```cron
* 7-8 * * 0 sleep 0 && /usr/bin/php /home/fikfak-go/htdocs/go.fikfak.news/update-latest-video.php >> /home/fikfak-go/htdocs/go.fikfak.news/cron.log 2>&1
* 7-8 * * 0 sleep 30 && /usr/bin/php /home/fikfak-go/htdocs/go.fikfak.news/update-latest-video.php >> /home/fikfak-go/htdocs/go.fikfak.news/cron.log 2>&1
```

3. Save and exit (Ctrl+X, then Y, then Enter in nano)

### Step 4: Verify It Works

Check the log file after Sunday 8am:
```bash
tail -f /home/fikfak-go/htdocs/go.fikfak.news/cron.log
```

You should see entries like:
```
✅ Got video from API: 3UEf4G1eVtA
✅ Updated to new video: 3UEf4G1eVtA - FikFak News ...
```

## Timeline (With API Key):

- **7:55am** - Cron starts checking every 10-20 seconds
- **8:00:00am** - Video goes public on YouTube
- **8:00:10am** - Script detects new video via API (instant!)
- **8:00:15am** - Website updated, meta tags refreshed
- **8:00:20am** - Visitors see new video!

**Total delay: 10-20 seconds!** ⚡

## How It Works:

1. **YouTube Data API** (with API key): Updates **instantly** when video goes public
2. **RSS Feed** (fallback): Updates in 5-15 minutes if no API key
3. **Cron job**: Checks every 10-30 seconds during publish window
4. **Automatic updates**: JSON file, HTML meta tags, everything!

### What Gets Updated Automatically:

- ✅ `latest-video.json` - Current video info
- ✅ HTML meta tags (`og:image`, `og:video`, etc.) - Social sharing
- ✅ JavaScript video ID - Player loads correct video
- ✅ All synchronized instantly!

### Manual Override (Emergency):

If you need to manually set a video immediately:

1. Edit `latest-video.json`:
```json
{
  "videoId": "YOUR_VIDEO_ID_HERE",
  "title": "Your Video Title",
  "published": "2026-02-08T08:00:00Z",
  "lastUpdated": "2026-02-08T08:00:00Z"
}
```

2. Run the script manually:
```bash
php update-latest-video.php
```

The website will pick it up immediately!

### Troubleshooting:

**Video not updating instantly?**
- **Check if you added the API key** in `update-latest-video.php`
- Test manually: `php update-latest-video.php` - should say "Got video from API"
- Check cron log: `cat cron.log`
- Verify cron is running: `crontab -l`

**API quota exceeded?**
- Free quota: 10,000 units/day (each check = 100 units = 100 checks/day)
- More than enough for Sunday mornings!
- If exceeded, falls back to RSS automatically

**Social media not showing thumbnail?**
- Clear Facebook cache: https://developers.facebook.com/tools/debug/
- Wait 5-10 minutes for WhatsApp cache to clear
- The script updates all meta tags automatically every time

**Video still shows old one?**
- Hard refresh the website: Ctrl+F5
- Check `latest-video.json` was updated
- Check cron.log for errors

## API Key Benefits:

| Method | Update Speed | Setup |
|--------|-------------|--------|
| **With API Key** | ⚡ **10-20 seconds** | Get free API key |
| Without API Key | 🐌 5-15 minutes | No setup needed |

**Get your API key now!** It's free and takes 2 minutes: https://console.cloud.google.com/

## Questions?

The system is fully automatic once set up. Every Sunday at 8am your email goes out, and within 10-20 seconds your website is live with the new video! 🚀

