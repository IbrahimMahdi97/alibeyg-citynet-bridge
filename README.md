# Alibeyg Citynet Bridge Plugin

A WordPress plugin that provides a server-side proxy to the Citynet API for booking flights, hotels, and visas. Features a modern travel widget with autocomplete functionality and full internationalization support.

## Features

- **Flight Booking**: Search for flights with round-trip, one-way, and multi-city options
- **Hotel Booking**: Search for hotels with flexible date and guest options
- **Visa Services**: Search for visa requirements by country and duration
- **Autocomplete**: Real-time airport/city suggestions
- **Internationalization**: Full i18n support with Polylang integration
- **Responsive Design**: Mobile-friendly interface
- **Customizable**: Configurable colors and URLs via shortcode attributes

## Installation

1. Upload the `alibeyg-citynet-bridge` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure API credentials in `wp-config.php` (see Configuration section)
4. Use the shortcode `[alibeyg_travel_widget]` in your pages or posts

## Configuration

Add these constants to your `wp-config.php` file:

```php
define('CN_API_BASE', 'https://citynet.ir/');
define('CN_ORG_ID', 12345);
define('CN_API_KEY', 'your-api-key-here');
// OR use username/password authentication:
define('CN_USERNAME', 'your-username');
define('CN_PASSWORD', 'your-password');
```

## Usage

### Basic Shortcode

```
[alibeyg_travel_widget]
```

### Customized Shortcode

```
[alibeyg_travel_widget
    primary="#B8011F"
    primary_hover="#9a0119"
    flight_url="/flights-search/"
    hotel_url="/hotels-search/"
    visa_url="/visa-search/"]
```

### Shortcode Attributes

- `primary` - Primary color (default: #B8011F)
- `primary_hover` - Hover color (default: #9a0119)
- `flight_url` - Flight search results page URL
- `hotel_url` - Hotel search results page URL
- `visa_url` - Visa search results page URL
- `api_url` - API endpoint URL (optional)

## Plugin Structure (Separation of Concerns)

The plugin follows modern WordPress development best practices with complete separation of concerns:

### Directory Structure

```
alibeyg-citynet-bridge/
├── alibeyg-citynet-bridge.php    # Main plugin file (loader only)
├── includes/                      # PHP Classes (Business Logic)
│   ├── class-plugin.php          # Main plugin controller
│   ├── class-api-client.php      # API communication handler
│   ├── class-rest-controller.php # REST API endpoints
│   ├── class-shortcodes.php      # Shortcode handlers
│   └── class-i18n.php            # Internationalization
├── assets/                        # Frontend Assets
│   ├── css/
│   │   └── travel-widget.css     # Widget styles
│   └── js/
│       └── travel-widget.js      # Widget JavaScript logic
├── templates/                     # HTML Templates
│   ├── widget-template.php       # Main widget template
│   └── partials/                 # Template partials
│       ├── flights-form.php
│       ├── hotels-form.php
│       └── visa-form.php
├── languages/                     # Translation files
└── README.md                      # This file
```

### Architecture Overview

#### 1. **Main Plugin File** (`alibeyg-citynet-bridge.php`)
- Minimal bootstrap file
- Defines constants
- Loads main plugin class
- Single responsibility: Initialize plugin

#### 2. **PHP Classes** (`includes/`)

**class-plugin.php**
- Main plugin controller
- Coordinates all components
- Singleton pattern implementation
- Dependency injection

**class-api-client.php**
- Handles all API communication
- Token management
- Request/response handling
- Error handling

**class-rest-controller.php**
- WordPress REST API endpoints
- Request validation
- Response formatting
- `/wp-json/alibeyg/v1/proxy` - API proxy
- `/wp-json/alibeyg/v1/places` - Autocomplete

**class-shortcodes.php**
- Shortcode registration and rendering
- Asset enqueuing (CSS/JS)
- Translation management
- Template loading

**class-i18n.php**
- Text domain loading
- Polylang integration
- String registration
- Translation utilities

#### 3. **Frontend Assets** (`assets/`)

**CSS** (`assets/css/travel-widget.css`)
- Scoped widget styles
- Responsive design
- CSS custom properties for theming
- BEM-like naming convention

**JavaScript** (`assets/js/travel-widget.js`)
- Widget initialization
- Event handling
- AJAX requests
- Form validation
- Autocomplete logic

#### 4. **Templates** (`templates/`)

**widget-template.php**
- Main widget HTML structure
- Tab navigation
- Footer

**Partials** (`templates/partials/`)
- `flights-form.php` - Flight search form
- `hotels-form.php` - Hotel search form
- `visa-form.php` - Visa search form

Each partial is self-contained and can be modified independently.

## Development

### Adding New Features

1. **New API Endpoint**: Edit `class-api-client.php` and `class-rest-controller.php`
2. **New Form Field**: Edit the appropriate template in `templates/partials/`
3. **New Styles**: Edit `assets/css/travel-widget.css`
4. **New JavaScript**: Edit `assets/js/travel-widget.js`
5. **New Translations**: Add strings to `class-i18n.php`

### Code Standards

- Follow WordPress Coding Standards
- Use meaningful variable and function names
- Comment complex logic
- Maintain separation of concerns
- Keep files focused and single-purpose

### File Modification Guidelines

- **HTML Changes**: Modify template files in `templates/`
- **Style Changes**: Modify CSS files in `assets/css/`
- **Logic Changes**: Modify class files in `includes/`
- **JavaScript Changes**: Modify JS files in `assets/js/`

## REST API Endpoints

### POST `/wp-json/alibeyg/v1/proxy`

Proxy requests to Citynet API.

**Request:**
```json
{
    "path": "flights/search",
    "method": "POST",
    "payload": {
        // API-specific payload
    }
}
```

### GET `/wp-json/alibeyg/v1/places`

Get airport/city suggestions for autocomplete.

**Parameters:**
- `term` - Search term (minimum 2 characters)
- `limit` - Maximum results (default: 7)
- `locale` - Language code (default: en)

## Internationalization

### Polylang Integration

The plugin automatically registers all translatable strings with Polylang if it's active.

To re-register strings manually, visit:
```
your-site.com/?reregister_strings
```

### Supported Translations

All user-facing strings are translatable:
- UI labels
- Button text
- Validation messages
- Placeholder text

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Changelog

### 0.5.0 - 2024
- Complete plugin refactoring with separation of concerns
- Separated HTML, CSS, and JavaScript into dedicated files
- Object-oriented PHP architecture with dedicated classes
- Improved code organization and maintainability
- Added comprehensive documentation

### Previous Versions
- See `alibeyg-citynet-bridge-old.php` for legacy implementation

## License

This plugin is proprietary software developed for Alibeyg.

## Support

For support inquiries, please contact the Alibeyg development team.

## Credits

Developed by Alibeyg Development Team
