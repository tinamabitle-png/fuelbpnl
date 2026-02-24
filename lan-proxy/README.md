# LAN Proxy for Laravel

Use this Express proxy to expose Laravel API over LAN for the Flutter app.

## 1) Start Laravel API

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## 2) Start proxy

```bash
cd lan-proxy
npm install
npm start
```

Defaults:
- Proxy URL: `http://0.0.0.0:3001`
- Target Laravel: `http://127.0.0.1:8000`

Optional:

```bash
HOST=0.0.0.0 PORT=3001 LARAVEL_TARGET=http://127.0.0.1:8000 npm start
```

Mobile app default base URL is configured as:

`http://192.168.0.100:3001/api/v1`

Update in `mobile_app/lib/core/session_store.dart` for your LAN IP.

