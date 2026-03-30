import fs from "node:fs";
import http from "node:http";
import https from "node:https";
import path from "node:path";
import process from "node:process";
import puppeteer from "puppeteer-core";

function argValue(flag, defaultValue = null) {
  const idx = process.argv.indexOf(flag);
  if (idx === -1) return defaultValue;
  const v = process.argv[idx + 1];
  if (!v || v.startsWith("--")) return defaultValue;
  return v;
}

function hasFlag(flag) {
  return process.argv.includes(flag);
}

function exists(p) {
  try {
    fs.accessSync(p, fs.constants.X_OK);
    return true;
  } catch {
    return false;
  }
}

function resolveChromeExecutable() {
  const fromEnv = (process.env.PUPPETEER_EXECUTABLE_PATH || "").trim();
  if (fromEnv && exists(fromEnv)) return fromEnv;

  const candidates = [
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    "/Applications/Chromium.app/Contents/MacOS/Chromium",
    "/usr/bin/google-chrome",
    "/usr/bin/google-chrome-stable",
    "/usr/bin/chromium",
    "/usr/bin/chromium-browser",
  ];

  for (const c of candidates) {
    if (exists(c)) return c;
  }

  return "";
}

function extFromContentType(ct) {
  const v = (ct || "").toLowerCase().split(";")[0].trim();
  if (v === "image/jpeg") return ".jpg";
  if (v === "image/jpg") return ".jpg";
  if (v === "image/png") return ".png";
  if (v === "image/webp") return ".webp";
  if (v === "image/avif") return ".avif";
  if (v === "image/gif") return ".gif";
  if (v === "image/svg+xml") return ".svg";
  return "";
}

function requestBuffer(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    let u;
    try {
      u = new URL(url);
    } catch (e) {
      reject(e);
      return;
    }

    const mod = u.protocol === "https:" ? https : http;
    const req = mod.request(
      u,
      {
        method: "GET",
        headers: {
          "User-Agent":
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36 BwiserScraper/0.1",
          Accept: "image/avif,image/webp,image/apng,image/*,*/*;q=0.8",
          Referer: "https://www.mrdfood.com/",
        },
      },
      (res) => {
        const status = res.statusCode || 0;

        // Follow redirects (common on CDNs).
        if (status >= 300 && status < 400 && res.headers.location && redirects < 5) {
          const next = new URL(res.headers.location, u).toString();
          res.resume();
          requestBuffer(next, redirects + 1).then(resolve, reject);
          return;
        }

        if (status < 200 || status >= 300) {
          const chunks = [];
          res.on("data", (d) => chunks.push(d));
          res.on("end", () => {
            reject(new Error(`Failed to download image (${status}): ${url}`));
          });
          return;
        }

        const chunks = [];
        res.on("data", (d) => chunks.push(d));
        res.on("end", () => {
          resolve({ buf: Buffer.concat(chunks), headers: res.headers });
        });
      }
    );

    req.on("error", reject);
    req.end();
  });
}

async function downloadImage(url, outDir, baseName) {
  const { buf, headers } = await requestBuffer(url);

  const ct = String(headers["content-type"] || "");
  const extByType = extFromContentType(ct);
  const extByUrl = (() => {
    try {
      const u = new URL(url);
      const ext = path.extname(u.pathname || "");
      return ext && ext.length <= 8 ? ext : "";
    } catch {
      return "";
    }
  })();
  const ext = extByType || extByUrl || ".jpg";

  fs.mkdirSync(outDir, { recursive: true });
  const outPath = path.join(outDir, `${baseName}${ext}`);

  fs.writeFileSync(outPath, buf);

  return { outPath, contentType: ct, bytes: buf.length };
}

