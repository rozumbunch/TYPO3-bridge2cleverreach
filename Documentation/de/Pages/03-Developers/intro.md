# Hinweise für Entwickler

- Flow-UUIDs und numerische Formular-IDs können pro Gruppe gemischt werden; die Erkennung erfolgt über das ID-Format.
- Zusatzgruppen liegen in `cleverreachGroups` in der Site-`config.yaml`, nicht in den Site Settings.
- Templates und Partials lassen sich im Sitepackage über `plugin.tx_bridge2cleverreach_subscribeform.view` / `…_unsubscribeform.view` überschreiben.
- Ist `bridge2cleverreach.includeCss` deaktiviert, muss das Sitepackage die Formular-Styles selbst bereitstellen.
- Nach Änderungen an Site Sets oder `settings.definitions.yaml` sollte der TYPO3-Cache geleert werden.

Die Dokumentationsquelle dieser Extension ist in `Configuration/SourcesForDocumentation.php` registriert und erscheint unter **Help > Documentation**, wenn documentationhub installiert ist.
