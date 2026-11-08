/**
 * Brand icons (GitHub, Google) drawn as inline SVGs since lucide no longer
 * ships brand marks. Sized via the `size` prop, colored by currentColor.
 */

interface BrandIconProps {
  size?: number;
  className?: string;
}

export function GithubIcon({ size = 16, className }: BrandIconProps) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="currentColor"
      className={className}
      aria-hidden="true"
      focusable="false"
    >
      <path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56 0-.27-.01-1.17-.02-2.12-3.2.7-3.87-1.36-3.87-1.36-.52-1.33-1.28-1.68-1.28-1.68-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.19 1.76 1.19 1.03 1.76 2.69 1.25 3.35.95.1-.74.4-1.25.72-1.54-2.55-.29-5.23-1.28-5.23-5.68 0-1.26.45-2.28 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11.1 11.1 0 0 1 5.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.12 3.05.74.81 1.18 1.83 1.18 3.09 0 4.41-2.69 5.38-5.25 5.67.41.36.78 1.06.78 2.14 0 1.55-.02 2.79-.02 3.17 0 .31.21.67.8.56A10.52 10.52 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5z" />
    </svg>
  );
}

export function GoogleIcon({ size = 16, className }: BrandIconProps) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" className={className} aria-hidden="true" focusable="false">
      <path
        fill="#4285F4"
        d="M23.49 12.27c0-.85-.08-1.66-.22-2.45H12v4.64h6.45a5.52 5.52 0 0 1-2.39 3.62v3h3.86c2.26-2.09 3.57-5.16 3.57-8.81z"
      />
      <path
        fill="#34A853"
        d="M12 24c3.24 0 5.95-1.08 7.93-2.92l-3.86-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.29v3.1A11.99 11.99 0 0 0 12 24z"
      />
      <path
        fill="#FBBC05"
        d="M5.27 14.27A7.2 7.2 0 0 1 4.89 12c0-.79.14-1.56.38-2.27v-3.1H1.29a11.98 11.98 0 0 0 0 10.74l3.98-3.1z"
      />
      <path
        fill="#EA4335"
        d="M12 4.77c1.76 0 3.34.61 4.58 1.8l3.43-3.43A11.98 11.98 0 0 0 1.29 6.63l3.98 3.1C6.22 6.89 8.87 4.77 12 4.77z"
      />
    </svg>
  );
}

/** Miniature inline-SVG flags for the language dropdown (rounded 20×14). */
export function FlagUS({ className }: { className?: string }) {
  return (
    <svg width="20" height="14" viewBox="0 0 20 14" className={`flag${className ? ` ${className}` : ""}`} aria-hidden="true" focusable="false">
      <rect width="20" height="14" rx="2.5" fill="#f8f9fa" />
      <g fill="#c02c39">
        <rect y="1.1" width="20" height="1.55" />
        <rect y="4.2" width="20" height="1.55" />
        <rect y="7.3" width="20" height="1.55" />
        <rect y="10.4" width="20" height="1.55" />
      </g>
      <rect width="9" height="6.5" rx="1.2" fill="#2a4b8d" />
      <g fill="#fff">
        <circle cx="2" cy="1.8" r="0.55" />
        <circle cx="4.5" cy="1.8" r="0.55" />
        <circle cx="7" cy="1.8" r="0.55" />
        <circle cx="3.2" cy="3.4" r="0.55" />
        <circle cx="5.7" cy="3.4" r="0.55" />
        <circle cx="2" cy="5" r="0.55" />
        <circle cx="4.5" cy="5" r="0.55" />
        <circle cx="7" cy="5" r="0.55" />
      </g>
    </svg>
  );
}

export function FlagIR({ className }: { className?: string }) {
  return (
    <svg width="20" height="14" viewBox="0 0 20 14" className={`flag${className ? ` ${className}` : ""}`} aria-hidden="true" focusable="false">
      <rect width="20" height="14" rx="2.5" fill="#ffffff" />
      <path d="M0 1.2C0 .54.54 0 1.2 0h17.6c.66 0 1.2.54 1.2 1.2v3.6H0z" fill="#239f40" />
      <path d="M0 9.2h20v3.6c0 .66-.54 1.2-1.2 1.2H1.2A1.2 1.2 0 0 1 0 12.8z" fill="#da0000" />
      <circle cx="10" cy="6.95" r="1.7" fill="none" stroke="#da0000" strokeWidth="0.8" />
      <circle cx="10" cy="6.95" r="0.55" fill="#da0000" />
      <rect x="1" y="4.35" width="18" height="0.45" fill="#239f40" opacity="0.65" />
      <rect x="1" y="9.15" width="18" height="0.45" fill="#239f40" opacity="0.65" />
    </svg>
  );
}
