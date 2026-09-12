# Documentation for Extension Bridge2Cleverreach

## What does it do

The Bridge2Cleverreach extension provides API integration with CleverReach. It enables newsletter subscription and unsubscription in TYPO3, so content editors can manage newsletter forms without technical knowledge of the CleverReach API.

This documentation is available in two languages:

- English (`Documentation/base/`)
- Deutsch (`Documentation/de/`)

---

## Important: CleverReach REST API change

**This version accounts for the CleverReach REST API change:**

> **Migrated the flows endpoints to a dedicated API.** Please refer to the [Flows API](Pages/02-Usage/2-api-and-flows.md) for more details.

Double Opt-In / Double Opt-Out now uses the dedicated **Flows API** (`POST /flow/flow/{id}/send`) when a Flow UUID is configured. Legacy numeric Form IDs continue to work against the previous Forms endpoints.

---

## Features

- **Newsletter Subscription / Unsubscription**: Subscribe and unsubscribe via the CleverReach API
- **Double Opt-In / Opt-Out**: Dedicated Flows API, with fallback to the legacy Forms API
- **Site Configuration**: API credentials configurable in TYPO3 Site Configuration
- **Site Sets**: Site Set integration is possible (`rozumbunch/bridge2cleverreach`)
- **Flexible Group Management**: Per-plugin instance group configuration with fallback to site configuration
- **Privacy Checkbox**: Configurable privacy checkbox for GDPR compliance
- **Multi-language Support**: Full translation support for frontend and backend
- **Route Enhancers**: Clean, SEO-friendly URLs with language-specific routes
- **Content Elements**: Two plugins available as content elements (Subscribeform and Unsubscribeform)
- **Headless API**: RESTful API endpoint (`POST /cleverreach/api`) for headless integrations, supporting subscribe, unsubscribe, and get subscriber actions

## Next steps

1. Install the extension and enable the Site Set
2. Enter API credentials in the Site Configuration
3. Optionally add additional newsletter groups
4. Place Subscribeform or Unsubscribeform on a page
