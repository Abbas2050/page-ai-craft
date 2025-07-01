
# AI PageGen - WordPress Plugin

A professional WordPress plugin that generates high-quality posts and pages using OpenAI's GPT API. Available in Free and Pro versions with advanced customization options.

## Features

### Free Version
- Basic AI content generation using OpenAI API
- Simple prompt-based content creation
- Draft post/page creation for review

### Pro Version
- **Post Type Selection**: Choose between posts and pages
- **Header/Footer Options**: Use theme default or AI-generated custom headers/footers
- **SEO Optimization**: Automatic SEO title, meta description, and keyword optimization
- **Color Schemes**: Apply custom color combinations to generated content
- **Page Sections**: Structure content with custom sections (Hero, Services, Contact, etc.)
- **Priority Support**: Get help when you need it

## Installation

1. Upload the `ai-pagegen` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **AI PageGen > Settings** and enter your OpenAI API key
4. Start generating content from **AI PageGen > Generate Content**

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenAI API key (get one from [OpenAI](https://platform.openai.com/api-keys))

## Getting Started

### 1. Configure API Key
- Navigate to **AI PageGen > Settings**
- Enter your OpenAI API key
- Save settings

### 2. Generate Content
- Go to **AI PageGen > Generate Content**
- Enter a detailed prompt describing what you want to create
- Configure additional options (Pro version only)
- Click "Generate Content"
- Review and edit the generated draft

### 3. Upgrade to Pro (Optional)
- Visit **AI PageGen > License**
- Enter your Pro license key
- Unlock advanced features

## Pro Features in Detail

### SEO Optimization
When enabled, the plugin will:
- Generate SEO-optimized titles and meta descriptions
- Naturally incorporate your specified keywords
- Create proper heading hierarchy (H1, H2, H3)
- Support popular SEO plugins (Yoast, RankMath)

### Color Schemes
Apply custom colors to your generated content:
- Enter hex codes: `#2271b1,#ffffff,#000000`
- Use color names: `blue/white/dark`
- Automatic CSS generation for consistent styling

### Page Sections
Structure your content with specific sections:
- Example: `Hero, Services, About, Contact`
- Each section is properly organized and formatted
- Perfect for landing pages and business websites

## Developer Notes

### File Structure
```
ai-pagegen/
├── ai-pagegen.php              # Main plugin file
├── includes/
│   ├── class-ai-pagegen-admin.php         # Admin interface
│   ├── class-ai-pagegen-openai.php        # OpenAI API handler
│   ├── class-ai-pagegen-post-creator.php  # Post/page creation
│   └── class-ai-pagegen-licensing.php     # Licensing system
├── assets/
│   ├── css/admin.css           # Admin styles
│   └── js/admin.js             # Admin JavaScript
├── languages/
│   └── ai-pagegen.pot          # Translation template
└── README.md
```

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Modify generated content before saving
add_filter('ai_pagegen_before_save_content', function($content, $options) {
    // Your custom modifications
    return $content;
}, 10, 2);

// Customize OpenAI request parameters
add_filter('ai_pagegen_openai_request_args', function($args) {
    // Modify temperature, max_tokens, etc.
    return $args;
});
```

### Licensing Integration

The plugin includes a licensing system stub. For production use, integrate with Freemius SDK:

1. Download [Freemius SDK](https://github.com/Freemius/wordpress-sdk)
2. Replace the licensing class with proper Freemius initialization
3. Configure pricing plans in Freemius dashboard

See detailed integration notes in `includes/class-ai-pagegen-licensing.php`

## Security Features

- Nonce verification for all forms
- User capability checks
- Input sanitization and validation
- Secure API key storage
- CSRF protection

## Translation Ready

The plugin is fully translatable using WordPress localization functions. Translation files are located in the `languages/` directory.

To create a translation:
1. Copy `ai-pagegen.pot`
2. Rename to your locale (e.g., `ai-pagegen-es_ES.po`)
3. Translate strings using Poedit or similar tool
4. Generate `.mo` file

## Support

### Free Version
- Community support via WordPress.org forums
- Documentation and FAQ

### Pro Version
- Priority email support
- Advanced troubleshooting
- Feature requests consideration

## Changelog

### 1.0.0
- Initial release
- Basic AI content generation
- Pro licensing system
- SEO optimization features
- Color scheme customization
- Page section structuring

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed with WordPress best practices
- Uses OpenAI GPT API for content generation
- Built with modern JavaScript and CSS
- Responsive admin interface design

---

**Need help?** Visit our [documentation](https://your-domain.com/docs) or [contact support](https://your-domain.com/support).
