# Dokumentation für die Extension Bridge2Cleverreach

## Was macht die Extension

Die Extension Bridge2Cleverreach stellt die API-Anbindung an CleverReach bereit. Sie ermöglicht Newsletter-Anmeldung und -Abmeldung in TYPO3, sodass Redakteure Formulare ohne technisches Wissen über die CleverReach-API pflegen können.

Diese Dokumentation gibt es in zwei Sprachen:

- English (`Documentation/base/`)
- Deutsch (`Documentation/de/`)

---

## Wichtig: CleverReach REST-API-Änderung

**Diese Version berücksichtigt die Umstellung der CleverReach REST-API:**

> **Migrated the flows endpoints to a dedicated API.** Please refer to the [Flows API](Pages/02-Usage/2-api-and-flows.md) for more details.

Double Opt-In / Double Opt-Out nutzt die eigenständige **Flows API** (`POST /flow/flow/{id}/send`), sobald eine Flow-UUID konfiguriert ist. Numerische Formular-IDs aus bestehenden Installationen funktionieren weiter über die bisherigen Forms-Endpunkte.

---

## Funktionen

- **Newsletter-Anmeldung / -Abmeldung**: Anmeldung und Abmeldung über die CleverReach-API
- **Double Opt-In / Opt-Out**: eigenständige Flows API, mit Fallback auf die bisherige Forms API
- **Site Configuration**: API-Zugangsdaten in der TYPO3 Site Configuration
- **Site Sets**: Sets-Einbindung möglich (`rozumbunch/bridge2cleverreach`)
- **Flexible Group Management**: Gruppenkonfiguration pro Plugin-Instanz, Fallback auf die Site Configuration
- **Privacy Checkbox**: konfigurierbare Datenschutz-Checkbox für DSGVO-Konformität
- **Multi-language Support**: vollständige Übersetzungen für Frontend und Backend
- **Route Enhancers**: sprechende, SEO-freundliche URLs je Sprache
- **Content Elements**: zwei Plugins als Inhaltselemente (Anmeldeformular und Abmeldeformular)
- **Headless API**: REST-Endpunkt (`POST /cleverreach/api`) für Headless-Integrationen (Subscribe, Unsubscribe, Get Subscriber)

## Nächste Schritte

1. Extension installieren und Site Set aktivieren
2. API-Zugangsdaten in der Site Configuration eintragen
3. Optional weitere Newsletter-Gruppen anlegen
4. Anmeldeformular oder Abmeldeformular auf einer Seite platzieren
