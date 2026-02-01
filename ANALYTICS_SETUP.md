# Google Analytics Setup Guide

## 📊 Analytics Implementation

Google Analytics 4 (GA4) has been integrated into your FikFak News website to track user behavior and identify areas for improvement.

## 🔧 Configuration Required

**IMPORTANT:** Replace the placeholder measurement ID with your actual Google Analytics ID:

1. Go to [Google Analytics](https://analytics.google.com/)
2. Create a new GA4 property for `go.fikfak.news`
3. Get your Measurement ID (format: `G-Z9XCT4V4RG`)
4. In [index.html](index.html), find line 7-8 and replace `G-XXXXXXXXXX` with your actual ID in both locations:

```javascript
// Line 8
<script async src="https://www.googletagmanager.com/gtag/js?id=G-Z9XCT4V4RG"></script>

// Line 13
gtag('config', 'G-Z9XCT4V4RG', {
```

## 📈 Events Being Tracked

### 1. **Newsletter Signups** (`sign_up`)
- **Category:** newsletter
- **Label:** newsletter_subscription
- **Trigger:** When user successfully subscribes to newsletter
- **Value:** 1

### 2. **Video Selections** (`select_content`)
- **Category:** engagement
- **Content Type:** video
- **Content ID:** YouTube video ID
- **Label:** Video title
- **Trigger:** When user clicks on a video thumbnail to play

### 3. **Contact Form Submissions** (`form_submission`)
- **Category:** engagement
- **Label:** contact_form
- **Value:** 1
- **Trigger:** When contact form is successfully submitted

### 4. **Bank Transfer Donations** (`form_submission`)
- **Category:** donation
- **Label:** bank_transfer
- **Value:** 1
- **Trigger:** When bank transfer form is successfully submitted

### 5. **Bank Modal Opens** (`view_item`)
- **Category:** donation
- **Label:** bank_modal_opened
- **Trigger:** When user clicks "🏦 Bankoverschrijving" button

### 6. **Contact Modal Opens** (`view_item`)
- **Category:** engagement
- **Label:** contact_modal_opened
- **Trigger:** When user clicks contact links

## 🔐 Privacy Features

The implementation includes privacy-friendly features:

- **IP Anonymization:** `'anonymize_ip': true`
- **Secure Cookies:** `'cookie_flags': 'SameSite=None;Secure'`
- **HTTPS Only:** Analytics only loads over secure connections

## 📊 Key Metrics to Monitor

Once configured, you can track:

### Engagement Metrics
- Video view patterns (which videos get the most attention)
- Modal interaction rates (contact & donation)
- Newsletter signup conversion rate

### Donation Funnel
1. Users who click "Steun Fikfak News" section
2. Users who open bank transfer modal
3. Users who complete donation form

### Content Performance
- Which recent videos get selected most often
- Time spent on page
- Bounce rate vs engagement rate

## 🎯 Recommended GA4 Reports

### Custom Reports to Create:

1. **Conversion Funnel:**
   - Page View → Bank Modal Open → Form Submission

2. **Content Engagement:**
   - Video Selection Rate
   - Most Popular Videos (by content_id)

3. **Newsletter Growth:**
   - Newsletter signups over time
   - Conversion rate (views to signups)

4. **Form Analytics:**
   - Contact form submissions
   - Bank transfer submissions
   - Form abandonment rate

## 🔍 Enhanced Tracking Opportunities

### Additional Events You Could Add:

1. **Scroll Depth Tracking:** See how far users scroll
2. **Payconiq QR Code Clicks:** Track QR code image clicks
3. **Social Media Link Clicks:** Track Facebook, X, YouTube clicks
4. **External Link Clicks:** Track clicks to dirktheuns.be, shop, etc.
5. **Video Play/Pause Events:** Using YouTube IFrame API events
6. **Video Completion Rate:** Track how much of videos users watch

### Example: Social Media Tracking
```javascript
// Add this to social media links
document.querySelectorAll('.social-link').forEach(link => {
  link.addEventListener('click', function() {
    const platform = this.getAttribute('aria-label');
    if (typeof gtag !== 'undefined') {
      gtag('event', 'click', {
        'event_category': 'social_media',
        'event_label': platform,
        'value': 1
      });
    }
  });
});
```

## 🛡️ GDPR Compliance Considerations

### Current Status:
- IP anonymization is enabled
- Secure cookies are set

### To Be Fully Compliant:
1. **Cookie Consent Banner:** Consider adding a consent management solution
2. **Privacy Policy:** Update your privacy policy to mention Google Analytics
3. **Data Processing Agreement:** Sign Google's data processing terms
4. **User Rights:** Provide opt-out mechanism if required in your jurisdiction

### Quick Cookie Consent Implementation:
You can use simple solutions like:
- [Cookiebot](https://www.cookiebot.com/)
- [OneTrust](https://www.onetrust.com/)
- Or a custom consent banner

## 🚀 Testing Your Setup

### Before Going Live:
1. Replace `G-XXXXXXXXXX` with your real Measurement ID
2. Open the website in a browser
3. Open browser DevTools → Network tab
4. Look for requests to `google-analytics.com` or `googletagmanager.com`
5. In GA4 dashboard, check "Realtime" report to see your visit

### Test Each Event:
- ✅ Newsletter signup
- ✅ Video selection
- ✅ Contact form submission
- ✅ Bank transfer form submission
- ✅ Modal opens (bank & contact)

## 📞 Support

For Google Analytics setup help:
- [Google Analytics Help Center](https://support.google.com/analytics)
- [GA4 Setup Guide](https://support.google.com/analytics/answer/9304153)

---

**Next Steps:**
1. Get your GA4 Measurement ID
2. Replace placeholder in index.html
3. Test the implementation
4. Monitor your analytics dashboard
5. Create custom reports for key metrics
