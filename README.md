# FikFak News Site

Public website and member platform for FikFak News. This repository now covers both the public landing page and the account system used on `go.fikfak.news`, including legacy WordPress member sync, member login, admin management, and password reset flows.

## Features

- Public homepage with latest YouTube video, social metadata, analytics, and donation/contact flows
- Member registration, login, logout, profile, and dashboard pages
- Admin dashboard with user metrics and legacy sync health checks
- Legacy WordPress and ARMember import/sync into the new `users` table
- Transparent login support for imported WordPress password hashes
- Password reset flow for migrated accounts that do not yet have a usable password
- Cron-based sync tooling for keeping legacy signups aligned with the new platform

## Project Structure

```text
fikfak-news/
├── index.php                  # Public homepage and social sharing entry point
├── update-latest-video.php    # Updates latest-video.json from YouTube
├── latest-video.json          # Cached latest and recent YouTube videos
├── privacy-policy.html        # Privacy policy
├── .htaccess                  # Apache/LiteSpeed rewrite, cache, and security rules
├── assets/                    # Shared images, CSS, and favicons
├── archive/                   # Archive-facing static assets/pages
└── php/
    ├── login.php              # Member login with WordPress hash compatibility
    ├── register.php           # New member registration
    ├── logout.php             # Session logout
    ├── forgot_pw.php          # Reset request flow
    ├── set_new_pw.php         # Set a new password from reset token
    ├── dashboard.php          # Member dashboard
    ├── profile.php            # Member profile editor
    ├── admin_dashboard.php    # Admin metrics and sync status overview
    ├── user_manager.php       # User list and admin actions
    ├── reset_pw.php           # Admin-generated reset link helper
    ├── import_users.php       # Manual legacy import/upsert tool
    ├── sync_users.php         # Cron-safe recurring legacy sync script
    ├── send_contact.php       # Contact form handler
    ├── send_bank.php          # Bank transfer/support form handler
    └── wp-password-compat.php # WordPress legacy password compatibility
```

## Stack

- PHP 8.x
- MySQL / MariaDB
- Apache or LiteSpeed with `.htaccess`
- YouTube RSS feed for latest video discovery
- Google Analytics 4

## Account System

The account platform is intended to replace member access on the old WordPress site while subscriptions may still remain there temporarily.

- Existing WordPress members can usually log in with their existing email and password
- Successful login with a legacy WordPress hash upgrades that password storage to a current bcrypt hash
- Users imported without a usable password must use the reset flow once
- Admins can generate reset links from the user manager instead of handing out random passwords

## Legacy Sync Flow

The repository contains two legacy migration tools:

- `php/import_users.php`
  Use for manual import/upsert runs when you want to bring over older member data in bulk.

- `php/sync_users.php`
  Use for recurring cron sync so new signups that still happen on the old WordPress site are copied into the new system.

The sync writes `php/sync_meta.json`, which is displayed in the admin dashboard so you can see last run time, inserted/updated/skipped counts, and errors.

## Setup

### Basic Deployment

1. Clone the repository.

```bash
git clone https://github.com/web-technics/fikfak-news.git
cd fikfak-news
```

2. Configure your database connection values in the PHP account files.

3. Configure mail/form handling in `php/send_contact.php` and `php/send_bank.php` if needed.

4. Deploy to your web root and ensure PHP, MySQL, and Apache/LiteSpeed rewrite support are enabled.

5. Make sure HTTPS is valid for the domain you serve.

### Video Update Cron

To keep social metadata aligned with the latest broadcast, run `update-latest-video.php` on a schedule that fits your publishing cadence.

Example:

```bash
*/10 * * * 0 8-9 /usr/bin/php /path/to/update-latest-video.php
```

### Legacy Member Sync Cron

To keep the new account database aligned with the old WordPress site:

```bash
0 * * * * /usr/bin/php /path/to/php/sync_users.php >> /path/to/php/sync_users.log 2>&1
```

After first deployment, run it once manually to bootstrap the sync immediately:

```bash
php /path/to/php/sync_users.php
```

## Admin Area

The admin area currently provides:

- Total users and recent signup counts
- Accounts without passwords
- WordPress-compatible hash counts versus bcrypt counts
- Legacy email coverage metrics across WordPress and ARMember tables
- Last sync status and runtime metadata
- User search, edit, delete, and reset-link actions

## Security Notes

- HTTPS enforcement and security headers are configured in `.htaccess`
- Login supports legacy WordPress hashes without exposing them
- Password reset uses token-based links with expiry
- Admin access is role-based, not username-name based

## Links

- Website: https://go.fikfak.news
- Legacy WordPress site: https://fikfak.news
- YouTube: https://www.youtube.com/@fikfakmaster
- Facebook: https://www.facebook.com/groups/vriendenvandirktheuns
- X: https://x.com/dirktheuns

## License

© 2026 FikFak News / Dirk Theuns. All rights reserved.

Status: Production active
Last updated: April 2026
