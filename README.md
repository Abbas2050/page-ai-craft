
# AI PageGen - WordPress Plugin

A professional WordPress plugin that generates high-quality posts and pages using OpenAI's GPT API. Available in Free and Pro versions with advanced customization options.

## 🚀 Quick Start Guide

### 1. Installation
1. Upload the `ai-pagegen` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You'll see a new "AI PageGen" menu item in your WordPress admin sidebar

### 2. Configuration & API Key Setup
After activation, you **MUST** configure your OpenAI API key:

1. **Navigate to Settings**: Go to **AI PageGen > Settings** in your WordPress admin
2. **Get OpenAI API Key**: 
   - Visit [OpenAI Platform](https://platform.openai.com/api-keys)
   - Sign up/login to your OpenAI account
   - Click "Create new secret key"
   - Copy the generated API key (starts with `sk-`)
3. **Enter API Key**: Paste your API key in the "OpenAI API Key" field
4. **Configure Defaults**: Set your preferred default options:
   - Default post type (Post or Page)
   - Header/Footer preference
   - Color scheme
5. **Save Settings**: Click "Save Changes"

### 3. Generate Your First Content
1. Go to **AI PageGen > Generate Content**
2. Enter a detailed prompt (e.g., "Create a blog post about sustainable gardening tips")
3. Click "Generate Content"
4. Review the generated content in the preview
5. Edit the draft post/page as needed

## ✨ Features

### 🆓 Free Version
- **Basic AI Content Generation**: Create posts and pages using OpenAI's GPT API
- **Simple Prompt Interface**: Enter your content requirements in plain English
- **Draft Creation**: Generated content is saved as drafts for review
- **Theme Integration**: Uses your current theme's styling
- **WordPress Standards**: Follows WordPress coding standards and security practices

### 💎 Pro Version Features
- **📝 Post Type Selection**: Choose between posts and pages
- **🎨 Header/Footer Options**: 
  - Use theme default styling
  - Generate custom AI-powered headers and footers
- **🔍 SEO Optimization**: 
  - Automatic SEO title generation
  - Meta description creation
  - Keyword optimization
  - Proper heading hierarchy (H1, H2, H3)
  - Compatible with Yoast SEO and RankMath
- **🎨 Custom Color Schemes**: 
  - Apply custom color combinations
  - Support for hex codes: `#2271b1,#ffffff,#000000`
  - Color names: `blue/white/dark`
  - Automatic CSS generation
- **📋 Structured Page Sections**: 
  - Define content sections: "Hero, Services, About, Contact"
  - Organized content structure
  - Perfect for landing pages and business websites
- **⚡ Priority Support**: Get help when you need it

## 🛠️ Configuration Options

### API Settings
- **OpenAI API Key**: Your secret key from OpenAI Platform (Required)
- **Model Selection**: Choose GPT model (default: gpt-3.5-turbo)
- **Request Timeout**: API request timeout in seconds

### Content Defaults
- **Default Post Type**: Post or Page
- **Default Status**: Draft, Published, or Private
- **Default Author**: Current user or specific author
- **Header/Footer**: Theme default or custom AI-generated

### SEO Settings (Pro)
- **Enable SEO Optimization**: Automatic SEO enhancements
- **Default Keywords**: Fallback keywords for content
- **Meta Description Length**: Character limit for descriptions

### Styling Options (Pro)
- **Color Schemes**: Custom color combinations
- **CSS Integration**: How styles are applied to content
- **Theme Compatibility**: Override or complement theme styles

## 🎯 Best Practices for Prompts

### Effective Prompting Tips
1. **Be Specific**: Instead of "write about cars", use "write a comprehensive guide about electric car maintenance for beginners"
2. **Include Context**: Mention your target audience, tone, and purpose
3. **Specify Structure**: Request specific sections or formats
4. **Provide Examples**: Reference similar content you want to emulate

### Sample Prompts
```
✅ Good: "Create a professional blog post about digital marketing trends in 2024, targeting small business owners. Include sections on social media, email marketing, and SEO. Use a conversational but authoritative tone."

❌ Poor: "Write about marketing"

✅ Good: "Generate a landing page for a fitness coaching service. Include a hero section with compelling headline, benefits section, testimonials, pricing, and contact form. Target audience is working professionals aged 25-45."

❌ Poor: "Make a fitness page"
```

## 🔧 Technical Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **OpenAI API**: Valid API key with sufficient credits
- **Memory**: Minimum 128MB PHP memory limit recommended
- **Internet**: Stable connection for API requests

## 📁 File Structure

```
ai-pagegen/
├── ai-pagegen.php                    # Main plugin file
├── includes/                         # Core functionality
│   ├── class-ai-pagegen-admin.php           # Admin interface & UI
│   ├── class-ai-pagegen-openai.php          # OpenAI API integration
│   ├── class-ai-pagegen-post-creator.php    # Post/page creation logic
│   └── class-ai-pagegen-licensing.php       # Pro licensing system
├── assets/                           # Frontend assets
│   ├── css/
│   │   └── admin.css                # Admin panel styling
│   └── js/
│       └── admin.js                 # Admin panel interactions
├── languages/                       # Translation files
│   └── ai-pagegen.pot              # Translation template
└── README.md                       # This file
```

## 🌍 Translation & Localization

The plugin is fully translatable and ready for international use:

### Available Languages
- English (default)
- Translation template provided (`ai-pagegen.pot`)

### Adding Translations
1. Copy `languages/ai-pagegen.pot`
2. Rename to your locale (e.g., `ai-pagegen-es_ES.po`)
3. Translate using Poedit or similar tool
4. Generate `.mo` file
5. Upload both files to `languages/` directory

### Contributing Translations
We welcome community translations! Submit translated files via GitHub or contact support.

## 🔐 Security Features

- **Nonce Verification**: All forms protected against CSRF attacks
- **Capability Checks**: User permission validation
- **Input Sanitization**: All user inputs properly sanitized
- **API Key Encryption**: Secure storage of sensitive data
- **WordPress Standards**: Follows WordPress security guidelines

## 🚀 GitHub Integration & Development

### Setting Up GitHub Sync
1. **Create Repository**: Create a new repository on GitHub
2. **Clone Locally**: `git clone https://github.com/yourusername/ai-pagegen.git`
3. **Upload Plugin**: Copy plugin files to repository
4. **Commit Changes**: 
   ```bash
   git add .
   git commit -m "Initial AI PageGen plugin"
   git push origin main
   ```

### Development Workflow
1. **Local Development**: Edit files locally using your preferred IDE
2. **Testing**: Test changes on local WordPress installation
3. **Version Control**: Commit changes with descriptive messages
4. **Deployment**: Upload updated files to production

### Contributing
1. Fork the repository
2. Create feature branch: `git checkout -b feature/new-feature`
3. Make changes and test thoroughly
4. Commit changes: `git commit -m "Add new feature"`
5. Push to branch: `git push origin feature/new-feature`
6. Submit pull request

## 🎨 Customization & Hooks

### Available Hooks
```php
// Modify generated content before saving
add_filter('ai_pagegen_before_save_content', function($content, $options) {
    // Your custom modifications
    return $content;
}, 10, 2);

// Customize OpenAI request parameters
add_filter('ai_pagegen_openai_request_args', function($args) {
    $args['temperature'] = 0.8; // Adjust creativity
    $args['max_tokens'] = 2000;  // Limit response length
    return $args;
});

// Modify post data before creation
add_filter('ai_pagegen_post_data', function($post_data, $generated_content) {
    // Add custom fields, modify title, etc.
    return $post_data;
}, 10, 2);
```

### Custom Styling
Override plugin styles by adding CSS to your theme:
```css
/* Customize admin interface */
.ai-pagegen-form-container {
    background: #your-color;
}

/* Style generated content */
.ai-generated-content {
    font-family: your-font;
}
```

## 💰 Upgrading to Pro

### How to Upgrade
1. **Purchase License**: Visit our website to purchase Pro license
2. **Activate License**: Go to **AI PageGen > License** in WordPress admin
3. **Enter License Key**: Input your license key and activate
4. **Enjoy Pro Features**: All Pro features will be immediately available

### Pro License Benefits
- ✅ All Pro features unlocked
- ✅ Priority email support
- ✅ Regular updates and new features
- ✅ Advanced customization options
- ✅ Commercial use license

## 🆘 Troubleshooting

### Common Issues

**"API Key Invalid" Error**
- Verify your OpenAI API key is correct
- Check if key has sufficient credits
- Ensure key is not expired

**"Request Timeout" Error**
- Check your internet connection
- Increase timeout in settings
- Try with shorter prompts

**Generated Content Not Saving**
- Verify user has proper permissions
- Check WordPress memory limit
- Review error logs for details

**Pro Features Not Working**
- Verify license key is active
- Clear browser cache
- Contact support if issues persist

### Getting Help

**Free Version Support**
- WordPress.org plugin forums
- Documentation and FAQ
- Community support

**Pro Version Support**
- Priority email support: support@yourdomain.com
- Advanced troubleshooting assistance
- Feature request consideration

## 📊 Usage Analytics & Monitoring

### Tracking Usage
- Monitor API usage in OpenAI dashboard
- Track content generation success rates
- Review error logs for optimization

### Performance Optimization
- Cache frequently used prompts
- Optimize API request parameters
- Monitor response times

## 🔄 Updates & Maintenance

### Automatic Updates
- Free version: WordPress.org repository
- Pro version: Automatic updates via license system

### Manual Updates
1. Backup your site
2. Download latest version
3. Replace plugin files
4. Test functionality

### Version History
- **1.0.0**: Initial release with core features
- Future updates will be documented here

## 📄 License & Legal

### Open Source License
This plugin is licensed under GPL v2 or later.

### OpenAI API Terms
By using this plugin, you agree to OpenAI's Terms of Service and API usage policies.

### Privacy Policy
- Plugin does not store user prompts permanently
- API requests are sent to OpenAI servers
- Generated content is stored in your WordPress database

## 🤝 Credits & Acknowledgments

- **WordPress Community**: For the amazing platform
- **OpenAI**: For the powerful GPT API
- **Contributors**: Community members who help improve the plugin
- **Beta Testers**: Early users who provided valuable feedback

---

## 📞 Support & Contact

**Need Help?**
- 📖 [Documentation](https://yourdomain.com/docs)
- 💬 [Community Forum](https://wordpress.org/support/plugin/ai-pagegen)
- 📧 [Pro Support](mailto:support@yourdomain.com)
- 🐛 [Report Issues](https://github.com/yourusername/ai-pagegen/issues)

**Follow Us**
- 🐦 [Twitter](https://twitter.com/yourusername)
- 💼 [LinkedIn](https://linkedin.com/company/yourcompany)
- 📺 [YouTube Tutorials](https://youtube.com/yourchannel)

---

*Made with ❤️ for the WordPress community*
