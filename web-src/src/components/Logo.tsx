interface LogoProps {
  size?: number;
  withWordmark?: boolean;
}

/** Backupino brand mark — cloud + download arrow inside a rounded gradient tile. */
export function Logo({ size = 36, withWordmark = true }: LogoProps) {
  return (
    <>
      <span className="brand-logo" style={size !== 36 ? { width: size, height: size } : undefined}>
        <svg
          width={size * 0.58}
          height={size * 0.58}
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={2.1}
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
        >
          <path d="M12 3.5c-3.6 0-6.5 2.6-6.5 5.8 0 .24.015.47.045.7A4.25 4.25 0 0 0 6.75 18.5h10.6a4.7 4.7 0 0 0 .74-9.33A6.01 6.01 0 0 0 12 3.5z" />
          <path d="M12 9.5v6m0 0-2.2-2.2M12 15.5l2.2-2.2" />
        </svg>
      </span>
      {withWordmark && <span>Backupino</span>}
    </>
  );
}
