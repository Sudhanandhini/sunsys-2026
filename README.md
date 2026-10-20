# Sunsys Technologies website

Astro 5 + React 19 islands + Tailwind CSS 4. Static output; JS ships only for the header, counters and forms.

## Run
npm install
npm run dev        # http://localhost:4321
npm run build      # static site in dist/

## Edit content
All text, services, industries, stats, clients, jobs and contact details: `src/data/site.ts`.

## Admin dashboard (PHP + MySQL)
Blog posts, job openings, job applications (with CV uploads) and contact/quote/newsletter enquiries
are managed at `/admin`. The code lives in `public/api` and `public/admin` and is copied into `dist/` on build.

First-time setup on cPanel:
1. cPanel > MySQL Databases: create a database and a user with all privileges on it.
2. Upload `dist/` to `public_html/` as usual.
3. On the server, copy `public_html/api/_lib/config.sample.php` to `config.php` and enter the database details.
   Optional: set `private_dir` to a folder outside public_html for CVs, and `notify_email` for email alerts.
4. Visit `https://yourdomain/api/install.php`, create the first admin. It locks itself once an admin exists;
   delete it from the server afterwards.
5. Log in at `https://yourdomain/admin/`.

Redeploying: upload the new `dist/` over the old files. Do NOT delete public_html first: `api/_lib/config.php`,
`api/_private/` (CVs) and `uploads/blog/` (post images) exist only on the server.
Requires PHP 8.0+ with pdo_mysql and fileinfo, and Apache with .htaccess (standard on cPanel).
`npm run dev` does not run PHP, so the blog and live job list only load on the server (the Career page falls back
to the jobs in `src/data/site.ts`). To test locally with XAMPP, see the PHP built-in server note below:
`php -S 127.0.0.1:8099 -t dist` (protected folders are only blocked by .htaccess on Apache).

## Forms
Contact, quote and newsletter forms POST JSON to `/api/contact.php` (override with `PUBLIC_FORM_ENDPOINT`).
Job applications POST multipart to `/api/apply.php`.

## Theme
Brand #0266cb | Headings Montserrat 700 | Body Lato 400 | Card titles Raleway 600 (self-hosted via @fontsource).
