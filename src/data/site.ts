// Single source of truth for site content. Edit here; pages update automatically.

export const company = {
  name: 'Sunsys Technologies',
  legalName: 'Sunsys Technologies (India) Pvt. Ltd.',
  founder: 'Mr. Manjunatha Narasimhaiah',
  founded: 2006,
  registered: 2009,
  address: '4, 2nd Floor, 8th Main Road, CHBS Layout, Vijayanagar, Bengaluru 560040',
  hours: 'Mon to Sat, 9:00 am to 6:00 pm',
  social: {
    facebook: '#',
    instagram: '#',
    x: '#',
    linkedin: '#',
  },
  // Contact / quote / newsletter form POST endpoint (e.g. your Express API). Forms cannot send until this is set.
  formEndpoint: import.meta.env.PUBLIC_FORM_ENDPOINT ?? '',
};

export const nav = [
  { label: 'Home', href: '/' },
  { label: 'About Us', href: '/about' },
  { label: 'Services', href: '/services', mega: true },
  { label: 'Blog', href: '/blog' },
  { label: 'Career', href: '/career' },
  { label: 'Contact Us', href: '/contact' },
];

export type Service = {
  slug: string;
  title: string;
  icon: string;
  group: 'web' | 'hosting';
  summary: string;
  body: string;
  points: string[];
};

export const services: Service[] = [
  {
    slug: 'website-development', title: 'Website Development', icon: 'Globe', group: 'web',
    summary: 'Business websites planned, designed and built end to end by one team.',
    body: 'From the first sitemap to the day it goes live, we handle content structure, design, development and launch so you have one team accountable for the whole site.',
    points: ['Sitemap and content planning', 'Custom design for your brand', 'Fast, search-friendly build', 'Launch, training and handover'],
  },
  {
    slug: 'wordpress-development', title: 'WordPress Development', icon: 'Newspaper', group: 'web',
    summary: 'Custom WordPress websites with responsive design and seamless functionality.',
    body: 'Custom themes and plugins built for how your team actually edits content, without bloated page builders slowing the site down.',
    points: ['Custom theme development', 'Plugin setup and custom plugins', 'Easy editing for your team', 'Speed and security hardening'],
  },
  {
    slug: 'ecommerce-development', title: 'E-Commerce Development', icon: 'ShoppingCart', group: 'web',
    summary: 'Online stores with smooth checkout, payments and inventory built in.',
    body: 'Stores that make it easy to browse, pay and reorder, connected to the payment gateways and shipping partners you already use in India and abroad.',
    points: ['Product catalogue and search', 'Payment gateway integration', 'Order and inventory management', 'GST-ready invoices'],
  },
  {
    slug: 'custom-web-development', title: 'Custom Web Development', icon: 'Code', group: 'web',
    summary: 'Web applications built around your exact business process.',
    body: 'When an off-the-shelf tool does not fit, we build portals, dashboards and internal tools with modern stacks such as React, Node.js, PHP and MySQL.',
    points: ['Requirement study and scoping', 'Portals, dashboards and admin panels', 'API and third-party integrations', 'Ongoing support after launch'],
  },
  {
    slug: 'woocommerce-development', title: 'WooCommerce Development', icon: 'Store', group: 'web',
    summary: 'WooCommerce stores with the integrations your business needs.',
    body: 'Set up a new WooCommerce store or extend an existing one with custom checkout steps, product types, and integrations.',
    points: ['Store setup and theming', 'Custom checkout and product types', 'Payment and shipping integrations', 'Migration from other platforms'],
  },
  {
    slug: 'landing-page', title: 'Landing Page', icon: 'MousePointerClick', group: 'web',
    summary: 'Focused landing pages designed to turn visitors into enquiries.',
    body: 'Single-purpose pages for campaigns and launches, built to load fast and lead visitors to one clear action.',
    points: ['Conversion-focused layout', 'Lead forms with notifications', 'Analytics and ad tracking', 'A/B test-ready variants'],
  },
  {
    slug: 'cms-web-development', title: 'CMS Web Development', icon: 'LayoutGrid', group: 'web',
    summary: 'Websites your team can update without calling a developer.',
    body: 'We set up a content management system that matches your content, with clear fields and roles so updates stay quick and on-brand.',
    points: ['Structured content types', 'User roles and permissions', 'Media library setup', 'Team training'],
  },
  {
    slug: 'website-maintenance', title: 'Website Maintenance', icon: 'Wrench', group: 'web',
    summary: 'Regular updates, backups and security checks for your website.',
    body: 'Monthly care plans that keep your site updated, backed up and monitored, with a fixed number of change requests included.',
    points: ['Core and plugin updates', 'Daily or weekly backups', 'Uptime and security monitoring', 'Content changes on request'],
  },
  {
    slug: 'website-redesign', title: 'Website Redesign', icon: 'RefreshCw', group: 'web',
    summary: 'Refresh an older site with modern design and better performance.',
    body: 'We keep what works on your current site, including content and search rankings, and rebuild the rest for today’s devices and visitors.',
    points: ['Audit of the current site', 'New design and structure', 'SEO-safe URL redirects', 'Content migration'],
  },
  {
    slug: 'responsive-web-design', title: 'Responsive Web Design', icon: 'MonitorSmartphone', group: 'web',
    summary: 'Websites that work well on phones, tablets and desktops.',
    body: 'Layouts designed and tested across screen sizes so every visitor gets a site that is easy to read and use.',
    points: ['Mobile-first layouts', 'Touch-friendly navigation', 'Optimised images', 'Cross-browser testing'],
  },
  {
    slug: 'linux-hosting', title: 'Linux Hosting', icon: 'Terminal', group: 'hosting',
    summary: 'Reliable Linux hosting with cPanel for PHP, WordPress and Node.js sites.',
    body: 'Linux hosting plans with cPanel, free SSL and support for PHP, WordPress and Node.js applications.',
    points: ['cPanel access', 'Free SSL certificates', 'PHP, MySQL and Node.js support', 'Daily backups'],
  },
  {
    slug: 'web-hosting', title: 'Web Hosting', icon: 'Server', group: 'hosting',
    summary: 'Windows and Linux hosting plans sized for your website.',
    body: 'Hosting plans for small business sites through to busy applications, with help migrating from your current host.',
    points: ['Windows and Linux plans', 'Free migration', 'Domain registration', 'Local support from Bengaluru'],
  },
  {
    slug: 'email-hosting', title: 'Email Hosting', icon: 'Mail', group: 'hosting',
    summary: 'Professional email on your own domain, set up and managed for you.',
    body: 'Business email addresses on your domain, configured on phones and desktop mail clients for your whole team.',
    points: ['Email on your domain', 'Spam and virus filtering', 'Mobile and desktop setup', 'Mailbox migration'],
  },
];

