// Long-form content for the service and industry detail pages.
// Kept separate from site.ts so the short summaries used across the site stay easy to scan.
// Wording describes the work only: no prices, timelines or guarantees. Edit freely.

type Item = { title: string; text: string };
type Faq = { q: string; a: string };
export type ServiceDetail = { intro: string; includes: Item[]; idealFor: string[]; faqs: Faq[] };
export type IndustryDetail = { intro: string; features: Item[]; services: string[]; faqs: Faq[] };

const commonFaqs: Faq[] = [
  { q: 'How do we get started?', a: 'Send us a few details through the contact form. We will set up a free consultation, understand what you need and share a quote.' },
  { q: 'Will I be able to update the website myself?', a: 'Yes. We build on tools your team can use, and we walk you through making everyday changes before handover.' },
];

export const serviceDetails: Record<string, ServiceDetail> = {
  'website-development': {
    intro: 'A business website planned, designed and built by one team, so nothing falls between the cracks. We start with what your visitors need to find and shape every page around it.',
    includes: [
      { title: 'Planning and sitemap', text: 'We map the pages, content and calls to action before any design work starts.' },
      { title: 'Design for your brand', text: 'Layouts, colours and typography built around your identity, reviewed with you page by page.' },
      { title: 'Fast, clean build', text: 'Lightweight pages that load quickly and are structured for search engines.' },
      { title: 'Launch and handover', text: 'We go live, test on real devices and show your team how to manage the site.' },
    ],
    idealFor: ['New businesses that need a first website', 'Companies replacing an outdated site', 'Teams that want one partner for the whole job'],
    faqs: [
      { q: 'Do you write the content too?', a: 'We help you plan the content for each page and can shape the copy you provide so it reads well on the web.' },
      ...commonFaqs,
    ],
  },
  'wordpress-development': {
    intro: 'WordPress websites built to your design, not a generic theme. You get a site that looks right, runs smoothly and is easy for your team to update.',
    includes: [
      { title: 'Custom theme', text: 'A theme built for your design, so pages look exactly as planned.' },
      { title: 'Editor-friendly setup', text: 'Content blocks and fields arranged so updates are simple for non-technical staff.' },
      { title: 'Plugins chosen with care', text: 'Only the plugins you need, configured and tested together.' },
      { title: 'Security and backups', text: 'Sensible security settings and a backup routine from day one.' },
    ],
    idealFor: ['Businesses that publish news or blog posts', 'Teams that want to edit pages themselves', 'Sites that need to grow over time'],
    faqs: [
      { q: 'Can you move my existing site to WordPress?', a: 'Yes. We can migrate your pages and content and set up redirects so existing links keep working.' },
      ...commonFaqs,
    ],
  },
  'ecommerce-development': {
    intro: 'Online stores built to sell: clear product pages, a smooth checkout and order management that fits how your business works.',
    includes: [
      { title: 'Product catalogue', text: 'Categories, filters and product pages that help customers find what they want.' },
      { title: 'Checkout and payments', text: 'A simple checkout connected to the payment options your customers use.' },
      { title: 'Orders and stock', text: 'Tools to manage orders, inventory and customer details in one place.' },
      { title: 'Mobile-first shopping', text: 'Stores designed for buying on phones as easily as on desktops.' },
    ],
    idealFor: ['Retailers starting to sell online', 'Brands moving off a marketplace', 'Stores that have outgrown their current platform'],
    faqs: [
      { q: 'Which payment gateways can you connect?', a: 'We connect the gateway that suits your business and customers, and test the full payment flow before launch.' },
      ...commonFaqs,
    ],
  },
  'custom-web-development': {
    intro: 'Web applications built around your exact process: portals, dashboards and internal tools that save your team time.',
    includes: [
      { title: 'Requirement workshops', text: 'We map your workflow first, so the software fits the way you already work.' },
      { title: 'Custom features', text: 'Forms, dashboards, user roles and reports built for your needs.' },
      { title: 'Integrations', text: 'Connections to the systems and services you already use.' },
      { title: 'Ongoing improvements', text: 'Support to add features as your business changes.' },
    ],
    idealFor: ['Businesses running key work on spreadsheets', 'Teams that need a client or staff portal', 'Processes no off-the-shelf tool fits'],
    faqs: [
      { q: 'Which technologies do you use?', a: 'We choose the stack to fit the project, commonly React and Node.js for applications and PHP where it suits.' },
      ...commonFaqs,
    ],
  },
  'woocommerce-development': {
    intro: 'WooCommerce stores on WordPress, set up with the products, payments and integrations your business needs.',
    includes: [
      { title: 'Store setup', text: 'Products, variations, shipping and tax configured for your catalogue.' },
      { title: 'Custom design', text: 'A storefront that matches your brand rather than a stock theme.' },
      { title: 'Integrations', text: 'Payment gateways, shipping partners and other tools connected.' },
      { title: 'Store care', text: 'Updates and checks so the store keeps running smoothly.' },
    ],
    idealFor: ['Businesses already on WordPress', 'Small to mid-sized catalogues', 'Owners who want to manage the store themselves'],
    faqs: [
      { q: 'Can you add WooCommerce to my existing WordPress site?', a: 'Yes. We can add a store to your current site and style it to match.' },
      ...commonFaqs,
    ],
  },
  'landing-page': {
    intro: 'Focused landing pages for a single campaign or offer, designed to turn visitors into enquiries.',
    includes: [
      { title: 'One clear message', text: 'A page built around a single offer and call to action.' },
      { title: 'Persuasive layout', text: 'Benefits, proof and answers to common questions placed where visitors look.' },
      { title: 'Enquiry capture', text: 'Short forms that are easy to fill in on any device.' },
      { title: 'Ready for campaigns', text: 'Set up to track visits and conversions from your ads.' },
    ],
    idealFor: ['Advertising campaigns', 'Product or event launches', 'Testing a new offer quickly'],
    faqs: [
      { q: 'Can the landing page sit on my existing domain?', a: 'Yes. We can host it on your domain or a subdomain, whichever suits the campaign.' },
      ...commonFaqs,
    ],
  },
  'cms-web-development': {
    intro: 'Websites on a content management system your team can update without calling a developer.',
    includes: [
      { title: 'The right CMS', text: 'We recommend a CMS that suits your content and team.' },
      { title: 'Structured content', text: 'Pages and sections set up so content stays consistent.' },
      { title: 'User roles', text: 'Different access levels for editors, reviewers and admins.' },
      { title: 'Training', text: 'A walkthrough so your team is confident making changes.' },
    ],
    idealFor: ['Teams that update the site often', 'Organisations with several editors', 'Sites with many pages or sections'],
    faqs: [
      { q: 'Which CMS do you recommend?', a: 'It depends on your content and team. WordPress suits most business sites, and we will explain the options during consultation.' },
      ...commonFaqs,
    ],
  },
  'website-maintenance': {
    intro: 'Monthly care that keeps your website updated, backed up and secure, so you can focus on your business.',
    includes: [
      { title: 'Updates', text: 'Core, theme and plugin updates applied and checked.' },
      { title: 'Backups', text: 'Regular backups so your site can be restored if needed.' },
      { title: 'Monitoring', text: 'Uptime and security checks to catch problems early.' },
      { title: 'Content changes', text: 'Small edits handled on request as part of your plan.' },
    ],
    idealFor: ['Businesses without an in-house web team', 'Sites that must stay online', 'Owners who want peace of mind'],
    faqs: [
      { q: 'Can you maintain a site you did not build?', a: 'Yes. We review the site first, fix any urgent issues and then take over regular care.' },
      ...commonFaqs,
    ],
  },
  'website-redesign': {
    intro: 'Refresh an older website with modern design and better performance, while keeping the content and search rankings that already work.',
    includes: [
      { title: 'Site audit', text: 'We review what works and what holds your current site back.' },
      { title: 'New design', text: 'A fresh look and clearer structure for today’s visitors.' },
      { title: 'SEO-safe move', text: 'Redirects and careful migration so existing links keep working.' },
      { title: 'Content migration', text: 'Your pages, images and posts moved across and tidied up.' },
    ],
    idealFor: ['Sites that look dated', 'Sites that are hard to use on phones', 'Businesses that have changed since launch'],
    faqs: [
      { q: 'Will a redesign affect my Google rankings?', a: 'We plan the move carefully with redirects and matching content so you keep the visibility you have built.' },
      ...commonFaqs,
    ],
  },
  'responsive-web-design': {
    intro: 'Websites that work well on phones, tablets and desktops, so every visitor gets a site that is easy to read and use.',
    includes: [
      { title: 'Mobile-first layouts', text: 'Pages designed for small screens first, then scaled up.' },
      { title: 'Touch-friendly navigation', text: 'Menus and buttons sized for fingers, not just mouse pointers.' },
      { title: 'Optimised images', text: 'Images sized for each screen so pages load quickly.' },
      { title: 'Cross-device testing', text: 'Checked on real phones, tablets and browsers before launch.' },
    ],
    idealFor: ['Sites with many mobile visitors', 'Older sites that are hard to use on phones', 'Businesses relying on local search'],
    faqs: [
      { q: 'Can you make my current website responsive?', a: 'Often, yes. We review the existing site and advise whether an update or a redesign is the better route.' },
      ...commonFaqs,
    ],
  },
  'linux-hosting': {
    intro: 'Linux hosting with cPanel and free SSL, suited to PHP, WordPress and Node.js websites.',
    includes: [
      { title: 'cPanel access', text: 'Manage files, databases and email from a familiar control panel.' },
      { title: 'Free SSL', text: 'Secure HTTPS for your site at no extra cost.' },
      { title: 'Broad support', text: 'PHP, MySQL and Node.js applications supported.' },
      { title: 'Daily backups', text: 'Regular backups to keep your data safe.' },
    ],
    idealFor: ['WordPress and PHP websites', 'Node.js applications', 'Businesses that want local support'],
    faqs: [
      { q: 'Can you move my site from another host?', a: 'Yes. We help migrate your website and email with as little disruption as possible.' },
      { q: 'Who do I contact if something goes wrong?', a: 'Our Bengaluru team, through the contact form during office hours.' },
    ],
  },
  'web-hosting': {
    intro: 'Windows and Linux hosting plans for everything from small business sites to busy applications.',
    includes: [
      { title: 'Windows or Linux', text: 'Plans on the platform your website needs.' },
      { title: 'Free migration', text: 'Help moving your site from your current host.' },
      { title: 'Domain registration', text: 'Register and manage your domain alongside hosting.' },
      { title: 'Local support', text: 'Help from our team in Bengaluru when you need it.' },
    ],
    idealFor: ['Business websites of any size', 'Sites moving from an unreliable host', 'Owners who want hosting and domain in one place'],
    faqs: [
      { q: 'How do I choose between Windows and Linux?', a: 'It depends on how your site is built. Tell us about it and we will recommend the right plan.' },
      { q: 'Can you move my site from another host?', a: 'Yes. Migration help is included.' },
    ],
  },
  'email-hosting': {
    intro: 'Professional email on your own domain, set up on your team’s phones and computers.',
    includes: [
      { title: 'Email on your domain', text: 'Addresses like name@yourbusiness.com for the whole team.' },
      { title: 'Spam and virus filtering', text: 'Filtering to keep inboxes clean and safe.' },
      { title: 'Device setup', text: 'Configured on phones and desktop mail apps.' },
      { title: 'Mailbox migration', text: 'Existing mail moved over from your old provider.' },
    ],
    idealFor: ['Teams using free email addresses', 'Businesses changing email providers', 'Companies adding new staff'],
    faqs: [
      { q: 'Will I lose my old emails?', a: 'No. We can migrate existing mailboxes to the new service.' },
      { q: 'Can you set it up on our phones?', a: 'Yes. We configure email on phones and desktop mail apps for your team.' },
    ],
  },
};

