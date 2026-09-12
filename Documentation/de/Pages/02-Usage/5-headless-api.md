# Headless-API

Die Headless-API ist erreichbar unter:

```text
POST /cleverreach/api
```

Unterstützte Actions: `subscribe`, `unsubscribe`, `get`.

## Anmeldung

```json
{
  "action": "subscribe",
  "email": "user@example.com",
  "name": "Max",
  "surname": "Mustermann",
  "newsletter": "Politik & Gesellschaft"
}
```

`newsletter` ist optional. Ohne Angabe gilt die Standardgruppe. Mehrere Gruppennamen können kommagetrennt übergeben werden.

## Abmeldung

```json
{
  "action": "unsubscribe",
  "email": "user@example.com"
}
```

## Subscriber abfragen

```json
{
  "action": "get",
  "email": "user@example.com",
  "groupId": 123
}
```

## Fehlerbehandlung

HTTP-Statuscodes: `200` Erfolg, `400` Validierungsfehler, `405` Methode nicht erlaubt (nur POST), `500` Konfigurations- oder API-Fehler.

```json
{
  "status": false,
  "message": "Error description"
}
```

## Nutzungsbeispiel

```javascript
fetch('/cleverreach/api', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    action: 'subscribe',
    email: 'user@example.com',
    name: 'Max',
    surname: 'Mustermann'
  })
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Success:', data.message);
  } else {
    console.error('Error:', data.message);
  }
});
```

Die Middleware ist in `Configuration/RequestMiddlewares.php` registriert und nutzt dieselbe Site Configuration und dasselbe Gruppen-Mapping wie die Plugins.
