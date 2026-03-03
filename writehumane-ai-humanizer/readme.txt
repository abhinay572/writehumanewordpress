=== WriteHumane AI Humanizer ===
Contributors: viionrinfotech
Tags: ai humanizer, content rewriter, ai detection, seo, gutenberg
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Humanize AI-generated content with one click. Works in Gutenberg, Classic Editor, and via shortcode. Passes all AI detectors.

== Description ==

WriteHumane AI Humanizer transforms AI-generated content into naturally human-sounding text that passes all major AI detection tools including GPTZero, Originality.ai, Copyleaks, and Turnitin.

**Features:**

* One-click humanization in Gutenberg Editor (sidebar plugin)
* Classic Editor support with full content or selected text humanization
* Frontend shortcode widget [writehumane] with light/dark themes
* Three modes: Light (subtle polish), Balanced (best all-around), Aggressive (full rewrite)
* Four tones: Professional, Casual, Academic, Friendly
* SEO keywords preserved automatically
* Admin dashboard with usage tracking per user
* Monthly word limit controls
* Role-based access control

**Admin Dashboard:**

* Words used this month with progress bar
* Per-user usage breakdown (who, how much, when)
* Full request logs with pagination
* 7-day usage chart
* Mode breakdown statistics

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via WordPress admin
2. Activate the plugin
3. Go to **WriteHumane → Settings**
4. Enter your Gemini API key (get free from https://aistudio.google.com/apikey)
5. Click "Test Connection" to verify
6. Start humanizing content!

== Frequently Asked Questions ==

= Where do I get an API key? =
Get a free Gemini API key from Google AI Studio: https://aistudio.google.com/apikey

= Does the humanized text pass AI detectors? =
Yes. The balanced and aggressive modes consistently score very low on GPTZero, Originality.ai, Copyleaks, and Turnitin.

= Can I limit how many words users can humanize? =
Yes. Go to Settings → Monthly Word Limit. Set to 0 for unlimited.

= Which user roles can use the humanizer? =
By default: Administrator, Editor, and Author. You can change this in Settings.

= Can I use the shortcode on the frontend? =
Yes. Use [writehumane] in any page or post. Supports theme="dark" and mode="aggressive" attributes.

== Changelog ==

= 1.0.0 =
* Initial release
* Gutenberg sidebar integration
* Classic Editor meta box
* Frontend shortcode widget
* Admin dashboard with usage tracking
* Per-user usage stats and logs
