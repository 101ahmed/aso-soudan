$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $root 'frontend'))) {
  $root = (Get-Location).Path
}

$distDir = Join-Path $root 'frontend\dist'
$publicDir = Join-Path $root 'backend\public'
$preserve = @('.htaccess', 'index.php', 'robots.txt', 'favicon.ico')

if (-not (Test-Path $distDir)) {
  Write-Error "Missing frontend/dist. Run: cd frontend; npm run build"
}

$assetsDir = Join-Path $publicDir 'assets'
if (Test-Path $assetsDir) {
  Remove-Item $assetsDir -Recurse -Force
}

Get-ChildItem $distDir | ForEach-Object {
  if ($preserve -contains $_.Name) {
    Write-Warning "Skip preserved name from dist: $($_.Name)"
    return
  }

  $dest = Join-Path $publicDir $_.Name
  if (Test-Path $dest) {
    Remove-Item $dest -Recurse -Force
  }

  Copy-Item $_.FullName $dest -Recurse -Force
  Write-Host "Copied $($_.Name)"
}

if (-not (Test-Path (Join-Path $publicDir 'index.html'))) {
  Write-Error 'Copy failed: backend/public/index.html missing'
}

Write-Host 'SPA build copied to backend/public (index.php / .htaccess preserved).'
