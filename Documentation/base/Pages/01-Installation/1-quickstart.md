# Install and Configuration

1. Install the extension via Composer

```bash
composer req rozumbunch/bridge2cleverreach
```

2. Enable the Site Set in the site configuration

```yaml
dependencies:
  - rozumbunch/bridge2cleverreach
```

3. Enter API credentials

Go to **Sites > [Your Site] > Configuration > Cleverreach** and enter Client Id, Client Secret, Default List ID and Default Form ID (Flow UUID or legacy Form ID).

4. Add a content element

Add **Subscribeform** or **Unsubscribeform** to a page and configure the plugin in the FlexForm.

## Classic approach without Site Set

If no Site Set is used, include the static template **Bridge to CleverReach** in the template record (`sys_template`). Constants for view paths are available in the Constant Editor.
