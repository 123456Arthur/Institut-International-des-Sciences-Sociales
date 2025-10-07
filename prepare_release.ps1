# Prepare a production-ready 'dist' folder for publishing (Windows PowerShell)
# - Generates watermarked images using scripts\watermark_images.py
# - Copies index.html into dist and replaces image references with watermarked filenames
# - Copies protect bundle and scripts README
# Usage:
#   pwsh .\scripts\prepare_release.ps1

$ErrorActionPreference = 'Stop'

$root = Resolve-Path "$(Split-Path -Parent $MyInvocation.MyCommand.Path)\.."
$root = $root.ProviderPath
$dist = Join-Path $root 'dist'
$imagesOut = Join-Path $dist 'images'

Write-Host "Root: $root"
if(Test-Path $dist){
    Write-Host "Removing existing dist..."
    Remove-Item -Recurse -Force $dist
}
New-Item -ItemType Directory -Path $dist | Out-Null
New-Item -ItemType Directory -Path $imagesOut | Out-Null

# 1) Run watermark script (requires Python + Pillow)
$py = "python"
$wmScript = Join-Path $root 'scripts\watermark_images.py'
if(-not (Test-Path $wmScript)){
    Write-Error "watermark_images.py not found at $wmScript"
    exit 1
}
Write-Host "Running watermark script (this may take some time)..."
& $py $wmScript -i $root -o $imagesOut --opacity 0.16 --fontsize 42

# 2) Copy index.html and replace references to images with watermarked versions
$indexSrc = Join-Path $root 'index.html'
$indexDst = Join-Path $dist 'index.html'
Copy-Item $indexSrc $indexDst -Force

# Get list of watermarked files
$wmFiles = Get-ChildItem -Path $imagesOut -File | Where-Object { $_.Extension -match '\.(png|jpg|jpeg)$' }

if($wmFiles.Count -eq 0){
    Write-Warning "No watermarked images were produced in $imagesOut"
} else {
    $content = Get-Content $indexDst -Raw
    foreach($f in $wmFiles){
        # original name assumed to be name without _wm suffix
        $origName = $f.BaseName -replace '_wm$',''
        $origFile = "$origName$($f.Extension)"
        $escaped = [regex]::Escape($origFile)
        $newRef = "images/" + $f.Name
        $content = [regex]::Replace($content, $escaped, [Regex]::Escape($newRef))
    }
    Set-Content -Path $indexDst -Value $content -Encoding UTF8
    Write-Host "index.html updated with watermarked references"
}

# 3) Copy protect bundle and scripts readme
$protectSrc = Join-Path $root 'protect'
if(Test-Path $protectSrc){
    Copy-Item $protectSrc $dist -Recurse -Force
    Write-Host "protect/ copied to dist/protect"
} else {
    Write-Warning "protect/ folder not found; skipping"
}

$scriptsReadme = Join-Path $root 'scripts\README.md'
if(Test-Path $scriptsReadme){ Copy-Item $scriptsReadme $dist -Force }

Write-Host "Dist preparation complete. Review dist/ and push that folder to GitHub (public) or push to a gh-pages branch.\n"
Write-Host "Important: keep original assets private. Prefer pushing only dist/ to public repos." 
