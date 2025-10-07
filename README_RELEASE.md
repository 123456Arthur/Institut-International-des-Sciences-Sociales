Release guide — what to push to GitHub

Goal: publish a site on GitHub Pages without exposing original high-resolution source assets and development files.

Recommended workflow
1. Run the prepare script to create a `dist/` folder (contains watermarked images and production-ready index):
   pwsh .\scripts\prepare_release.ps1

2. Inspect `dist/` locally to confirm visuals.

3. Create a new git repo (or use an existing one) and add only the `dist/` contents as the root for GitHub Pages (or push `dist/` to `gh-pages` branch):
   cd dist
   git init
   git add .
   git commit -m "Release site"
   git branch -M gh-pages
   git remote add origin <your-git-repo-url>
   git push -u origin gh-pages --force

What NOT to push
- Do NOT push the original `audio/`, `protect/` source (optional), `scripts/` development files or original high-resolution images unless you want them public.
- Do NOT push any `.env`, API keys or server-side files.

.gitignore suggestion (root of source repo)
# development files
/scripts/
/protect/
/.venv
/*.pyc

Notes
- If you need the project in GitHub for collaboration but want to keep originals private, create a repo with only `dist/` (or a separate private repo for source). 
- For stronger DRM-like protection, host assets behind authentication and issue signed URLs rather than static public hosting.
