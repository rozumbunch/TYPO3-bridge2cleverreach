# API-Zugangsdaten und Flows API

## API-Zugangsdaten

Unter **Sites > [Ihre Site] > Configuration > Cleverreach** eintragen:

| Feld | Beschreibung |
|---|---|
| **Client Id** | CleverReach API Client ID |
| **Client Secret** | CleverReach API Client Secret |
| **Standard List ID** | ID der Standard-Empfängerliste |
| **Bezeichnung der Standard-Listen-ID** | Optionale Anzeigebezeichnung im Plugin-Select und im Frontend |
| **Standard Formular ID (Double-Opt-In/Out)** | Flow-UUID oder bisherige numerische Formular-ID |

Das OAuth-Token muss den Scope `oa_flows` enthalten. Für bestehende numerische Formular-IDs bleibt `oa_forms` erforderlich.

## CleverReach Flows API

CleverReach hat die bisherigen Flow-Endpunkte in eine eigene API überführt:

> Migrated the flows endpoints to a dedicated API. Please refer to the Flows API for more details.

Die Extension erkennt das ID-Format und ruft den passenden Endpunkt auf.

| Kennung | Beispiel | Endpunkt | Payload |
|---|---|---|---|
| **Flow-UUID** | `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee` | `POST /flow/flow/{id}/send` | `{ "receiver_id": "…", "doidata": { … } }` |
| **Bisherige Formular-ID** | `123456` | `POST /v3/forms.json/{id}/send/activate` bzw. `…/deactivate` | `{ "email": "…", "doidata": { … } }` |

Das Formular-ID-Feld in der Site Configuration (und je Zusatzgruppe) akzeptiert beide Formate. Neue CleverReach-Accounts sollten die Flow-UUID aus der Flows-Oberfläche verwenden. Bestehende numerische Formular-IDs bleiben unterstützt.

Umsetzung: `Classes/Api/ApiManager.php` (`isFlowIdentifier()`, `getDoiActivateEndpoint()`, `getDoiDeactivateEndpoint()`).
