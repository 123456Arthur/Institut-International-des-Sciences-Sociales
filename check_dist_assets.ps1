$htmlPath = 'd:\ISS page web idée\ISS 3\dist\index.html'

if(-not (Test-Path $htmlPath)){
  Write-Error "File not found: $htmlPath"
  exit 1
}

$html = Get-Content -Raw -Path $htmlPath
$pattern = '(?:src|href)\s*=\s*"([^"]+)"'
$matches = [System.Text.RegularExpressions.Regex]::Matches($html, $pattern)
$assets = @()

foreach($m in $matches){
  $p = $m.Groups[1].Value
  if($p -and $p -notmatch '^https?:' -and $p -notmatch '^data:'){
    $assets += $p
  }
}

$assets = $assets | Select-Object -Unique
Write-Host 'Referenced local assets in dist/index.html:'

foreach($a in $assets){
  $path = Join-Path 'd:\ISS page web idée\ISS 3\dist' $a
  $exists = Test-Path $path
  if($exists){ $status = 'OK' } else { $status = 'MISSING' }
  Write-Host ("[{0}] {1}" -f $status, $a)
}
