# Headless API

The headless API is available at:

```text
POST /cleverreach/api
```

Supported actions: `subscribe`, `unsubscribe`, `get`.

## Subscribe

```json
{
  "action": "subscribe",
  "email": "user@example.com",
  "name": "Max",
  "surname": "Mustermann",
  "newsletter": "Politics & Society"
}
```

`newsletter` is optional. Without it, the default group is used. Multiple group names can be passed as a comma-separated list.

## Unsubscribe

```json
{
  "action": "unsubscribe",
  "email": "user@example.com"
}
```

## Get subscriber

```json
{
  "action": "get",
  "email": "user@example.com",
  "groupId": 123
}
```

## Error handling

HTTP status codes: `200` success, `400` validation error, `405` method not allowed (POST only), `500` configuration or API error.

```json
{
  "status": false,
  "message": "Error description"
}
```

## Usage example

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

The middleware is registered in `Configuration/RequestMiddlewares.php` and uses the same Site Configuration and group mapping as the plugins.
