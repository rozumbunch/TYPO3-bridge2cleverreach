# Notes for Developers

- Flow UUIDs and numeric Form IDs can be mixed per group; detection is based on the identifier format.
- Additional groups live in `cleverreachGroups` in the site `config.yaml`, not in Site Settings.
- Templates and partials can be overridden in the sitepackage via `plugin.tx_bridge2cleverreach_subscribeform.view` / `…_unsubscribeform.view`.
- When `bridge2cleverreach.includeCss` is disabled, the sitepackage must provide the form styles.
- After changes to Site Sets or `settings.definitions.yaml`, the TYPO3 cache should be cleared.

The documentation source for this extension is registered in `Configuration/SourcesForDocumentation.php` and is shown in **Help > Documentation** when documentationhub is installed.
