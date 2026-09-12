# Site Set

Das Set muss in der Site-Konfiguration aktiviert sein:

```yaml
dependencies:
  - rozumbunch/bridge2cleverreach
```

Das Set liefert:

- TypoScript für beide Plugins (Template-, Partial- und Layout-Pfade)
- Registrierung der Inhaltselemente Anmeldeformular und Abmeldeformular
- optionale Einbindung von `Resources/Public/Css/newsletter.css`

Die Felder erscheinen im Backend unter:

```text
Site Management > Sites > [Site] > Settings
```

```yaml
bridge2cleverreach.includeCss: true
```

- `true`: Extension-CSS über `page.includeCSS.bridge2cleverreach` einbinden
- `false`: CSS nicht einbinden (wenn das Sitepackage eigene Styles mitbringt)

Nach Änderungen an `settings.definitions.yaml` sollte der TYPO3-Cache geleert werden.
