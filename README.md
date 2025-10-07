Watermark images script

Usage (PowerShell / Windows):

# create a virtualenv (optional)
python -m venv .venv; .\.venv\Scripts\Activate.ps1; python -m pip install -r requirements.txt

# watermark a single file
python watermark_images.py -i "..\L1 réel.jpg" -o "..\watermarked" -t "SESSION12345" --opacity 0.18 --fontsize 48

# watermark all images in folder
python watermark_images.py -i "." -o "..\watermarked" --opacity 0.16

Notes:
- The script tries to embed a token inside PNG metadata or JPEG comment when possible.
- For best forensic value, use a unique token per user/session and log it server-side when the user is authenticated.
- Test outputs for visual quality and adjust font size/opacities as needed.
