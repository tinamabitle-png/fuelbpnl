import fs from "node:fs";
import http from "node:http";
import https from "node:https";
import process from "node:process";

function argValue(flag, defaultValue = null) {
  const idx = process.argv.indexOf(flag);
  if (idx === -1) return defaultValue;
  const v = process.argv[idx + 1];
  if (!v || v.startsWith("--")) return defaultValue;
  return v;
}

function toInt(v, fallback) {
  const n = Number.parseInt(String(v || ""), 10);
  return Number.isFinite(n) ? n : fallback;
}

const listenPort = toInt(argValue("--listen", "8443"), 8443);
const target = String(argValue("--target", "http://127.0.0.1:8000")).trim();
const keyPath = String(
  argValue("--key", "storage/ssl/localhost/localhost.key")
).trim();
const certPath = String(
  argValue("--cert", "storage/ssl/localhost/localhost.crt")
).trim();

if (!fs.existsSync(keyPath) || !fs.existsSync(certPath)) {
  console.error("Missing TLS files:");
  console.error("  key :", keyPath);
  console.error("  cert:", certPath);
  console.error("Run: bash scripts/create_localhost_cert.sh");
  process.exit(2);
}

const key = fs.readFileSync(keyPath);
const cert = fs.readFileSync(certPath);

function makeProxyHeaders(req) {
  const headers = { ...req.headers };

  // Ensure Laravel sees https and generates https asset URLs (TrustProxies already enabled).
  headers["x-forwarded-proto"] = "https";
  headers["x-forwarded-port"] = String(listenPort);
  headers["x-forwarded-host"] = req.headers.host || `localhost:${listenPort}`;

  // Keep the original host in case the app wants it.
  headers["x-original-host"] = req.headers.host || "";

  return headers;
}

const server = https.createServer({ key, cert }, (req, res) => {
  const upstream = new URL(target);
  const options = {
    protocol: upstream.protocol,
    hostname: upstream.hostname,
    port: upstream.port,
    method: req.method,
    path: req.url,
    headers: makeProxyHeaders(req),
  };

  const proxyReq = http.request(options, (proxyRes) => {
    res.writeHead(proxyRes.statusCode || 502, proxyRes.headers);
    proxyRes.pipe(res);
  });

  proxyReq.on("error", (err) => {
    res.statusCode = 502;
    res.setHeader("content-type", "text/plain; charset=utf-8");
    res.end(`Proxy error: ${err.message}`);
  });

  req.pipe(proxyReq);
});

server.on("clientError", (err, socket) => {
  try {
    socket.end("HTTP/1.1 400 Bad Request\r\n\r\n");
  } catch {
    // ignore
  }
});

server.listen(listenPort, "0.0.0.0", () => {
  console.log(`HTTPS proxy listening on https://localhost:${listenPort}`);
  console.log(`Forwarding to ${target}`);
});