async function main() {
  const url = (argValue("--url", "") || process.argv[2] || "https://www.mrdfood.com/").trim();
  const outDir = (argValue("--out-dir", "") || "out").trim();
  const downloadDir = (argValue("--download-dir", "") || "").trim();
  const screenshot = hasFlag("--screenshot");
  const headful = hasFlag("--headful");
  const waitMs = Number.parseInt(argValue("--wait-ms", "4000"), 10) || 4000;

  // Specific element requested by the user.
  const xpath =
    (argValue("--xpath", "") ||
      "//*[@id=\"splide12-slide01\"]/section/article/div[1]/div[1]/img").trim();

  const executablePath = resolveChromeExecutable();
  if (!executablePath) {
    console.error("No Chrome/Chromium executable found.");
    console.error("Set PUPPETEER_EXECUTABLE_PATH and retry.");
    process.exit(2);
  }

  fs.mkdirSync(outDir, { recursive: true });
  const userDataDir = path.join(outDir, ".chrome-profile");

  const browser = await puppeteer.launch({
    headless: headful ? false : "new",
    executablePath,
    userDataDir,
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-gpu",
      "--no-first-run",
      "--no-default-browser-check",
      "--disable-breakpad",
      "--disable-crash-reporter",
    ],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 720, deviceScaleFactor: 1 });
    await page.setUserAgent(
      "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36 BwiserScraper/0.1"
    );

    page.setDefaultTimeout(60_000);
    page.setDefaultNavigationTimeout(60_000);

    const startedAt = Date.now();
    await page.goto(url, { waitUntil: "networkidle2" });

    // Prefer selectors (more robust across small DOM changes); fall back to the user-provided XPath.
    const selectorsToTry = [
      "#splide12-slide01 img",
      "#splide01-slide01 img",
      "[id^=\"splide\"][id$=\"-slide01\"] img",
    ];

    let selectedBy = "";
    let handle = null;

    const trySelectorsOnce = async () => {
      for (const sel of selectorsToTry) {
        const h = await page.$(sel);
        if (h) {
          selectedBy = `selector:${sel}`;
          return h;
        }
      }
      return null;
    };

    handle = await trySelectorsOnce();
    if (!handle && waitMs > 0) {
      await new Promise((r) => setTimeout(r, waitMs));
      handle = await trySelectorsOnce();
    }

    if (!handle) {
      const handles = await page.$x(xpath);
      handle = handles && handles[0] ? handles[0] : null;
      if (handle) selectedBy = `xpath:${xpath}`;
    }
    if (!handle) {
      // Diagnostics to help adjust selectors when the target element is dynamic.
      const debug = await page.evaluate(() => {
        const splideIds = Array.from(document.querySelectorAll("[id]"))
          .map((el) => el.id)
          .filter((id) => id && id.toLowerCase().includes("splide"))
          .slice(0, 80);

        const imgs = Array.from(document.querySelectorAll("img"))
          .slice(0, 40)
          .map((img) => {
            const attrs = {};
            for (const n of img.getAttributeNames()) attrs[n] = img.getAttribute(n);
            return {
              attrs,
              currentSrc: img.currentSrc || "",
              src: img.getAttribute("src") || "",
              alt: img.getAttribute("alt") || "",
            };
          });

        return { splideIds, imgs, url: location.href, title: document.title };
      });

      const debugPath = path.join(outDir, "mrd_special_debug.json");
      fs.writeFileSync(debugPath, JSON.stringify(debug, null, 2));

      if (screenshot) {
        const shotPath = path.join(outDir, "mrd_special_debug.png");
        await page.screenshot({ path: shotPath, fullPage: true });
      }

      throw new Error(`Could not find image element via selector or XPath: ${xpath}`);
    }

    const img = await page.evaluate((el) => {
      const attrs = {};
      for (const n of el.getAttributeNames()) attrs[n] = el.getAttribute(n);
      return {
        attrs,
        currentSrc: el.currentSrc || "",
        src: el.getAttribute("src") || "",
      };
    }, handle);

    const candidate =
      img.currentSrc ||
      img.src ||
      img.attrs["data-src"] ||
      img.attrs["data-lazy-src"] ||
      img.attrs["data-splide-lazy"] ||
      img.attrs["data-lazy"] ||
      "";

    if (!candidate) {
      throw new Error("Image element found, but no src/currentSrc/data-src attribute was present.");
    }

    const absoluteUrl = (() => {
      try {
        if (candidate.startsWith("//")) return `https:${candidate}`;
        return new URL(candidate, page.url()).toString();
      } catch {
        return candidate;
      }
    })();

    if (screenshot) {
      const shotPath = path.join(outDir, "mrd_special.png");
      await page.screenshot({ path: shotPath, fullPage: true });
    }

    const dlTargetDir = downloadDir ? path.resolve(downloadDir) : path.resolve(outDir);
    const dl = await downloadImage(absoluteUrl, dlTargetDir, "mrd-special");

    const meta = {
      page_url: page.url(),
      xpath,
      selected_by: selectedBy,
      image_url: absoluteUrl,
      downloaded_to: dl.outPath,
      content_type: dl.contentType,
      bytes: dl.bytes,
      fetched_at: new Date().toISOString(),
      duration_ms: Date.now() - startedAt,
      note:
        "Local-only prototype scrape. Ensure compliance with the target site's terms before using in production.",
    };

    const jsonPath = path.join(outDir, "mrd_special.json");
    fs.writeFileSync(jsonPath, JSON.stringify(meta, null, 2));

    console.log(`Saved: ${jsonPath}`);
    console.log(`Downloaded: ${dl.outPath}`);
  } finally {
    await browser.close();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
