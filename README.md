=== Auto Post to X ===
Contributors: yourname
Donate link: https://example.com/donate
Tags: twitter, x, social media, auto post, automation, social sharing
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically publishes new WordPress posts to X (formerly Twitter) with optimized images that meet X's posting specifications.

== Description ==

Auto Post to X is a powerful WordPress plugin that automatically shares your new blog posts to X (formerly Twitter) when they're published. The plugin intelligently optimizes images to meet X's current specifications and provides a comprehensive set of features for seamless social media automation.

= Key Features =

* **Automatic Posting**: Instantly share new WordPress content to X when published
* **Smart Image Optimization**: Automatically optimizes images to meet X's 2024 specifications (5MB max, optimal dimensions)
* **X API v2 Integration**: Uses the latest X API v2 with secure OAuth 2.0 authentication
* **Flexible Image Sources**: Supports featured images with fallback to first content image
* **Customizable Messages**: Template-based message generation with dynamic placeholders
* **Post Type Control**: Configure which post types should be automatically posted
* **Per-Post Override**: Enable or disable posting on individual posts via meta box
* **Activity Logging**: Comprehensive logging of all posting attempts with success/failure tracking
* **Automatic Retry**: Smart retry logic for failed posts
* **User-Friendly Interface**: Intuitive admin panel with tabbed settings organization

= X Image Specifications Compliance (2024) =

This plugin automatically ensures your images meet X's current requirements:

* Maximum file size: 5MB
* Supported formats: JPG, PNG, GIF, WebP
* Recommended dimensions:
  * Landscape: 1200 x 675 pixels (16:9 aspect ratio)
  * Square: 1200 x 1200 pixels (1:1 aspect ratio) 
  * Portrait: 1080 x 1350 pixels (4:5 aspect ratio)
* Aspect ratio range: 1:3 to 3:1

= Message Template System =

Create dynamic posts using these placeholders:

* `{POST_TITLE}` - The post title
* `{PERMALINK}` - The post URL
* `{EXCERPT}` - Post excerpt or content preview
* `{AUTHOR}` - Post author name

Example: "🚀 New post: {POST_TITLE} - {EXCERPT} Read more: {PERMALINK}"

= Requirements =

* X Developer Account with API access
* X App with proper OAuth 2.0 configuration
* WordPress 5.0 or higher
* PHP 7.4 or higher
* GD or ImageMagick for image processing

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/auto-post-to-x/` directory, or install directly through WordPress admin.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Create an X (Twitter) app at https://developer.twitter.com/en/portal/dashboard
4. Configure OAuth 2.0 settings in your X app with the callback URL: `https://yourdomain.com/wp-admin/options-general.php?page=auto-post-to-x`
5. Go to Settings > Auto Post to X in WordPress admin.
6. Enter your X API credentials (Client ID and Client Secret).
7. Click "Authorize with X" to connect your account.
8. Configure your posting preferences.

== Frequently Asked Questions ==

= Do I need X Premium/Pro for API access? =

X API access requirements change over time. Check the current X Developer documentation for the latest requirements and pricing.

= Can I customize which images are posted? =

Yes! You can choose to use only featured images or allow fallback to the first image found in post content. Images are automatically optimized to meet X specifications.

= What happens if image upload fails? =

The plugin will proceed to post text-only content and log the image upload failure for your review. Failed posts are automatically retried.

= Can I post to multiple X accounts? =

Currently, the plugin supports one X account per WordPress site. Multiple account support may be added in future versions.

= How are failed posts handled? =

Failed posts are automatically retried after 5 minutes. All attempts are logged in the plugin's activity log for easy troubleshooting.

= Can I disable posting for specific posts? =

Yes! Each post editor includes an "Auto Post to X" meta box where you can enable or disable posting for individual posts.

= What post types are supported? =

You can configure which post types should be automatically posted in the plugin settings. By default, only standard posts are included.

== Screenshots ==

1. Main settings page with tabbed interface
2. API settings and authorization flow
3. Image optimization settings with X specifications
4. Activity logs showing posting history
5. Per-post meta box for individual control

== Changelog ==

= 1.0.0 =
* Initial release
* X API v2 integration with OAuth 2.0
* Automatic image optimization for X specifications
* Customizable message templates with placeholders
* Per-post posting control via meta box
* Activity logging and retry mechanism
* Comprehensive admin interface with tabbed settings
* Support for multiple post types
* Internationalization support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Auto Post to X. Start automatically sharing your WordPress posts to X with optimized images!

== Support ==

For support and troubleshooting:

1. Check the plugin's activity logs in Settings > Auto Post to X > Logs
2. Verify your X API credentials and app permissions
3. Ensure images meet X specifications (plugin handles this automatically)
4. Review WordPress and PHP error logs

For additional help, visit the plugin support forum or documentation.

== Privacy Policy ==

This plugin connects to X (Twitter) API to post content on your behalf. The plugin:

* Stores X API credentials securely in your WordPress database
* Sends post content and images to X servers when posting
* Logs posting activities locally for troubleshooting
* Does not collect or share personal data beyond what's necessary for X posting
* Follows WordPress privacy and security best practices

By using this plugin, you agree to X's Terms of Service and Privacy Policy.

== Credits ==

Developed with ❤️ for the WordPress community. Uses X API v2 for reliable social media integration. 
