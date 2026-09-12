# Site Set

The Site Set must be enabled in the site configuration:

```yaml
dependencies:
  - rozumbunch/bridge2cleverreach
```

The Set provides:

- TypoScript for both plugins (template, partial and layout paths)
- Content element registration for Subscribeform and Unsubscribeform
- Optional inclusion of `Resources/Public/Css/newsletter.css`

The fields then appear in the backend under:

```text
Site Management > Sites > [Site] > Settings
```

```yaml
bridge2cleverreach.includeCss: true
```

- `true`: include the extension CSS via `page.includeCSS.bridge2cleverreach`
- `false`: do not include the CSS (use this when the sitepackage provides its own styles)

After changes to `settings.definitions.yaml`, the TYPO3 cache should be cleared.
