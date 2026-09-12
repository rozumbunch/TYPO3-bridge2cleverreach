# API credentials and Flows API

## API credentials

Go to **Sites > [Your Site] > Configuration > Cleverreach** and enter:

| Field | Description |
|---|---|
| **Client Id** | CleverReach API Client ID |
| **Client Secret** | CleverReach API Client Secret |
| **Default List ID** | Default recipient list ID |
| **Label for default list ID** | Optional display name used in the plugin select and in the frontend |
| **Default Form ID (Double-Opt-In/Out)** | Flow UUID or legacy numeric Form ID |

The OAuth token must include the scope `oa_flows`. For existing numeric Form IDs, `oa_forms` is still required.

## CleverReach Flows API

CleverReach moved the previous flow endpoints into a dedicated API:

> Migrated the flows endpoints to a dedicated API. Please refer to the Flows API for more details.

This extension detects the identifier format and calls the matching endpoint.

| Identifier | Example | Endpoint | Payload |
|---|---|---|---|
| **Flow UUID** | `aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee` | `POST /flow/flow/{id}/send` | `{ "receiver_id": "…", "doidata": { … } }` |
| **Legacy Form ID** | `123456` | `POST /v3/forms.json/{id}/send/activate` or `…/deactivate` | `{ "email": "…", "doidata": { … } }` |

The Form ID field in the Site Configuration (and per additional group) accepts both formats. New CleverReach accounts should use the Flow UUID from the Flows UI. Existing numeric Form IDs remain supported.

Implementation: `Classes/Api/ApiManager.php` (`isFlowIdentifier()`, `getDoiActivateEndpoint()`, `getDoiDeactivateEndpoint()`).
