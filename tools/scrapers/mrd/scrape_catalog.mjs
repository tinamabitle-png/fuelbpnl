import fs from "node:fs";
import os from "node:os";
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

function slugify(input) {
  return String(input || "")
    .toLowerCase()
    .replace(/['"]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80);
}

function titleCaseFromSlug(slug) {
  const s = String(slug || "")
    .replace(/[-_]+/g, " ")
    .trim();
  if (!s) return "Featured";
  return s.replace(/\b\w/g, (m) => m.toUpperCase());
}

function absolutize(url, baseUrl) {
  try {
    if (!url) return "";
    if (url.startsWith("//")) return `https:${url}`;
    return new URL(url, baseUrl).toString();
  } catch {
    return url || "";
  }
}

function deriveCategory(urlStr) {
  try {
    const u = new URL(urlStr, "https://www.mrdfood.com");
    const parts = (u.pathname || "").split("/").filter(Boolean);
    const idx = parts.findIndex((p) => p === "food");
    // Example: /delivery/restaurants/food/hot-deals
    if (idx >= 0 && parts[idx + 1]) {
      const id = slugify(parts[idx + 1]);
      return { id, name: titleCaseFromSlug(id) };
    }
  } catch {
    // ignore
  }
  return { id: "featured", name: "Featured" };
}

function bumpMrdCdnWidth(url, targetW) {
  try {
    const u = new URL(url);
    const p = u.pathname || "";
    const m = p.match(/\/(\d+)x0\//);
    if (!m) return "";
    const currentW = Number.parseInt(m[1], 10) || 0;
    if (currentW <= 0 || currentW >= targetW) return "";
    u.pathname = p.replace(/\/\d+x0\//, `/${targetW}x0/`);
    return u.toString();
  } catch {
    return "";
  }
}

function extractItemsFromJsonPayload(payload) {
  const out = [];
  const seen = new Set();

  const getStr = (v) => (typeof v === "string" ? v : "");
  const pickImg = (o) =>
    getStr(o.logoUrl) ||
    getStr(o.logo_url) ||
    getStr(o.logo) ||
    getStr(o.imageUrl) ||
    getStr(o.image_url) ||
    getStr(o.image) ||
    getStr(o.thumbnailUrl) ||
    getStr(o.thumbnail) ||
    getStr(o.avatarUrl) ||
    "";

  const push = (o) => {
    if (!o || typeof o !== "object") return;
    const name = String(o.name || o.title || o.displayName || "").trim();
    if (!name) return;

    const img = pickImg(o);
    const id = o.id ?? o.restaurantId ?? o.vendorId ?? null;
    const slug = getStr(o.slug || o.seoSlug || o.urlSlug || "");

    // Construct a link when possible (matches observed MRD patterns).
    const url =
      getStr(o.url || o.href || o.link || "") ||
      (slug && id !== null ? `/delivery/restaurants/${slug}/${id}` : "") ||
      "";

    if (!img && !url) return;
    if (img && !/mrd|img\\.mrd/i.test(img)) {
      // Reduce false positives when crawling random JSON payloads.
      return;
    }

    const key = `${url}::${name}`.toLowerCase().slice(0, 240);
    if (seen.has(key)) return;
    seen.add(key);

    out.push({
      name,
      url,
      image_url: img,
      description: String(o.description || o.subtitle || "").trim(),
      source: "network_json",
    });
  };

  const walk = (v, depth) => {
    if (depth > 9) return;
    if (!v) return;
    if (Array.isArray(v)) {
      for (const it of v) walk(it, depth + 1);
      return;
    }
    if (typeof v !== "object") return;

    push(v);
    for (const k of Object.keys(v)) walk(v[k], depth + 1);
  };

  walk(payload, 0);
  return out;
}

async function main() {
  const url = (argValue("--url", "") || process.argv[2] || "https://www.mrdfood.com/").trim();
  const outDir = (argValue("--out-dir", "") || "out").trim();
  const outFile = (argValue("--out-file", "") || "catalog.json").trim();
  const maxItems = Number.parseInt(argValue("--max-items", "80"), 10) || 80;
  const scrollPasses = Number.parseInt(argValue("--scroll-passes", "3"), 10) || 3;
  const scrollWaitMs = Number.parseInt(argValue("--scroll-wait-ms", "1200"), 10) || 1200;
  const maxW = Number.parseInt(argValue("--max-w", "2000"), 10) || 2000;
  const lat = Number.parseFloat(argValue("--lat", ""));
  const lng = Number.parseFloat(argValue("--lng", ""));
  const useGeo = Number.isFinite(lat) && Number.isFinite(lng);
  const screenshot = hasFlag("--screenshot");
  const headful = hasFlag("--headful");
  const debug = hasFlag("--debug");

  const category = deriveCategory(url);

  const executablePath = resolveChromeExecutable();
  if (!executablePath) {
    console.error("No Chrome/Chromium executable found.");
    console.error("Set PUPPETEER_EXECUTABLE_PATH and retry.");
    process.exit(2);
  }

  fs.mkdirSync(outDir, { recursive: true });
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), "bwiser-mrd-profile-"));

  const browser = await puppeteer.launch({
    headless: headful ? false : "new",
    executablePath,
    userDataDir,
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-gpu",
      "--disable-features=Crashpad",
      `--crash-dumps-dir=${path.join(outDir, "crash")}`,
      "--no-crash-upload",
      "--no-first-run",
      "--no-default-browser-check",
      "--disable-breakpad",
      "--disable-crash-reporter",
      "--disable-background-networking",
      "--disable-component-update",
    ],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1365, height: 768, deviceScaleFactor: 1 });
    await page.setUserAgent(
      "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36 BwiserScraper/0.1"
    );

    page.setDefaultTimeout(60_000);
    page.setDefaultNavigationTimeout(60_000);

    if (useGeo) {
      try {
        const origin = new URL(url).origin;
        await browser.defaultBrowserContext().overridePermissions(origin, ["geolocation"]);
        await page.setGeolocation({ latitude: lat, longitude: lng, accuracy: 100 });
      } catch {
        // Best-effort. Some environments won't allow overriding permissions.
      }
    }

    const capturedJson = [];
    page.on("response", async (res) => {
      try {
        const rt = String(res.request().resourceType() || "");
        if (rt !== "xhr" && rt !== "fetch") return;

        // Keep it bounded; we only need small listing payloads.
        const url = res.url();
        if (!/mrd/i.test(url)) return;

        const txt = await res.text();
        if (!txt || txt.length > 1_500_000) return;
        const trimmed = txt.trim();
        if (!trimmed.startsWith("{") && !trimmed.startsWith("[")) return;
        const parsed = JSON.parse(txt);
        capturedJson.push({ url, parsed });
      } catch {
        // ignore
      }
    });

    const startedAt = Date.now();
    await page.goto(url, { waitUntil: "networkidle2" });

    // If the site is gating results behind a location prompt, try to trigger "use current location".
    if (useGeo) {
      try {
        const clicked = await page.evaluate(() => {
          const rx = /(use|detect|enable).*(current|my).*(location)|current location|my location/i;
          const candidates = Array.from(document.querySelectorAll("button,a,[role='button'],div,span"))
            .filter((el) => {
              const t = (el.textContent || "").trim().replace(/\s+/g, " ");
              if (!t) return false;
              if (t.length > 80) return false;
              return rx.test(t);
            })
            .slice(0, 40);

          for (const el of candidates) {
            try {
              el.click();
              return true;
            } catch {
              // ignore
            }
          }
          return false;
        });

        if (clicked) {
          await new Promise((r) => setTimeout(r, 1500));
        }
      } catch {
        // ignore
      }
    }

    // Trigger lazy-loaded cards/images.
    for (let i = 0; i < scrollPasses; i++) {
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await new Promise((r) => setTimeout(r, scrollWaitMs));
    }

    // Collect direct restaurant links (these exist even when listings are locked behind location).
    // Example: /delivery/restaurants/pizza-hut/46
    const restaurantLinks = await page.$$eval('a[href^="/delivery/restaurants/"]', (as) => {
      const out = [];
      const seen = new Set();
      for (const a of as) {
        const href = a.getAttribute("href") || "";
        if (!href) continue;
        const parts = href.split("/").filter(Boolean);
        if (parts.length < 4) continue;
        if (parts[0] !== "delivery" || parts[1] !== "restaurants") continue;
        const slug = parts[2] || "";
        const id = parts[3] || "";
        if (!/^\d+$/.test(id)) continue;
        if (!slug || slug === "food") continue;
        if (seen.has(href)) continue;
        seen.add(href);
        out.push({
          href,
          text: (a.textContent || "").trim().replace(/\s+/g, " ").slice(0, 100),
        });
        if (out.length >= 140) break;
      }
      return out;
    });

    const extracted = await page.evaluate(() => {
      const baseUrl = location.href;

      const safeJsonParse = (s) => {
        try {
          return JSON.parse(s);
        } catch {
          return null;
        }
      };

      // 1) Try JSON-LD (SEO) first (often more stable than DOM classnames).
      const ldNodes = Array.from(document.querySelectorAll('script[type="application/ld+json"]'));
      const ld = [];
      for (const n of ldNodes) {
        const t = (n.textContent || "").trim();
        if (!t) continue;
        const parsed = safeJsonParse(t);
        if (!parsed) continue;
        if (Array.isArray(parsed)) ld.push(...parsed);
        else ld.push(parsed);
      }

      const items = [];

      const pushItem = (raw) => {
        if (!raw || typeof raw !== "object") return;
        const name = String(raw.name || "").trim();
        const url = String(raw.url || raw["@id"] || "").trim();
        const image = raw.image;
        const desc = String(raw.description || "").trim();

        const imgUrl =
          typeof image === "string"
            ? image
            : Array.isArray(image) && typeof image[0] === "string"
              ? image[0]
              : image && typeof image.url === "string"
                ? image.url
                : "";

        if (!name && !url) return;
        items.push({
          name,
          url,
          image_url: imgUrl,
          description: desc,
          source: "jsonld",
        });
      };

      for (const obj of ld) {
        const type = obj && obj["@type"];
        if (type === "ItemList" && Array.isArray(obj.itemListElement)) {
          for (const li of obj.itemListElement) {
            if (!li) continue;
            if (li.item) pushItem(li.item);
            else pushItem(li);
          }
        } else if (type === "Restaurant") {
          pushItem(obj);
        }
      }

      // 2) DOM fallback: restaurant cards with an <img> (avoid nav/category links).
      {
        const anchors = Array.from(document.querySelectorAll('a[href]')).slice(0, 600);
        for (const a of anchors) {
          const href = a.getAttribute("href") || "";
          if (!href) continue;
          // Prefer "restaurant detail" routes: /delivery/restaurants/<slug>/<id>
          const looksLikeRestaurant =
            /^\/delivery\/restaurants\/[^/]+\/\d+/.test(href) ||
            /mrdfood\.com\/delivery\/restaurants\/[^/]+\/\d+/.test(href);
          if (!looksLikeRestaurant) continue;

          const img = a.querySelector("img");
          const name =
            (img && (img.getAttribute("alt") || img.getAttribute("title"))) ||
            (a.textContent || "").trim().replace(/\s+/g, " ").slice(0, 80);

          const imgSrc = img
            ? (img.currentSrc ||
              img.getAttribute("src") ||
              img.getAttribute("data-src") ||
              img.getAttribute("data-lazy-src") ||
              img.getAttribute("data-splide-lazy") ||
              "")
            : "";
          if (!imgSrc) continue;

          items.push({
            name: String(name || "").trim(),
            url: href,
            image_url: imgSrc,
            description: "",
            source: "dom",
          });
        }
      }

      // 2b) DOM fallback: restaurant cards that use background-image rather than <img>.
      {
        const els = Array.from(document.querySelectorAll("*")).slice(0, 2200);
        for (const el of els) {
          const a = el.closest && el.closest("a[href]");
          if (!a) continue;
          const href = a.getAttribute("href") || "";
          if (!href) continue;
          const looksLikeRestaurant =
            /^\/delivery\/restaurants\/[^/]+\/\d+/.test(href) ||
            /mrdfood\.com\/delivery\/restaurants\/[^/]+\/\d+/.test(href);
          if (!looksLikeRestaurant) continue;

          const bg = getComputedStyle(el).backgroundImage || "";
          if (!bg || !bg.includes("url(")) continue;
          const m = bg.match(/url\\(\"?([^\"\\)]+)\"?\\)/);
          const imgSrc = m && m[1] ? m[1] : "";
          if (!imgSrc) continue;

          const name = (a.textContent || "").trim().replace(/\\s+/g, " ").slice(0, 80);
          items.push({
            name,
            url: href,
            image_url: imgSrc,
            description: "",
            source: "bg",
          });
        }
      }

      // 3) App-state fallback: Next.js-style payloads often contain the real card data.
      {
        const withImages = items.filter((i) => i && typeof i.image_url === "string" && i.image_url.trim() !== "")
          .length;
        if (withImages >= 8) {
          // good enough; avoid over-collecting
        } else {
        const nextScript = document.querySelector("#__NEXT_DATA__");
        const nextData = nextScript ? safeJsonParse(nextScript.textContent || "") : null;

        const found = [];
        const seen = new Set();

        const push = (o) => {
          if (!o || typeof o !== "object") return;
          const name = String(o.name || o.title || o.displayName || "").trim();
          if (!name) return;

          const rawImg = o.imageUrl || o.logoUrl || o.image || o.thumbnailUrl || o.photoUrl || "";
          const img =
            typeof rawImg === "string"
              ? rawImg
              : Array.isArray(rawImg) && typeof rawImg[0] === "string"
                ? rawImg[0]
                : rawImg && typeof rawImg.url === "string"
                  ? rawImg.url
                  : "";

          const url =
            String(o.url || o.href || o.link || "").trim() ||
            (o.slug && o.id ? `/delivery/restaurants/${o.slug}/${o.id}` : "") ||
            "";

          if (!img && !url) return;

          const key = `${url}::${name}`.toLowerCase().slice(0, 220);
          if (seen.has(key)) return;
          seen.add(key);

          found.push({
            name,
            url,
            image_url: img,
            description: String(o.description || o.subtitle || "").trim(),
            source: "next_data",
          });
        };

        const walk = (v, depth) => {
          if (depth > 7) return;
          if (!v) return;
          if (Array.isArray(v)) {
            for (const it of v) walk(it, depth + 1);
            return;
          }
          if (typeof v !== "object") return;

          push(v);
          for (const k of Object.keys(v)) {
            walk(v[k], depth + 1);
          }
        };

          if (nextData) walk(nextData, 0);
          items.push(...found.slice(0, 160));
        }
      }

      // Deduplicate by url+name
      const seen = new Set();
      const uniq = [];
      for (const it of items) {
        const key = `${it.url}::${it.name}`.toLowerCase().slice(0, 220);
        if (seen.has(key)) continue;
        seen.add(key);
        uniq.push(it);
      }

      const debugInfo = (() => {
        const out = {};
        out.has_next_data = Boolean(document.querySelector("#__NEXT_DATA__"));
        out.script_ids = Array.from(document.querySelectorAll("script[id]"))
          .slice(0, 40)
          .map((s) => s.id);
        out.sample_restaurant_links = Array.from(document.querySelectorAll('a[href^="/delivery/restaurants/"]'))
          .slice(0, 24)
          .map((a) => ({
            href: a.getAttribute("href") || "",
            text: (a.textContent || "").trim().replace(/\\s+/g, " ").slice(0, 80),
            has_img: Boolean(a.querySelector("img")),
          }));
        return out;
      })();

      return {
        baseUrl,
        title: document.title,
        items: uniq.slice(0, 150),
        debug: debugInfo,
      };
    });

    if (screenshot) {
      const shotPath = path.join(outDir, "mrd_catalog.png");
      await page.screenshot({ path: shotPath, fullPage: true });
    }

    if (debug) {
      const debugPath = path.join(outDir, "mrd_catalog_debug.json");
      fs.writeFileSync(debugPath, JSON.stringify(extracted, null, 2));

      const netDebugPath = path.join(outDir, "mrd_catalog_network.json");
      fs.writeFileSync(
        netDebugPath,
        JSON.stringify(
          {
            captured_count: capturedJson.length,
            sample_urls: capturedJson.slice(0, 60).map((e) => e.url),
            restaurant_links_count: restaurantLinks.length,
            restaurant_links_sample: restaurantLinks.slice(0, 30),
          },
          null,
          2
        )
      );

      console.log(`Debug: ${debugPath}`);
      console.log(`Debug: ${netDebugPath}`);
    }

    const baseUrl = extracted.baseUrl || url;
    let items = Array.isArray(extracted.items) ? extracted.items.slice() : [];

    // Network JSON fallback: pull entities from captured XHR payloads.
    if (items.length < 6 && capturedJson.length) {
      const fromNetwork = [];
      for (const entry of capturedJson.slice(0, 80)) {
        const extractedItems = extractItemsFromJsonPayload(entry.parsed);
        for (const it of extractedItems) {
          fromNetwork.push({ ...it, _payload_url: entry.url });
          if (fromNetwork.length >= 240) break;
        }
        if (fromNetwork.length >= 240) break;
      }
      items = items.concat(fromNetwork);
    }

    // Deduplicate by url+name once more (after network merge).
    {
      const seen = new Set();
      const uniq = [];
      for (const it of items) {
        const key = `${it.url}::${it.name}`.toLowerCase().slice(0, 220);
        if (seen.has(key)) continue;
        seen.add(key);
        uniq.push(it);
      }
      items = uniq;
    }

    // If we still have no usable items, enrich from direct restaurant pages.
    // This avoids the "select location" gate on listing pages.
    if (items.length < 6 && restaurantLinks.length) {
      const startFrom = page.url();
      const enrichPage = await browser.newPage();
      try {
        enrichPage.setDefaultTimeout(60_000);
        enrichPage.setDefaultNavigationTimeout(60_000);
        await enrichPage.setViewport({ width: 1365, height: 768, deviceScaleFactor: 1 });
        await enrichPage.setUserAgent(
          "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36 BwiserScraper/0.1"
        );

        for (const l of restaurantLinks) {
          if (items.length >= maxItems) break;
          const abs = absolutize(l.href, startFrom);
          if (!abs) continue;

          try {
            await enrichPage.goto(abs, { waitUntil: "networkidle2" });
            const d = await enrichPage.evaluate(() => {
              const pickMeta = (sel) => {
                const el = document.querySelector(sel);
                return el ? (el.getAttribute("content") || "").trim() : "";
              };

              const ogTitle = pickMeta('meta[property="og:title"]');
              const ogDesc = pickMeta('meta[property="og:description"]');
              const ogImg = pickMeta('meta[property="og:image"]');

              const parseSrcset = (srcset) => {
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
              };

              const widthFromUrl = (u) => {
                try {
                  const p = new URL(u, location.href).pathname || "";
                  const m = p.match(/\/(\d+)x0\//);
                  return m ? Number.parseInt(m[1], 10) : 0;
                } catch {
                  return 0;
                }
              };

              const isSeoImage = (u) => /\/seo\//i.test(String(u || "")) || /seo\//i.test(String(u || ""));

              const pickBestImage = () => {
                const imgs = Array.from(document.querySelectorAll("img")).slice(0, 180);
                let bestHero = "";
                let bestHeroScore = 0;
                let bestLogo = "";
                let bestLogoScore = 0;
                for (const img of imgs) {
                  const base = img.currentSrc || img.getAttribute("src") || "";
                  const srcset = img.getAttribute("srcset") || "";
                  const candidates = [];
                  if (base) candidates.push(base);
                  for (const e of parseSrcset(srcset)) candidates.push(e.url);

                  for (const c of candidates) {
                    if (!c) continue;
                    const abs = c.startsWith("//") ? `https:${c}` : new URL(c, location.href).toString();
                    if (!/img\.mrdfood\.com/i.test(abs)) continue;
                    if (isSeoImage(abs)) continue;
                    const w = widthFromUrl(abs);
                    const r = img.getBoundingClientRect();
                    const area = Math.max(0, Math.round(r.width || 0)) * Math.max(0, Math.round(r.height || 0));
                    const heroScore = Math.max(area, w * 10);

                    if (heroScore > bestHeroScore) {
                      bestHeroScore = heroScore;
                      bestHero = abs;
                    }

                    // Prefer a logo-like image:
                    // - usually smaller and more square than the hero banners
                    // - often PNG
                    const aspect =
                      r.height && r.width ? Math.max(r.width, r.height) / Math.max(1, Math.min(r.width, r.height)) : 999;
                    const isSquareish = aspect <= 1.35;
                    const isPng = /\.png($|\?)/i.test(abs);
                    const isLogoSize = w >= 80 && w <= 900;
                    if (isSquareish && isLogoSize) {
                      const logoScore = (area || 0) + (isPng ? 30_000 : 0) + (w * 20);
                      if (logoScore > bestLogoScore) {
                        bestLogoScore = logoScore;
                        bestLogo = abs;
                      }
                    }
                  }
                }

                if (bestLogo) return bestLogo;
                if (bestHero) return bestHero;

                // Fallback: accept og:image even if it's SEO, but only if nothing else exists.
                if (ogImg) {
                  try {
                    return ogImg.startsWith("//") ? `https:${ogImg}` : new URL(ogImg, location.href).toString();
                  } catch {
                    return ogImg;
                  }
                }
                return "";
              };

              // JSON-LD often contains a Restaurant with image + name.
              const ldNodes = Array.from(document.querySelectorAll('script[type="application/ld+json"]'));
              let ldName = "";
              let ldImg = "";
              for (const n of ldNodes) {
                try {
                  const parsed = JSON.parse((n.textContent || "").trim());
                  const arr = Array.isArray(parsed) ? parsed : [parsed];
                  for (const obj of arr) {
                    if (!obj || typeof obj !== "object") continue;
                    if (obj["@type"] !== "Restaurant") continue;
                    if (!ldName && typeof obj.name === "string") ldName = obj.name;
                    const image = obj.image;
                    if (!ldImg) {
                      if (typeof image === "string") ldImg = image;
                      else if (Array.isArray(image) && typeof image[0] === "string") ldImg = image[0];
                      else if (image && typeof image.url === "string") ldImg = image.url;
                    }
                  }
                } catch {
                  // ignore
                }
              }

              const h1 = (document.querySelector("h1")?.textContent || "").trim();

              return {
                name: (ldName || ogTitle || h1 || document.title || "").trim(),
                description: (ogDesc || "").trim(),
                image_url: (ldImg || pickBestImage() || ogImg || "").trim(),
                page_url: location.href,
              };
            });

            const name = String(d.name || "").trim();
            const img = String(d.image_url || "").trim();
            if (!name || !img) continue;

            items.push({
              name,
              url: String(d.page_url || abs),
              image_url: img,
              description: String(d.description || "").trim(),
              source: "restaurant_page",
            });

            // Be gentle.
            await new Promise((r) => setTimeout(r, 400));
          } catch {
            // ignore individual failures
          }
        }
      } finally {
        await enrichPage.close();
      }
    }

    const products = [];

    const seenIds = new Set();
    for (const it of items || []) {
      const absUrl = absolutize(it.url || "", baseUrl);
      let absImg = absolutize(it.image_url || "", baseUrl);
      const bumped = bumpMrdCdnWidth(absImg, maxW);
      if (bumped) absImg = bumped;

      if (!absUrl && !absImg) continue;

      // Derive stable ID from URL when possible.
      let id = "";
      try {
        const u = new URL(absUrl || "", baseUrl);
        const parts = (u.pathname || "").split("/").filter(Boolean);
        const maybeNum = parts.length ? parts[parts.length - 1] : "";
        const maybeSlug = parts.length > 1 ? parts[parts.length - 2] : "";
        if (/^\d+$/.test(maybeNum) && maybeSlug) id = `${slugify(maybeSlug)}-${maybeNum}`;
        else if (parts.length) id = slugify(parts.slice(-2).join("-"));
      } catch {
        // ignore
      }
      if (!id) id = slugify(it.name || "item");
      if (!id) continue;
      if (seenIds.has(id)) continue;
      seenIds.add(id);

      products.push({
        id,
        category_id: category.id,
        name: String(it.name || "").trim() || "Item",
        unit: "",
        price: null,
        rating: null,
        badge: "COMING SOON",
        image: "",
        image_url: absImg,
        description: String(it.description || "").trim(),
        url: absUrl,
        source: String(it.source || ""),
      });

      if (products.length >= maxItems) break;
    }

    const out = {
      source: "mrd_scrape",
      fetched_at: new Date().toISOString(),
      currency: "ZAR",
      categories: [{ id: category.id, name: category.name }],
      products,
      meta: {
        page_url: url,
        page_title: extracted.title || "",
        item_count: products.length,
        duration_ms: Date.now() - startedAt,
        geo: useGeo
          ? {
              used: true,
              lat,
              lng,
            }
          : {
              used: false,
            },
        note:
          "Local-only prototype scrape. Ensure compliance with the target site's terms before using in production.",
      },
    };

    const outPath = path.join(outDir, outFile);
    fs.writeFileSync(outPath, JSON.stringify(out, null, 2));
    console.log(`Saved: ${outPath}`);
  } finally {
    try {
      fs.rmSync(userDataDir, { recursive: true, force: true });
    } catch {
      // ignore
    }
    await browser.close();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
