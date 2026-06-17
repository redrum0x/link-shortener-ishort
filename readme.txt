=== Link Shortener by iShort ===
Contributors: ishortsu
Tags: url shortener, link shortener, short url, ishort, tiny url
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shorten URLs directly from the WordPress editor using iShort.su — the fast, reliable URL shortening service.

== Description ==

**Link Shortener by iShort** lets you shorten any URL right inside the WordPress post editor — no copy-pasting between tabs.

**Features:**

* **Classic Editor (TinyMCE)** — icon button in the editor toolbar. Select a URL, click the button, and it's instantly replaced with a short link.
* **Gutenberg / Block Editor** — inline toolbar button. Select a URL in any text block and shorten it in one click.
* **Meta box** — "Shorten post URL" button in the post sidebar. Shortens the current post's URL and saves the result for later copying.
* **Works on all post types** — posts, pages, custom post types.
* **Disabled for drafts** — the meta box button is disabled until the post is published.
* **Settings page** — one field (API token) and a "Test Connection" button. Includes a direct link to get your token.

**External service:**

This plugin connects to **iShort.su** (https://ishort.su) to shorten URLs. An API token from iShort.su is required. Requests are only sent when you explicitly click a shortening button — no background tracking or data collection.

* iShort Privacy Policy: https://ishort.su/policy
* iShort Terms of Service: https://ishort.su/offer

== Installation ==

1. Upload the `link-shortener-ishort` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → iShort** and enter your API token.
4. Get your token at https://ishort.su/user/api-clients

== Frequently Asked Questions ==

= Where do I get an API token? =

Register at https://ishort.su and go to https://ishort.su/user/api-clients to create an API token.

= Does the plugin work without an account? =

No. An iShort.su account and API token are required.

= Which editors are supported? =

Both Classic Editor (TinyMCE) and Gutenberg (Block Editor) are supported.

= Can I shorten URLs that are already short links? =

Yes, already-shortened URLs can be re-shortened.

= What happens if the API token is not configured? =

You will see a message with a link to the plugin settings page.

== Screenshots ==

1. Settings page — enter your API token and test the connection.
2. Classic Editor toolbar button — shorten a selected URL.
3. Gutenberg inline toolbar button — shorten a selected URL in a block.
4. Meta box — shorten the post URL and copy the result.

== Changelog ==

= 1.0.0 =
* Initial release.
* Classic Editor (TinyMCE) toolbar button.
* Gutenberg inline toolbar button.
* Meta box with post URL shortening and copy button.
* Settings page with API token field and connection test.
* English and Russian translations.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
