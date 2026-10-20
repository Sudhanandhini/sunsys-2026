// Runs the PHP API/admin locally from public/ on http://127.0.0.1:8099 (used by `npm run dev`).
// Database settings come from config.local.php in the project root (never inside public/).
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

const config = resolve('config.local.php');
if (!existsSync(config)) {
  console.error('[php] Missing config.local.php in the project root. Copy public/api/_lib/config.sample.php to config.local.php and fill in your local MySQL details.');
  process.exit(1);
}
const php = process.env.PHP_BIN || 'php';
const child = spawn(php, ['-S', '127.0.0.1:8099', '-t', 'public', 'scripts/php-router.php'], {
  stdio: 'inherit',
  env: { ...process.env, SUNSYS_CONFIG: config },
});
child.on('error', () => console.error('[php] Could not start PHP. Install PHP (e.g. XAMPP) or set PHP_BIN to php.exe.'));
child.on('exit', (code) => process.exit(code ?? 0));