export const industries = [
  { slug: 'tours-and-travels', title: 'Tours and Travels', icon: 'Plane', summary: 'Package listings, enquiry forms and booking flows for travel agencies.' },
  { slug: 'hotel-and-resort', title: 'Hotel & Resort', icon: 'BedDouble', summary: 'Room galleries, availability enquiries and direct booking for hotels and resorts.' },
  { slug: 'healthcare', title: 'Healthcare', icon: 'HeartPulse', summary: 'Doctor profiles, appointment requests and clear service pages for clinics and hospitals.' },
  { slug: 'corporate', title: 'Corporate', icon: 'Building2', summary: 'Company websites that present your services, leadership and careers clearly.' },
  { slug: 'education', title: 'Education', icon: 'GraduationCap', summary: 'Admissions, events, galleries and notices for schools and colleges.' },
  { slug: 'real-estate-and-construction', title: 'Real Estate & Construction', icon: 'HardHat', summary: 'Project showcases, floor plans and site-visit enquiries for builders and developers.' },
];

export const stats = [
  { value: 10, suffix: '+', label: 'Team members' },
  { value: 1300, suffix: '+', label: 'Projects completed' },
  { value: 20, suffix: '+', label: 'Years of experience' },
  { value: 1000, suffix: '+', label: 'Clients served' },
];

export const ratings = [
  { name: 'Trustpilot', score: 5, text: 'Rated 5 stars on Trustpilot' },
  { name: 'Clutch', score: 4, text: 'Rated 4 stars on Clutch' },
  { name: 'G2', score: 3.5, text: 'Rated 3.5 stars on G2' },
];

export const partners = ['Google Partner', 'Microsoft Partner', 'ResellerClub', 'PhonePe', 'ICANN Accredited'];

export const clients = [
  'Supreme Infrastructure', 'Alcon', 'Bharat Electronics', 'ChildFund India', 'KLE Society',
  'Indian Institute of Science', 'Spectrum', 'Indian Cricketers’ Association', 'DynaFusion', 'Apollo',
];

export const footprints = ['India', 'UAE', 'United Kingdom', 'United States', 'Canada', 'Israel', 'Nigeria', 'Australia'];

export const processSteps = [
  { title: 'Discover', text: 'We understand your business, audience and goals, and agree on scope and timeline.' },
  { title: 'Design', text: 'You review page designs and give feedback before any code is written.' },
  { title: 'Build', text: 'We develop, test on real devices and load your content.' },
  { title: 'Launch & support', text: 'We go live, train your team and stay available for updates.' },
];

// Sample content — replace with real posts and openings.
export const posts = [
  { slug: '#', title: 'How long does it take to build a business website?', date: '2026-09-18', excerpt: 'A realistic timeline for a 10 to 15 page site, and what speeds it up or slows it down.' },
  { slug: '#', title: 'WordPress or custom build: which fits your business?', date: '2026-08-27', excerpt: 'When a CMS is the right call, and when a custom application saves money long term.' },
  { slug: '#', title: 'Five checks before you switch web hosts', date: '2026-08-05', excerpt: 'Backups, DNS, email and SSL: what to confirm so the move causes no downtime.' },
];

export const openings = [
  { title: 'React Developer', type: 'Full-time', exp: '1 to 3 years', text: 'Build React and Node.js applications for client projects.' },
  { title: 'WordPress / PHP Developer', type: 'Full-time', exp: '1 to 3 years', text: 'Develop custom themes and plugins and maintain client sites.' },
  { title: 'Digital Marketing Executive', type: 'Full-time', exp: '0 to 2 years', text: 'Run SEO and social media campaigns for our clients.' },
];
