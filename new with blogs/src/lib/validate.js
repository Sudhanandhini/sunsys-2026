// Shared form rules for the contact, quote, job application and newsletter forms.
// The PHP endpoints apply the same rules (see valid_* in public/api/_lib/bootstrap.php).

// Names: letters (any language, with their accent/vowel marks), spaces, and . ' - between words. No digits or symbols.
const NAME = /^\p{L}[\p{L}\p{M}]*(?:[ .'-]+\p{L}[\p{L}\p{M}]*)*\.?$/u;
// Emails: local@domain.tld with a real-looking domain (no spaces, no "..", no leading/trailing dots)
const EMAIL = /^[A-Za-z0-9](?:[A-Za-z0-9._%+-]{0,62}[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,24}$/;

/** While typing: drop digits and symbols from a name. */
export const nameInput = (v) => v.replace(/[^\p{L}\p{M} .'-]/gu, '').replace(/ {2,}/g, ' ').slice(0, 60);
/** While typing: digits only, at most 10. A pasted +91 / 0 prefix is dropped rather than cutting off the end. */
export const phoneInput = (v) => {
  let d = v.replace(/\D/g, '');
  if (d.length > 10 && d.startsWith('91')) d = d.slice(2);
  if (d.length > 10 && d.startsWith('0')) d = d.slice(1);
  return d.slice(0, 10);
};
/** While typing: no spaces in an email. */
export const emailInput = (v) => v.replace(/\s/g, '').slice(0, 190);

export function nameError(v) {
  const s = v.trim();
  if (!s) return 'Enter your name.';
  if (s.replace(/[^\p{L}]/gu, '').length < 2) return 'Enter your full name.';
  if (!NAME.test(s)) return 'Use letters only (no numbers or symbols).';
  return '';
}

export function phoneError(v, { required = true } = {}) {
  const s = v.trim();
  if (!s) return required ? 'Enter your phone number.' : '';
  if (!/^\d{10}$/.test(s)) return 'Enter a 10-digit phone number.';
  if (/^0/.test(s) || /^(\d)\1{9}$/.test(s)) return 'Enter a valid phone number.';
  return '';
}

export function emailError(v) {
  const s = v.trim();
  if (!s) return 'Enter your email address.';
  if (!EMAIL.test(s) || s.includes('..')) return 'Enter a valid email address, like name@example.com.';
  return '';
}

/** Free text such as a message or subject: long enough and containing real words. */
export function textError(v, { min = 10, label = 'a message' } = {}) {
  const s = v.trim();
  if (!s) return `Enter ${label}.`;
  if (s.length < min) return `Please write at least ${min} characters.`;
  if ((s.match(/\p{L}/gu) ?? []).length < Math.min(min, 3)) return 'Please describe it in words.';
  return '';
}

/** While typing: years of experience, digits and one decimal point. */
export const experienceInput = (v) => v.replace(/[^\d.]/g, '').replace(/(\..*)\./g, '$1').slice(0, 4);

export function experienceError(v) {
  const s = v.trim();
  if (!s) return '';
  return /^\d{1,2}(\.\d)?$/.test(s) && +s <= 50 ? '' : 'Enter years as a number, like 2 or 3.5.';
}
