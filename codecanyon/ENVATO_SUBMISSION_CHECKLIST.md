# Envato Submission Checklist (CodeCanyon)

## Before Upload
- [ ] Replace placeholder preview PNGs in `Preview-Images/` with real product screenshots.
- [ ] Confirm no secrets in `Main-Files` (`.env` is excluded by default).
- [ ] Verify installation steps in `Documentation/index.html`.
- [ ] Test clean install on fresh server.
- [ ] Confirm third-party license references are complete.

## Build Package
```bash
./codecanyon/build_codecanyon_package.sh 1.0.0
```

Generated file:
- `codecanyon/dist/Bwiser-CodeCanyon-Package-v1.0.0.zip`

## Upload Content
- Upload the ZIP from `codecanyon/dist/`.
- Use `Preview-Images/main-preview-590x300.png` for main preview image.
- Use `Preview-Images/thumbnail-80x80.png` for thumbnail.
- Use the screenshot PNGs as gallery images.

## Notes
This package contains source only. Buyers must run Composer/npm and configure their own environment keys.
