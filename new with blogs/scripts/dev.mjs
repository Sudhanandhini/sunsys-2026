// `npm run dev`: starts the PHP server (API + admin) and Astro together.
import { spawn } from 'node:child_process';

const run = (cmd, args) => spawn(cmd, args, { stdio: 'inherit', shell: process.platform === 'win32' });
const php = run('node', ['scripts/dev-php.mjs']);
const astro = run('npx', ['astro', 'dev']);
const stop = () => { php.kill(); astro.kill(); process.exit(); };
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
astro.on('exit', stop);
