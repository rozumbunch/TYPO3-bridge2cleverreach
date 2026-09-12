# Installation und Konfiguration

1. Extension per Composer installieren

```bash
composer req rozumbunch/bridge2cleverreach
```

2. Site Set in der Site-Konfiguration aktivieren

```yaml
dependencies:
  - rozumbunch/bridge2cleverreach
```

3. API-Zugangsdaten eintragen

Unter **Sites > [Ihre Site] > Configuration > Cleverreach** Client Id, Client Secret, Standard List ID und Standard Formular ID (Flow-UUID oder bisherige Formular-ID) eintragen.

4. Inhaltselement anlegen

**Anmeldeformular** oder **Abmeldeformular** auf einer Seite platzieren und das Plugin im FlexForm konfigurieren.

## Klassischer Weg ohne Site Set

Ohne Site Set das Static Template **Bridge to CleverReach** im Template-Datensatz (`sys_template`) einbinden. Constants für View-Pfade stehen im Constant Editor zur Verfügung.
