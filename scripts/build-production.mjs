import { spawnSync } from 'node:child_process'
import { copyFileSync, existsSync } from 'node:fs'
import { join } from 'node:path'
import { platform } from 'node:os'

const cwd = process.cwd()
const root =
  existsSync(join(cwd, 'frontend')) && existsSync(join(cwd, 'backend'))
    ? cwd
    : join(cwd, '..')

const frontendDir = join(root, 'frontend')
const envProdExample = join(frontendDir, '.env.production.example')
const envProd = join(frontendDir, '.env.production')

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: options.cwd ?? root,
    stdio: 'inherit',
    shell: true,
  })
  if (result.status !== 0) {
    process.exit(result.status ?? 1)
  }
}

if (!existsSync(frontendDir) || !existsSync(join(root, 'backend'))) {
  console.error('Run from the monorepo root (or frontend/).')
  process.exit(1)
}

if (!existsSync(envProd) && existsSync(envProdExample)) {
  copyFileSync(envProdExample, envProd)
  console.log('Created frontend/.env.production from .env.production.example')
}

console.log('Building frontend…')
run('npm', ['run', 'build'], { cwd: frontendDir })

console.log('Copying dist → backend/public…')
if (platform() === 'win32') {
  run('powershell', [
    '-NoProfile',
    '-ExecutionPolicy',
    'Bypass',
    '-File',
    join(root, 'scripts', 'copy-dist-to-public.ps1'),
  ])
} else {
  run('node', [join(root, 'scripts', 'copy-dist-to-public.mjs')])
}
