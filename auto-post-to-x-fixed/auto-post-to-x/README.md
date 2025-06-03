# Auto Post to X

A WordPress plugin that automatically publishes new blog posts to X (formerly Twitter) with optimized images that meet X's posting specifications.

## Features

- **Automatic Posting**: Automatically posts new WordPress content to X when published
- **Image Optimization**: Automatically optimizes images to meet X's specifications (5MB max, optimal dimensions)
- **X API v2 Compliance**: Uses the latest X API v2 with OAuth 2.0 authentication
- **Flexible Image Sources**: Supports featured images and first content images
- **Customizable Messages**: Template-based message generation with placeholders
- **Post Type Support**: Configure which post types to auto-post
- **Per-Post Control**: Enable/disable posting on individual posts
- **Activity Logging**: Comprehensive logging of all posting attempts
- **Retry Logic**: Automatic retry for failed posts
- **Admin Interface**: User-friendly settings panel with tabbed interface

## X Image Specifications (2024)

This plugin automatically optimizes images to meet X's current requirements:

- **Maximum file size**: 5MB
- **Supported formats**: JPG, PNG, GIF, WebP
- **Recommended dimensions**:
  - Landscape: 1200 x 675 pixels (16:9 aspect ratio)
  - Square: 1200 x 1200 pixels (1:1 aspect ratio)
  - Portrait: 1080 x 1350 pixels (4:5 aspect ratio)
- **Aspect ratio range**: 1:3 to 3:1

## Installation

1. **Upload the plugin**:
   - Download or clone this repository
   - Upload the `auto-post-to-x` folder to `/wp-content/plugins/`
   - Or upload the ZIP file through WordPress admin

2. **Activate the plugin**:
   - Go to 'Plugins' in WordPress admin
   - Find 'Auto Post to X' and click 'Activate'

3. **Set up X API credentials**:
   - Go to [X Developer Portal](https://developer.twitter.com/en/portal/dashboard)
   - Create a new app or use an existing one
   - Get your Client ID and Client Secret
   - Configure OAuth 2.0 settings in your X app

4. **Configure the plugin**:
   - Go to Settings > Auto Post to X in WordPress admin
   - Enter your X API credentials
   - Authorize the plugin with your X account
   - Configure your preferences

## Setup Guide

### 1. Create X Developer App

1. Visit [X Developer Portal](https://developer.twitter.com/en/portal/dashboard)
2. Create a new project/app
3. Note down your **Client ID** and **Client Secret**
4. In your app settings, add this callback URL:
   ```
   https://yourdomain.com/wp-admin/options-general.php?page=auto-post-to-x
   ```
5. Ensure your app has the following permissions:
   - Read and Write tweets
   - Read users

### 2. Plugin Configuration

1. **General Settings**:
   - Enable automatic posting
   - Select post types to include
   - Customize message template
   - Set character limit

2. **API Settings**:
   - Enter Client ID and Client Secret
   - Click "Authorize with X" to connect your account
   - Test the connection

3. **Image Settings**:
   - Enable image posting
   - Choose image source (featured only or content fallback)
   - Select preferred image size

## Message Templates

Use these placeholders in your message template:

- `{POST_TITLE}` - The post title
- `{PERMALINK}` - The post URL
- `{EXCERPT}` - Post excerpt (or content preview)
- `{AUTHOR}` - Post author name

Example template:
```
🚀 New post: {POST_TITLE}

{EXCERPT}

Read more: {PERMALINK}
```

## File Structure

```
auto-post-to-x/
├── auto-post-to-x.php          # Main plugin file
├── includes/
│   ├── class-auto-post-x.php   # Main plugin class
│   ├── class-x-api.php         # X API handler
│   └── class-image-optimizer.php # Image optimization
├── admin/
│   └── admin-settings.php      # Admin interface
└── README.md                   # This file
```

## Technical Details

### Image Optimization Process

1. **Source Selection**: Choose featured image or first content image
2. **Size Check**: Verify if image meets X requirements
3. **Optimization**: If needed, resize and compress image
4. **Format Conversion**: Ensure compatible format (JPG, PNG, GIF, WebP)
5. **Quality Adjustment**: Compress to meet 5MB limit while maintaining quality

### X API Integration

- Uses X API v2 for posting
- OAuth 2.0 with PKCE for secure authentication
- Automatic token refresh
- Proper error handling and retry logic
- Media upload with chunked support for large files

### WordPress Integration

- Hooks into `transition_post_status` for post publishing
- Meta box for per-post control
- Uses WordPress image editor for optimization
- Follows WordPress coding standards
- Internationalization ready

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- X Developer Account with API access
- GD or ImageMagick for image processing

## Frequently Asked Questions

**Q: Do I need X Premium/Pro for API access?**
A: X API access requirements change over time. Check the current X Developer documentation for the latest requirements.

**Q: Can I customize which images are posted?**
A: Yes, you can choose to use only featured images or fall back to the first image in post content.

**Q: What happens if image upload fails?**
A: The plugin will proceed to post text-only content and log the image upload failure.

**Q: Can I post to multiple X accounts?**
A: Currently, the plugin supports one X account per WordPress site.

**Q: How are failed posts handled?**
A: Failed posts are automatically retried after 5 minutes and logged for review.

## Support

For support, please:

1. Check the plugin logs in Settings > Auto Post to X > Logs
2. Verify your X API credentials and permissions
3. Ensure your images meet X specifications
4. Check WordPress and PHP error logs

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Changelog

### Version 1.0.0
- Initial release
- X API v2 integration
- Image optimization for X specifications
- OAuth 2.0 authentication
- Comprehensive admin interface
- Activity logging and retry logic 