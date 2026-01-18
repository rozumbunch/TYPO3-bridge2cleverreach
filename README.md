[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-12.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)
[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-13.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)
[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-14.0-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)

# TYPO3 extension `bridge2cleverreach`

API bridge extension for seamless integration between TYPO3 and CleverReach email marketing platform.

## Features

- **Newsletter Subscription**: Subscribe users to CleverReach newsletters via API
- **Newsletter Unsubscription**: Allow users to unsubscribe from newsletters
- **Double Opt-In Support**: Full support for CleverReach Double Opt-In process
- **Site Configuration**: API credentials configurable in TYPO3 Site Configuration
- **Flexible Group Management**: Per-plugin instance group configuration with fallback to site configuration
- **Privacy Checkbox**: Configurable privacy checkbox for GDPR compliance
- **Multi-language Support**: Full translation support for frontend and backend
- **Route Enhancers**: Clean, SEO-friendly URLs with language-specific routes 
- **Content Elements**: Two plugins available as content elements (Subscribeform and Unsubscribeform)
- **Headless API**: RESTful API endpoint (`/api/cleverreach`) for headless integrations, supporting subscribe, unsubscribe, and get subscriber actions

## Links

|                    | URL                                                                                      |
|--------------------|------------------------------------------------------------------------------------------|
| **Repository:**    | https://github.com/rozumbunch/TYPO3-bridge2cleverreach                                   |
| **Documentation:** | https://github.com/rozumbunch/TYPO3-bridge2cleverreach                                   |
| **TER:**           | https://extensions.typo3.org/extension/bridge2cleverreach                                |
| **Packagist:**     | https://packagist.org/packages/rozumbunch/bridge2cleverreach                             |

## Installation

Require this package via composer:

```bash
composer req rozumbunch/bridge2cleverreach
```

## Quick Start

### 1. Configure API Credentials

Go to **Sites > [Your Site] > Configuration > Cleverreach** and enter:

- **Client Id**: Your CleverReach API Client ID
- **Client Secret**: Your CleverReach API Client Secret
- **List ID**: Default recipient list ID
- **Form ID (Double-Opt-In/Out)**: Form ID for subscription/unsubscription

### 2. Add Content Elements

Add the **Subscribeform** or **Unsubscribeform** content element to your page. Configure the plugin settings in the FlexForm:

- **CleverReach Group**: Optional alternative group ID (falls back to site configuration if empty)
- **Double Opt-In Mail ID**: Optional form ID override
- **Show Privacy Checkbox**: Enable/disable privacy checkbox

### 3. Configure Route Enhancers (Optional)

Import the route enhancers for clean URLs:

```yaml
# In your site config.yaml
imports:
  -
    resource: 'EXT:bridge2cleverreach/Configuration/Routes/routeEnhancers.yaml'
```


## Usage

For detailed documentation, refer to the [Extension Documentation](https://github.com/rozumbunch/TYPO3-bridge2cleverreach) in the TYPO3 backend under **Help > Documentation**.

## Requirements

- TYPO3 12.4+ / 13.0+ / 14.0+
- PHP 8.1+
- CleverReach API account with valid credentials

## Feedback and Support

You can reach us on the [Contact Form](https://www.rozumbunch.com/kontakt)
