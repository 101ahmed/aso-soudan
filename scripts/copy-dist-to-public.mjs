/**
 * Cross-platform copy of frontend/dist → backend/public.
 * On some Windows setups with non-ASCII paths, prefer:
 *   powershell -File scripts/copy-dist-to-public.ps1
 */
import { cpSync, existsSync, mkdirSync, rmSync, readdirSync, statSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'

function resolveRoot() {
  try {
    const scriptDir = dirname(fileURLToPath(import.meta.url))
    const fromScript = join(scriptDir, '..')
    if (existsSync(join(fromScript, 'frontend')) && existsSync(join(fromScript, 'backend'))) {
      return fromScript
    }
  } catch {
    // ignore import.meta.url issues on some Windows locales
  }

  const cwd = process.cwd()
  if (existsSync(join(cwd, 'frontend')) && existsSync(join(cwd, 'backend'))) {
    return cwd
  }
  if (existsSync(join(cwd, 'dist')) && existsSync(join(cwd, '..', 'backend'))) {
    return join(cwd, '..')
  }
  return cwd
}

const root = resolveRoot()
const distDir = join(root, 'frontend', 'dist')
const publicDir = join(root, 'backend', 'public')
const preserve = new Set(['.htaccess', 'index.php', 'robots.txt', 'favicon.ico'])

console.log('Root:', root)

if (!existsSync(distDir)) {
  console.error('Missing frontend/dist. Run: cd frontend && npm run build')
  process.exit(1)
}

if (!existsSync(publicDir)) {
  mkdirSync(publicDir, { recursive: true })
}

const assetsDir = join(publicDir, 'assets')
if (existsSync(assetsDir)) {
  rmSync(assetsDir, { recursive: true, force: true })
}

for (const entry of readdirSync(distDir)) {
  if (preserve.has(entry)) {
    console.warn(`Skip preserved name from dist: ${entry}`)
    continue
  }

  const from = join(distDir, entry)
  const to = join(publicDir, entry)

  if (existsSync(to) && statSync(to).isDirectory()) {
    rmSync(to, { recursive: true, force: true })
  } else if (existsSync(to)) {
    rmSync(to, { force: true })
  }

  cpSync(from, to, { recursive: true })
  console.log(`Copied ${entry}`)
}

if (!existsSync(join(publicDir, 'index.html'))) {
  console.error('Copy failed: backend/public/index.html missing')
  process.exit(1)
}

console.log('SPA build copied to backend/public (index.php / .htaccess preserved).')
