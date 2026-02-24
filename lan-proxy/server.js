import express from 'express';
import cors from 'cors';
import { createProxyMiddleware } from 'http-proxy-middleware';

const app = express();

const host = process.env.HOST || '0.0.0.0';
const port = Number(process.env.PORT || 3001);
const target = process.env.LARAVEL_TARGET || 'http://127.0.0.1:8000';

app.use(
  cors({
    origin: '*',
    methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'Accept'],
  }),
);

app.get('/health', (_req, res) => {
  res.json({
    ok: true,
    proxy: true,
    target,
  });
});

app.use(
  '/',
  createProxyMiddleware({
    target,
    changeOrigin: true,
    ws: false,
    xfwd: true,
    logLevel: 'warn',
  }),
);

app.listen(port, host, () => {
  console.log(`[lan-proxy] listening on http://${host}:${port} -> ${target}`);
});

