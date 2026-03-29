# Mr D Scraper (Local Only)

This folder contains a small Puppeteer-based scraper used for **local prototyping**.

Notes:
- Scraping third-party sites can violate their Terms of Service. Only use this with permission and respect robots/rate limits.
- This uses `puppeteer-core` to avoid downloading Chromium automatically.

## Install

```bash
cd tools/scrapers/mrd
npm install
```

## Run

```bash
PUPPETEER_EXECUTABLE_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
node scrape.mjs --url "https://www.mrdfood.com/" --screenshot
```

Output is written to `tools/scrapers/mrd/out/`.

