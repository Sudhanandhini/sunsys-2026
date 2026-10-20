# Sunsys Technologies website

Astro 5 + React 19 islands + Tailwind CSS 4. Static output; JS ships only for the header, counters and forms.

## Run
npm install
npm run dev        # http://localhost:4321
npm run build      # static site in dist/

## Edit content
All text, services, industries, stats, clients, jobs and contact details: `src/data/site.ts`.
Replace the placeholder phone/email before going live.

## Forms
Set `PUBLIC_FORM_ENDPOINT` in `.env` to your Express endpoint (POST JSON: name, email, phone, service, message).
Without it, the contact form opens the visitor's email client.

## Deploy (cPanel)
Upload the contents of `dist/` to `public_html/`. No Node.js app needed.
.htaccess:  ErrorDocument 404 /404.html

## Theme
Brand #0266cb | Headings Montserrat 700 | Body Lato 400 | Card titles Raleway 600 (self-hosted via @fontsource).
