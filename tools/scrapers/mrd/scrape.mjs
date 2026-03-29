import fs from "node:fs";
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
    // macOS
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    "/Applications/Chromium.app/Contents/MacOS/Chromium",
    // Linux
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

async function main() {
  const url = (argValue("--url", "") || process.argv[2] || "https://www.mrdfood.com/").trim();
  const outDir = (argValue("--out-dir", "") || "out").trim();
  const screenshot = hasFlag("--screenshot");
  const headful = hasFlag("--headful");

  const executablePath = resolveChromeExecutable();
  if (!executablePath) {
    console.error("No Chrome/Chromium executable found.");
    console.error("Set PUPPETEER_EXECUTABLE_PATH and retry.");
    process.exit(2);
  }

  fs.mkdirSync(outDir, { recursive: true });

  const browser = await puppeteer.launch({
    headless: headful ? false : "new",
    executablePath,
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-dev-shm-usage",
      "--disable-gpu",
    ],
  });

  try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 720, deviceScaleFactor: 1 });
    await page.setUserAgent(
      "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36 BwiserScraper/0.1"
    );

    // Be gentle; avoid hammering a third-party site.
    page.setDefaultTimeout(60_000);
    page.setDefaultNavigationTimeout(60_000);

    const startedAt = Date.now();
    await page.goto(url, { waitUntil: "networkidle2" });

    const title = await page.title();
    const links = await page.$$eval("a[href]", (as) => {
      const out = [];
      for (const a of as) {
        const href = a.getAttribute("href") || "";
        const text = (a.textContent || "").trim().replace(/\s+/g, " ");
        if (!href) continue;
        out.push({ href, text });
      }
      return out;
    });

    const uniqueLinks = [];
    const seen = new Set();
    for (const l of links) {
      const key = `${l.href}::${l.text}`.slice(0, 300);
      if (seen.has(key)) continue;
      seen.add(key);
      uniqueLinks.push(l);
      if (uniqueLinks.length >= 80) break;
    }

    if (screenshot) {
      const shotPath = path.join(outDir, "mrd.png");
      await page.screenshot({ path: shotPath, fullPage: true });
    }

    const snapshot = {
      url,
      title,
      fetched_at: new Date().toISOString(),
      duration_ms: Date.now() - startedAt,
      link_count: uniqueLinks.length,
      links: uniqueLinks,
      note:
        "This is a lightweight snapshot for local integration. Scraping must comply with the target site's terms and robots policy.",
    };

    const jsonPath = path.join(outDir, "mrd_snapshot.json");
    fs.writeFileSync(jsonPath, JSON.stringify(snapshot, null, 2));

    console.log(`Saved: ${jsonPath}${screenshot ? " (+ screenshot)" : ""}`);
  } finally {
    await browser.close();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});

