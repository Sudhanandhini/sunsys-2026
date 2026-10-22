// Internal links are written root-relative ("/contact"). The site can also be built for a subfolder
// (`npm run build:staging` → https://sunsysweb.co.in/sunsys-2026/), so every one goes through url(),
// which adds Astro's `base`. Assets (CSS, JS, fonts, astro:assets images) get the base from Astro itself.
const BASE = import.meta.env.BASE_URL.replace(/\/$/, '');

export const url = (path: string) => (path.startsWith('/') && !path.startsWith('//') ? BASE + path : path);
