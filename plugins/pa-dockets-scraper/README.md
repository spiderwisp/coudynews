# PA Dockets Scraper WordPress Plugin

A WordPress plugin that automatically scrapes the PA Web Dockets system hourly for Potter, Tioga, and McKean counties, generates SEO-optimized articles using Groq Cloud AI, and publishes them as WordPress posts.

## Features

- **Automated Scraping**: Hourly cron job checks for new dockets from PA Web Dockets system
- **AI-Powered Content**: Uses Groq Cloud AI to generate professional, SEO-optimized articles from docket data
- **Automatic Publishing**: Creates WordPress posts with proper categories, tags, and SEO metadata
- **All-in-One SEO Pack Integration**: Automatically sets SEO meta data for generated articles
- **Admin Dashboard**: Easy-to-use settings page for configuration and log viewing
- **County-Specific**: Monitors Potter, Tioga, and McKean counties

## Installation

1. Upload the `pa-dockets-scraper` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'PA Dockets' in the WordPress admin menu
4. Configure your Groq Cloud API credentials and settings

## Configuration

### Required Settings

1. **Groq API Key**: Get your API key from [Groq Cloud Console](https://console.groq.com)
2. **API URL**: Default is `https://api.groq.com/openai/v1` (usually doesn't need to be changed)
3. **Model**: Select the Groq model to use (default: Llama 3.1 70B Versatile)

### Optional Settings

- **Counties to Monitor**: Select which counties to scrape (Potter, Tioga, McKean)
- **Default Category**: Set a default WordPress category for generated posts
- **Default Tags**: Comma-separated list of default tags
- **Article Tone**: Choose the writing style (Professional, Informative, Conversational, Formal)

## Usage

### Automatic Operation

Once configured, the plugin will:
1. Run hourly to check for new dockets
2. Scrape docket information from PA Web Dockets portal
3. Generate articles using Groq Cloud AI
4. Publish posts automatically with SEO optimization

### Manual Scraping

You can trigger a manual scrape from the Settings page:
1. Go to 'PA Dockets' > 'Settings'
2. Click 'Trigger Manual Scrape' button
3. Check the Logs page for results

### Viewing Logs

1. Go to 'PA Dockets' > 'Logs'
2. Filter by log type (Info, Success, Warning, Error)
3. View detailed context for each log entry

## Technical Details

### Database

The plugin creates a custom table `wp_pa_dockets_scraped` to track:
- Docket numbers (unique)
- County information
- Scraped dates
- Associated WordPress post IDs
- Raw docket data (JSON)
- Processing status

### Cron Job

- Hook: `pa_dockets_hourly_scrape`
- Frequency: Hourly
- Automatically scheduled on activation
- Automatically cleared on deactivation

### Dependencies

- WordPress 5.0+
- PHP 7.4+
- All-in-One SEO Pack (optional, for enhanced SEO features)
- Groq Cloud API access (Groq account)

## County Codes

The plugin uses Pennsylvania Unified Judicial System county codes:
- Potter County: 62
- Tioga County: 61
- McKean County: 42

## File Structure

```
pa-dockets-scraper/
├── pa-dockets-scraper.php (Main plugin file)
├── includes/
│   ├── class-database.php
│   ├── class-logger.php
│   ├── class-scraper.php
│   ├── class-ai-generator.php
│   ├── class-post-creator.php
│   └── class-cron-handler.php
├── admin/
│   ├── class-admin-settings.php
│   ├── views/
│   │   ├── settings-page.php
│   │   └── logs-page.php
│   └── css/
│       └── admin.css
└── README.md
```

## Support

For issues or questions, please check the Logs page in the WordPress admin for detailed error messages.

## License

GPL v2 or later
