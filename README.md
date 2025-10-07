Protect bundle
================

What this bundle does
- Lightweight client-side protection bundle (minified js) intended to add deterrents only.
- Adds a per-session invisible watermark token stored in a CSS variable and on <html data-wm> so you can log it server-side if desired.
- Disables right-click, text selection and copy in a "smart" way (doesn't block inputs or known editable elements).
- Detects devtools-ish viewport changes periodically and applies a short, reversible visual perturbation (tiny contrast/hue change) to discourage inspection.
- Loads two small internal modules dynamically as blobs so full code is not present in a single inspectable script resource.

How to integrate
1. Copy `protect.bundle.js` into your site (for example into `protect/`).
2. Include it in the page by adding just before </body>:

<script src="protect/protect.bundle.js" async></script>

Important limitations & security notes
- This is only a client-side deterrent. It does not prevent a motivated attacker from copying assets or code.
- Determined users can bypass these protections (disable JS, inspect network, or use an automated browser). Treat this as "obfuscation + deterrent", not "true protection".
- The watermark token is visible to client-side code and can be exfiltrated; for forensic usefulness combine it with server-side logging (log token when a user performs authenticated actions, or embed token server-side into delivered content).

Privacy & accessibility
- The script avoids blocking inputs and contenteditable regions. Users can still interact normally with forms and editors.
- The devtools detection only triggers a very short visual perturbation; the behavior is reversible and does not alter DOM content.

Server-side recommendations
- Generate and serve pre-watermarked images for distribution.
- Use signed URLs or access control for private assets.

If you want, I can:
- Improve the watermarking to tie it to authentication/session cookies.
- Provide a server-side Python script to batch stamp watermarks into images before publishing.
- Replace the simple devtools detection with a server-side fingerprinting + logging approach.

Limitations from your request
- Full HTML/CSS/JS obfuscation that makes the site impossible to inspect can't be guaranteed: browsers need readable DOM/CSS/JS to render the page. This bundle focuses on deterrence and fragmentation only.

License & notes
- This code is provided as-is. Test thoroughly across browsers and devices before deploying to production.
