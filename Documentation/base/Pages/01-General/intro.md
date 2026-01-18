# Introduction

## Overview

The Bridge2Cleverreach extension provides seamless integration between TYPO3 and CleverReach, enabling newsletter subscription and unsubscription functionality directly within your TYPO3 website. This extension handles all API communication with CleverReach, making it easy for content editors to manage newsletter forms without technical knowledge.

## 1. API Configuration in Site Configuration

The extension requires API credentials to be configured in the TYPO3 Site Configuration. These settings can be entered in the backend under **Sites > [Your Site] > Configuration > Cleverreach**:

### Required Configuration Fields:

- **Client Id**: Your CleverReach API Client ID
- **Client Secret**: Your CleverReach API Client Secret
- **List ID**: The default recipient list ID for your CleverReach account
- **Form ID (Double-Opt-In/Out)**: The ID of the form used for newsletter subscription/unsubscription

These credentials are stored at the site level, allowing different configurations for different sites or languages if needed.

## 2. Plugins: Subscription and Unsubscription

The extension provides two content element plugins:

### Subscribeform Plugin
- Allows users to subscribe to newsletters
- Collects first name, last name, and email address
- Supports Double-Opt-In process via CleverReach
- Can be configured with an alternative group ID per plugin instance

### Unsubscribeform Plugin
- Allows users to unsubscribe from newsletters
- Requires only email address
- Can be configured with an alternative group ID per plugin instance

### Alternative Group Configuration

Each plugin instance can define its own CleverReach group ID in the FlexForm settings. If no group is specified in the plugin configuration, the extension falls back to the group ID defined in the site configuration. This allows flexibility for different newsletter lists on different pages.

## 3. Configuration for Required Fields and Privacy Notices

### Privacy Checkbox

The subscription form can display a privacy checkbox that users must accept before subscribing. This can be enabled/disabled in the FlexForm settings:

- **Show Privacy Checkbox**: Toggle to show or hide the privacy checkbox
- The checkbox text and link to the privacy policy page are configurable via TypoScript constants

### Required Fields

The subscription form includes the following fields:
- **First Name**: Optional field
- **Last Name**: Optional field
- **Email**: Required field
- **Privacy Checkbox**: Required if enabled

All form labels and messages are translatable and can be customized in the language files.

## 4. Route Enhancers

The extension includes route enhancers for clean, SEO-friendly URLs:

### German (languageId: 0)
- **Subscribeform**: `/ergebnis` - Result page after subscription
- **Unsubscribeform**: `/ergebnis` - Result page after unsubscription

### English (languageId: 1)
- **Subscribeform**: `/result` - Result page after subscription
- **Unsubscribeform**: `/result` - Result page after unsubscription

The route enhancers are automatically applied based on the current language, ensuring that users see the appropriate URL structure for their language. These routes work on any page where the plugin is placed, making the URLs relative to the current page.

### Configuration

The route enhancers are defined in `Configuration/Routes/routeEnhancers.yaml` and can be imported into your site configuration via:

```yaml
imports:
  -
    resource: 'EXT:bridge2cleverreach/Configuration/Routes/routeEnhancers.yaml'
```

Alternatively, you can copy the route enhancer definitions directly into your site's `config.yaml` file under the `routeEnhancers` section.

## 5. Headless API

The extension provides a headless API endpoint for integration with external applications, JavaScript frameworks, or mobile apps. The API is accessible via a Request Middleware that processes JSON requests and returns structured JSON responses.

### API Endpoint

The headless API is available at:
```
POST /api/cleverreach
```

### Supported Actions

The API supports three actions via the `action` parameter:

#### Subscribe (`action: "subscribe"`)
Subscribes a user to the newsletter with Double-Opt-In process.

**Request Body:**
```json
{
  "action": "subscribe",
  "email": "user@example.com",
  "name": "Max",
  "surname": "Mustermann"
}
```

**Required Parameters:**
- `action`: Must be `"subscribe"`
- `email`: Valid email address (required)

**Optional Parameters:**
- `name`: First name
- `surname`: Last name

**Response:**
```json
{
  "success": true,
  "message": "Newsletter-Anmeldung erfolgreich. Bitte bestätigen Sie Ihre Email-Adresse."
}
```

#### Unsubscribe (`action: "unsubscribe"`)
Unsubscribes a user from the newsletter.

**Request Body:**
```json
{
  "action": "unsubscribe",
  "email": "user@example.com"
}
```

**Required Parameters:**
- `action`: Must be `"unsubscribe"`
- `email`: Valid email address (required)

**Response:**
```json
{
  "success": true,
  "message": "Newsletter-Abmeldung erfolgreich"
}
```

#### Get Subscriber (`action: "get"`)
Retrieves subscriber information from CleverReach.

**Request Body:**
```json
{
  "action": "get",
  "email": "user@example.com",
  "groupId": 123
}
```

**Required Parameters:**
- `action`: Must be `"get"`
- `email`: Valid email address (required)

**Optional Parameters:**
- `groupId`: CleverReach group ID (if not provided, searches globally)

**Response:**
```json
{
  "success": true,
  "data": {
    // Subscriber data from CleverReach
  },
  "message": "Subscriber gefunden"
}
```

### Error Handling

All API endpoints return appropriate HTTP status codes:
- `200`: Success
- `400`: Bad Request (validation errors, missing parameters)
- `405`: Method Not Allowed (only POST requests are accepted)
- `500`: Internal Server Error (configuration issues, API errors)

**Error Response Format:**
```json
{
  "success": false,
  "message": "Error description"
}
```

### Configuration

The headless API uses the same site configuration as the plugins:
- **Client Id**: Required for all actions
- **Client Secret**: Required for all actions
- **List ID**: Required for subscribe and unsubscribe actions
- **Form ID (Double-Opt-In)**: Required for subscribe action

These settings are automatically read from the TYPO3 Site Configuration.

### Usage Example

**JavaScript/Fetch:**
```javascript
fetch('/api/cleverreach', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    action: 'subscribe',
    email: 'user@example.com',
    name: 'Max',
    surname: 'Mustermann'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Success:', data.message);
  } else {
    console.error('Error:', data.message);
  }
});
```

### Implementation Details

The headless API is implemented as a TYPO3 Request Middleware (`RequestMiddleware`) that:
- Validates all incoming parameters
- Uses the `ApiManager` for all CleverReach API communication
- Handles authentication automatically
- Returns consistent JSON responses
- Supports both JSON and form-data request bodies

The middleware is automatically registered via `Configuration/Services.yaml` and processes requests before they reach the TYPO3 frontend rendering.
