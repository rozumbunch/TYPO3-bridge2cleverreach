[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-12.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)
[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-13.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)
[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-14.0-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)

# TYPO3 extension `bridge2cleverreach`

API bridge extension for seamless integration between TYPO3 and the CleverReach email marketing platform.

|                    | URL                                                                          |
|--------------------|------------------------------------------------------------------------------|
| **Repository:**    | https://github.com/rozumbunch/TYPO3-bridge2cleverreach                       |
| **Read Extension Manual:** | [English](https://github.com/rozumbunch/TYPO3-bridge2cleverreach/blob/main/Documentation/base/index.md) · [Deutsch](https://github.com/rozumbunch/TYPO3-bridge2cleverreach/blob/main/Documentation/de/index.md) |
| **TER:**           | https://extensions.typo3.org/extension/bridge2cleverreach                    |
| **Packagist:**     | https://packagist.org/packages/rozumbunch/bridge2cleverreach                 |

This extension connects TYPO3 with CleverReach. After installation and configuration of the API credentials, newsletter subscribe and unsubscribe forms are available as content elements. Double Opt-In is handled via the CleverReach API.

---

## Important: CleverReach REST API change

**This version accounts for the CleverReach REST API change:**

> **Migrated the flows endpoints to a dedicated API.** Please refer to the [Flows API](Documentation/base/Pages/02-Usage/2-api-and-flows.md) for more details.

Double Opt-In / Double Opt-Out now uses `POST /flow/flow/{id}/send` when a Flow UUID is configured. Legacy numeric Form IDs continue to work.

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

## Installation

```bash
composer req rozumbunch/bridge2cleverreach
```

Usage, Site Set, Flows API, group management and the headless API are documented in the extension manual:

- [English](Documentation/base/index.md)
- [Deutsch](Documentation/de/index.md)

In the TYPO3 backend the same documentation is available via **Help > Documentation** when [documentationhub](https://github.com/rozumbunch/TYPO3-documentation) is installed.

## Requirements

- TYPO3 12.4+ / 13.0+ / 14.0+
- PHP 8.1+
- CleverReach API account with valid credentials and the OAuth scope `oa_flows` (plus `oa_forms` for legacy Form IDs)

## Feedback and Support

You can reach us on the [Contact Form](https://www.rozumbunch.com/kontakt)

---

## Deutsch

[← English version](#typo3-extension-bridge2cleverreach)

# TYPO3-Extension `bridge2cleverreach`

API-Bridge für die Integration von TYPO3 mit der E-Mail-Marketing-Plattform CleverReach.

Nach Installation und Konfiguration der API-Zugangsdaten stehen Anmelde- und Abmeldeformulare als Inhaltselemente zur Verfügung. Double Opt-In läuft über die CleverReach-API.

---

## Wichtig: CleverReach REST-API-Änderung

**Diese Version berücksichtigt die Umstellung der CleverReach REST-API:**

> **Migrated the flows endpoints to a dedicated API.** Please refer to the [Flows API](Documentation/de/Pages/02-Usage/2-api-and-flows.md) for more details.

Double Opt-In / Double Opt-Out nutzt `POST /flow/flow/{id}/send`, sobald eine Flow-UUID konfiguriert ist. Numerische Formular-IDs funktionieren weiter.

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

## Installation

```bash
composer req rozumbunch/bridge2cleverreach
```

Nutzung, Site Set, Flows API, Gruppenverwaltung und Headless-API stehen im Extension-Manual:

- [Deutsch](Documentation/de/index.md)
- [English](Documentation/base/index.md)

Im TYPO3-Backend ist dieselbe Dokumentation über **Help > Documentation** erreichbar, wenn [documentationhub](https://github.com/rozumbunch/TYPO3-documentation) installiert ist.

## Voraussetzungen

- TYPO3 12.4+ / 13.0+ / 14.0+
- PHP 8.1+
- CleverReach-API-Account mit gültigen Zugangsdaten und OAuth-Scope `oa_flows` (plus `oa_forms` für bisherige Formular-IDs)

## Feedback und Support

Erreichbar über das [Kontaktformular](https://www.rozumbunch.com/kontakt)