export const industryDetails: Record<string, IndustryDetail> = {
  'tours-and-travels': {
    intro: 'Travel websites that inspire trips and make enquiring easy, with packages, itineraries and booking requests in one place.',
    features: [
      { title: 'Package listings', text: 'Tours organised by destination, duration and theme.' },
      { title: 'Day-by-day itineraries', text: 'Clear plans with photos, inclusions and exclusions.' },
      { title: 'Enquiry and booking forms', text: 'Simple forms that capture dates, travellers and preferences.' },
      { title: 'Galleries and reviews', text: 'Photos and traveller feedback that build confidence.' },
    ],
    services: ['website-development', 'responsive-web-design', 'cms-web-development', 'website-maintenance'],
    faqs: [
      { q: 'Can I add and update packages myself?', a: 'Yes. We set up package pages so your team can add tours and change details without help.' },
      commonFaqs[0],
    ],
  },
  'hotel-and-resort': {
    intro: 'Hotel and resort websites that show off your property and encourage direct bookings.',
    features: [
      { title: 'Room galleries', text: 'Rooms and suites presented with photos, amenities and rates.' },
      { title: 'Availability enquiries', text: 'Forms that capture dates and guests in a few taps.' },
      { title: 'Direct booking', text: 'Booking flows that help you rely less on third-party sites.' },
      { title: 'Facilities and offers', text: 'Dining, events and seasonal offers on their own pages.' },
    ],
    services: ['website-development', 'ecommerce-development', 'responsive-web-design', 'website-maintenance'],
    faqs: [
      { q: 'Can you connect a booking engine?', a: 'Yes. We can link or embed the booking system you use and match it to your site design.' },
      commonFaqs[0],
    ],
  },
  'healthcare': {
    intro: 'Clear, trustworthy websites for clinics and hospitals that help patients find the right care.',
    features: [
      { title: 'Doctor profiles', text: 'Specialities, qualifications and timings for each doctor.' },
      { title: 'Appointment requests', text: 'Easy forms to request a visit or a call back.' },
      { title: 'Service pages', text: 'Departments and treatments explained in plain language.' },
      { title: 'Location and contact', text: 'Maps, timings and directions for every branch.' },
    ],
    services: ['website-development', 'cms-web-development', 'responsive-web-design', 'website-maintenance'],
    faqs: [
      { q: 'Can we update doctor timings ourselves?', a: 'Yes. Doctor profiles and timings are set up as editable content for your staff.' },
      commonFaqs[0],
    ],
  },
  'corporate': {
    intro: 'Company websites that present your services, leadership and careers with clarity and credibility.',
    features: [
      { title: 'Services and solutions', text: 'What you offer, explained for each audience.' },
      { title: 'Leadership and story', text: 'Your team, history and values presented with care.' },
      { title: 'Careers section', text: 'Current openings and an easy way to apply.' },
      { title: 'News and updates', text: 'Announcements and articles your team can publish.' },
    ],
    services: ['website-development', 'wordpress-development', 'custom-web-development', 'website-maintenance'],
    faqs: [
      { q: 'Can the site have separate sections for different divisions?', a: 'Yes. We structure the site so each division or service line has its own clear area.' },
      commonFaqs[0],
    ],
  },
  'education': {
    intro: 'School and college websites that keep parents, students and staff informed.',
    features: [
      { title: 'Admissions', text: 'Courses, eligibility and enquiry forms in one clear flow.' },
      { title: 'Events and notices', text: 'Announcements and calendars staff can update quickly.' },
      { title: 'Galleries', text: 'Photos from campus life, events and achievements.' },
      { title: 'Departments and faculty', text: 'Pages for each department and its teachers.' },
    ],
    services: ['website-development', 'cms-web-development', 'responsive-web-design', 'website-maintenance'],
    faqs: [
      { q: 'Can several staff members post notices?', a: 'Yes. We set up user roles so different staff can publish within their own areas.' },
      commonFaqs[0],
    ],
  },
  'real-estate-and-construction': {
    intro: 'Websites for builders and developers that showcase projects and turn interest into site visits.',
    features: [
      { title: 'Project showcases', text: 'Ongoing and completed projects with photos and progress.' },
      { title: 'Floor plans and specs', text: 'Layouts, amenities and specifications for each project.' },
      { title: 'Site-visit enquiries', text: 'Forms to book a visit or request a call back.' },
      { title: 'Location details', text: 'Maps and nearby landmarks for every project.' },
    ],
    services: ['website-development', 'landing-page', 'responsive-web-design', 'website-maintenance'],
    faqs: [
      { q: 'Can we add a landing page for each new project?', a: 'Yes. We can create dedicated project pages or campaign landing pages as you launch.' },
      commonFaqs[0],
    ],
  },
};
