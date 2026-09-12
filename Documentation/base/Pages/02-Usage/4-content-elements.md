# Content Elements

Add **Subscribeform** or **Unsubscribeform** to a page. Configure the plugin in the FlexForm:

- **Newsletter groups**: Groups this plugin instance applies to
- **Only technically required fields** (subscribe): if enabled, only the email address is required
- **Show Privacy Checkbox**: enable/disable the privacy checkbox
- **Privacy policy page**: page linked from the privacy checkbox text

The subscribe form can also use the content element's header and teaser (`bodytext`).

## Required fields

The subscription form includes:

- **First Name**: required unless “only technically required fields” is enabled
- **Last Name**: required unless “only technically required fields” is enabled
- **Email**: always required
- **Privacy Checkbox**: required if enabled

All form labels and messages are translatable in the language files.

## Route Enhancers (optional)

Import the route enhancers for clean URLs:

```yaml
imports:
  -
    resource: 'EXT:bridge2cleverreach/Configuration/Sets/Bridge2CleverReach/route-enhancers.yaml'
```

Adjust `limitToPages` and `limitToLanguages` to the pages and languages of the project. Example paths:

- German: `/anmeldung-ergebnis`, `/abmelden-ergebnis`
- English: `/subscribe-result`, `/unsubscribe-result`
