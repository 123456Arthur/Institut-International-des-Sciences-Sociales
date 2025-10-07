#!/usr/bin/env python3
"""
Batch watermark images (text overlay + metadata tag)
Usage:
  python watermark_images.py --input <file-or-dir> --output <dir> [--text TEXT] [--opacity 0.18] [--fontsize 48]

Creates watermarked copies with suffix _wm and embeds a forensic token in metadata.
"""
from PIL import Image, ImageDraw, ImageFont, PngImagePlugin
from pathlib import Path
import argparse
import uuid
import os


def generate_token():
    return uuid.uuid4().hex[:12]


def load_font(size):
    # Try common fonts then fallback
    candidates = [
        "arial.ttf",
        "DejaVuSans.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/Library/Fonts/Arial.ttf"
    ]
    for p in candidates:
        try:
            return ImageFont.truetype(p, size)
        except Exception:
            continue
    return ImageFont.load_default()


def watermark_one(in_path: Path, out_path: Path, text: str, opacity: float, fontsize: int):
    im = Image.open(in_path).convert('RGBA')
    w,h = im.size

    # Create overlay
    overlay = Image.new('RGBA', im.size, (255,255,255,0))
    draw = ImageDraw.Draw(overlay)

    font = load_font(fontsize)
    # compute diagonal repeated watermark
    txt = text
    # size of text
    tw, th = draw.textsize(txt, font=font)
    # rotate and draw across image
    import math
    angle = -30
    # create a text image to rotate
    txt_im = Image.new('RGBA', (tw+20, th+10), (255,255,255,0))
    txt_draw = ImageDraw.Draw(txt_im)
    txt_draw.text((10,5), txt, font=font, fill=(255,255,255,int(255*opacity)))
    # compute spacing
    spacing_x = int(tw*2.5)
    spacing_y = int(th*3.0)

    # tile the rotated text
    for y in range(-spacing_y, h+spacing_y, spacing_y):
        for x in range(-spacing_x, w+spacing_x, spacing_x):
            overlay.paste(txt_im.rotate(angle, expand=1), (x,y), txt_im.rotate(angle, expand=1))

    combined = Image.alpha_composite(im, overlay)
    # convert back to RGB for saving (preserve ability to save JPEG)
    out_im = combined.convert('RGB')

    out_path.parent.mkdir(parents=True, exist_ok=True)

    suffix = in_path.suffix.lower()
    token = text
    if suffix in ['.png']:
        meta = PngImagePlugin.PngInfo()
        meta.add_text('ISS_WM', token)
        out_im.save(out_path, pnginfo=meta)
    elif suffix in ['.jpg', '.jpeg']:
        try:
            out_im.save(out_path, quality=95, subsampling=0, comment=token.encode('utf-8'))
        except Exception:
            out_im.save(out_path, quality=95)
    else:
        out_im.save(out_path)

    print(f"watermarked: {in_path} -> {out_path} (token={token})")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', '-i', required=True, help='Input file or directory')
    parser.add_argument('--output', '-o', required=False, default='scripts/watermarked', help='Output directory')
    parser.add_argument('--text', '-t', required=False, help='Watermark text or token (default: generate)')
    parser.add_argument('--opacity', type=float, default=0.16, help='Opacity for overlay text (0-1)')
    parser.add_argument('--fontsize', type=int, default=42, help='Base font size')
    args = parser.parse_args()

    inp = Path(args.input)
    outdir = Path(args.output)
    token = args.text or generate_token()

    if inp.is_file():
        files = [inp]
    elif inp.is_dir():
        files = [p for p in inp.iterdir() if p.suffix.lower() in ('.png','.jpg','.jpeg')]
    else:
        print('Input path not found')
        return

    for f in files:
        out_name = f.stem + '_wm' + f.suffix
        out_path = outdir / out_name
        watermark_one(f, out_path, token, args.opacity, args.fontsize)

if __name__ == '__main__':
    main()
