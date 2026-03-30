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

function parseSrcset(srcset) {
  const out = [];
  const s = (srcset || "").trim();
  if (!s) return out;
  for (const raw of s.split(",")) {
    const part = raw.trim();
    if (!part) continue;
    const pieces = part.split(/\s+/).filter(Boolean);
    const url = pieces[0] || "";
    const desc = pieces[1] || "";
    const m = desc.match(/^(\d+)w$/);
    const w = m ? Number.parseInt(m[1], 10) : 0;
    if (url) out.push({ url, w });
  }
  return out;
}

function widthFromUrl(u) {
  try {
    const p = new URL(u).pathname || "";
    const m = p.match(/\/(\d+)x0\//);
    return m ? Number.parseInt(m[1], 10) : 0;
  } catch {
    return 0;
  }
}

function pickBestFromSrcset(srcset, baseUrl, maxW) {
  const entries = parseSrcset(srcset).map((e) => ({
    ...e,
    abs: (() => {
      try {
        if (e.url.startsWith("//")) return `https:${e.url}`;
        return new URL(e.url, baseUrl).toString();
      } catch {
        return e.url;
      }
    })(),
  }));
  if (!entries.length) return "";

  // Prefer the largest <= maxW; otherwise the absolute largest.
  const under = entries.filter((e) => e.w && e.w <= maxW).sort((a, b) => b.w - a.w);
  if (under.length) return under[0].abs;
  const any = entries.sort((a, b) => (b.w || 0) - (a.w || 0));
  return any[0].abs;
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
  const maxW = Number.parseInt(argValue("--max-w", "2000"), 10) || 2000;

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

    // 1) Try the user-provided XPath exactly (after scrolling, since some sections are lazy-loaded).
    let selectedBy = "";
    let handle = null;

    const tryXPath = async () => {
      const handles = await page.$x(xpath);
      return handles && handles[0] ? handles[0] : null;
    };

    handle = await tryXPath();

    if (!handle) {
      // Scroll to trigger lazy sections.
      for (let i = 0; i < 4; i++) {
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
        await new Promise((r) => setTimeout(r, 1200));
        handle = await tryXPath();
        if (handle) break;
      }
    }

    if (handle) {
      selectedBy = `xpath:${xpath}`;
    } else {
      // 2) Fallback: pick the best visible "hero/special" image by size and srcset width.
      const candidates = await page.evaluate(() => {
        const take = Array.from(document.querySelectorAll("img")).slice(0, 220);
        return take.map((img) => {
          const r = img.getBoundingClientRect();
          const attrs = {};
          for (const n of img.getAttributeNames()) attrs[n] = img.getAttribute(n);
          return {
            currentSrc: img.currentSrc || "",
            src: img.getAttribute("src") || "",
            srcset: img.getAttribute("srcset") || "",
            alt: img.getAttribute("alt") || "",
            w: Math.max(0, Math.round(r.width || 0)),
            h: Math.max(0, Math.round(r.height || 0)),
            attrs,
          };
        });
      });

      // Heuristic: avoid tiny "seo" thumbnails (e.g. 32x0) and prefer large visible images.
      let best = null;
      for (const c of candidates) {
        const base = c.currentSrc || c.src || "";
        if (!base) continue;
        const area = (c.w || 0) * (c.h || 0);
        const wFromUrl = widthFromUrl(base);
        const score = Math.max(area, wFromUrl * 10);
        const looksTiny = wFromUrl > 0 && wFromUrl <= 64;
        if (looksTiny) continue;
        if (!best || score > best.score) best = { ...c, score };
      }

      if (!best) {
        // If everything looked tiny, just take the first image with a URL.
        const fallback = candidates.find((c) => (c.currentSrc || c.src || "").length > 0);
        if (fallback) best = { ...fallback, score: 0 };
      }

      if (!best) {
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

        throw new Error(`Could not find image element via XPath, and no fallback image candidates were found.`);
      }

      const baseUrl = page.url();
      const bestSrcset = best.srcset || best.attrs?.srcset || "";
      const bestUrlFromSrcset = pickBestFromSrcset(bestSrcset, baseUrl, maxW);
      const bestBase = best.currentSrc || best.src || "";
      const bestUrlFinal = bestUrlFromSrcset || bestBase;

      const absoluteUrl = (() => {
        try {
          if (bestUrlFinal.startsWith("//")) return `https:${bestUrlFinal}`;
          return new URL(bestUrlFinal, baseUrl).toString();
        } catch {
          return bestUrlFinal;
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
        selected_by: "heuristic:largest-visible-image",
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
      return;
    }

    // If we found the exact element via XPath, extract its best URL (prefer srcset).
    const img = await page.evaluate((el) => {
      const attrs = {};
      for (const n of el.getAttributeNames()) attrs[n] = el.getAttribute(n);
      return {
        attrs,
        currentSrc: el.currentSrc || "",
        src: el.getAttribute("src") || "",
        srcset: el.getAttribute("srcset") || "",
      };
    }, handle);

    const bestUrlFromSrcset = pickBestFromSrcset(img.srcset || img.attrs?.srcset || "", page.url(), maxW);

    const candidate =
      bestUrlFromSrcset ||
      img.currentSrc ||
      img.src ||
      img.attrs["data-src"] ||
      img.attrs["data-lazy-src"] ||
      img.attrs["data-splide-lazy"] ||
      img.attrs["data-lazy"] ||
      "";

    if (!candidate) {
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
      throw new Error(`Image element found but no URL candidate could be extracted.`);
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
