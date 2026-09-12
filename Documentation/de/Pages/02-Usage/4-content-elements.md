# Inhaltselemente

**Anmeldeformular** oder **Abmeldeformular** auf einer Seite anlegen. Plugin-Einstellungen im FlexForm:

- **Newsletter-Gruppen**: Gruppen, für die diese Plugin-Instanz gilt
- **Nur technisch erforderliche Felder** (Anmeldung): wenn aktiv, ist nur die E-Mail-Adresse Pflicht
- **Datenschutz-Checkbox anzeigen**: Checkbox ein- oder ausblenden
- **Datenschutz-Seite**: Seite, die im Checkbox-Text verlinkt wird

Das Anmeldeformular kann zusätzlich Header und Teaser (`bodytext`) des Inhaltselements nutzen.

## Pflichtfelder

Das Anmeldeformular enthält:

- **Vorname**: Pflicht, außer „nur technisch erforderliche Felder“ ist aktiv
- **Nachname**: Pflicht, außer „nur technisch erforderliche Felder“ ist aktiv
- **E-Mail**: immer Pflicht
- **Datenschutz-Checkbox**: Pflicht, wenn aktiviert

Alle Formulartexte sind in den Sprachdateien übersetzbar.

## Route Enhancer (optional)

Route Enhancer für sprechende URLs importieren:

```yaml
imports:
  -
    resource: 'EXT:bridge2cleverreach/Configuration/Sets/Bridge2CleverReach/route-enhancers.yaml'
```

`limitToPages` und `limitToLanguages` an die Seiten und Sprachen des Projekts anpassen. Beispielpfade:

- Deutsch: `/anmeldung-ergebnis`, `/abmelden-ergebnis`
- Englisch: `/subscribe-result`, `/unsubscribe-result`
