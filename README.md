# Sunsys Technologies website

Astro 5 + React 19 islands + Tailwind CSS 4. Static output; JS ships only for the header, counters and forms.

## Run
npm install
npm run dev        # http://localhost:4321
npm run build      # static site in dist/ for https://www.sunsys.in (domain root)
npm run build:staging  # same, for https://sunsysweb.co.in/sunsys-2026/ (upload dist/ into public_html/sunsys-2026)
npm run perf       # production build on http://localhost:4400 (use this for Lighthouse, not npm run dev)

Internal links go through `url()` (src/lib/url.ts) so they work in a subfolder; the PHP side detects the folder itself.

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

## Addresses and redirects
Every page keeps the old WordPress site's address, without a trailing slash (`/about`, `/hotel`; `/about/` 301s to `/about`).
Service and industry pages sit at the site root at the address in their `path` (`src/data/site.ts`); `slug` only names
their photos and details. Blog articles are at `/blog/<post slug>` (Apache routes them to `blog/post/index.php`);
the old `/<post slug>` (WordPress) and `/blog/post/?slug=` addresses 301 there. `blog_path()` in `public/api/_lib/seo.php`
and `postHref` in `src/components/BlogSidebar.jsx` build the address.
Redirects are managed in Admin > SEO > Redirects (table `seo_redirects`). On first run it is filled with this site's
earlier `/services/…` and `/industries/…` addresses (`SEO_SITE_MOVES` in `public/api/_lib/seo.php`: update it and
`path` together) and the old site's unmatched pages (`REDIRECT_WP_DEFAULTS` in `public/api/_lib/redirects.php`).
A page can also be given a new address in Admin > SEO > edit page; its old address then 301s there automatically.
The root `.htaccess` only keeps pattern redirects for the old blog archives (category, tag, author, page/N).

## Forms
Contact, quote and newsletter forms POST JSON to `/api/contact.php` (override with `PUBLIC_FORM_ENDPOINT`).
Job applications POST multipart to `/api/apply.php`.

## Theme
Brand #0266cb | Headings Montserrat 700 | Body Lato 400 | Card titles Raleway 600 (self-hosted via @fontsource).

## Photos
Stock photos in `src/assets/photos/` are from Unsplash (free for commercial use, no credit required; only plain
`images.unsplash.com/photo-…` images, not Unsplash+ `premium_photo-…`). `src/images/about.png` is our own team photo,
`src/images/logo/` are client logos. Unsplash IDs: hero-video 1581291518857-4e27b48ff24e, mission 1454165804606-c3d57bc86b40,
vision 1552581234-26160f608093, cta-team 1522071820081-009f0129c71c.
