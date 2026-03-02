=== WriteHumane AI Humanizer ===
Contributors: viionrinfotech
Tags: ai, humanizer, content, writing, ai detection
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Humanize AI-generated content to pass AI detectors. One-click integration with Gutenberg, Classic Editor, and shortcode support.

== Description ==

**WriteHumane AI Humanizer** transforms AI-generated text into naturally human-sounding content that passes AI detection tools like GPTZero, Originality.ai, Copyleaks, and Turnitin.

= Features =

* **Gutenberg Integration** — Sidebar plugin with one-click humanization for all block content
* **Classic Editor Integration** — Meta box with full content and selected text humanization
* **Shortcode Widget** — Embed `[writehumane]` on any page for a user-facing humanization tool
* **Multiple AI Providers** — Use WriteHumane API, OpenAI (direct), or Anthropic (direct)
* **Three Modes** — Light (subtle polish), Balanced (recommended), Aggressive (full rewrite)
* **Four Tones** — Professional, Casual, Academic, Friendly
* **SEO Preservation** — Keywords and key phrases stay intact
* **Usage Tracking** — Monthly word usage dashboard with limits
* **Role-Based Access** — Control which user roles can humanize content

= How It Works =

1. Install and activate the plugin
2. Go to **Settings → AI Humanizer**
3. Choose your API provider and enter credentials
4. Open any post in Gutenberg or Classic Editor
5. Click **Humanize Content** — done!

= API Providers =

**WriteHumane API (Recommended)**
Sign up at [writehumane.com](https://writehumane.com) for a free API key with 1,000 words/month.

**OpenAI Direct**
Use your own OpenAI API key. Recommended model: gpt-4o-mini (cheapest).

**Anthropic Direct**
Use your own Anthropic API key. Recommended model: Claude Sonnet.

== Installation ==

1. Upload the `writehumane-ai-humanizer` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings → AI Humanizer** to configure your API provider
4. Start humanizing content in the post editor!

= Using the Shortcode =

Add `[writehumane]` to any post or page. Customize with attributes:

`[writehumane mode="aggressive" tone="casual" theme="dark" max_words="3000"]`

== Frequently Asked Questions ==

= Does this really pass AI detectors? =

Yes. The balanced and aggressive modes consistently score below 15% on major AI detectors. Results vary based on content type and length.

= Which API provider should I use? =

We recommend the WriteHumane API for the best results. It's specifically optimized for humanization. Direct OpenAI/Anthropic options are available if you prefer to use your own keys.

= Is there a free plan? =

Yes! Sign up at writehumane.com for 1,000 free words/month. No credit card required.

= Does it preserve my SEO keywords? =

Yes. The humanizer is specifically designed to preserve all keywords, key phrases, and factual content while rewriting sentence structure and adding natural variation.

= Can I humanize selected text only? =

Yes! In the Classic Editor, select text and click "Humanize Selected Text." In Gutenberg, the full content is humanized as a unit.

== Changelog ==

= 1.0.0 =
* Initial release
* Gutenberg sidebar integration
* Classic Editor meta box
* Frontend shortcode widget
* WriteHumane, OpenAI, and Anthropic provider support
* Usage tracking dashboard
* Role-based access control

== Screenshots ==

1. Admin settings page with usage dashboard
2. Gutenberg sidebar humanization panel
3. Classic Editor meta box
4. Frontend shortcode widget
