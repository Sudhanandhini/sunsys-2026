// Site photos live in src/assets/photos so Astro can resize them and convert them to WebP at build time.
// Code keeps referring to them by their old public path ("/images/services/<slug>.jpg").
import type { ImageMetadata } from 'astro';

const files = import.meta.glob<{ default: ImageMetadata }>('/src/assets/photos/**/*.jpg', { eager: true });

export const photo = (path: string): ImageMetadata | undefined =>
  files[path.replace(/^\/images\//, '/src/assets/photos/')]?.default;

/** The path if that photo exists, otherwise undefined (sections fall back to a plain tile). */
export const photoPath = (path: string) => (photo(path) ? path : undefined);
