interface LogoProps {
  size?: number;
  withWordmark?: boolean;
}

/** Backupino brand mark — rounded tile with stylized B emblem & restore orbit */
export function Logo({ size = 36, withWordmark = true }: LogoProps) {
  return (
    <>
      <span
        className="brand-logo"
        style={{
          width: size,
          height: size,
          background: "transparent",
          boxShadow: "none",
          padding: 0,
        }}
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 256 256"
          width={size}
          height={size}
          role="img"
          aria-label="Backupino"
        >
          <defs>
            <linearGradient id="logoBgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#1e3a8a" />
              <stop offset="50%" stopColor="#1d4ed8" />
              <stop offset="100%" stopColor="#0f172a" />
            </linearGradient>

            <radialGradient id="logoCoreGlow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stopColor="#38bdf8" stopOpacity="0.28" />
              <stop offset="65%" stopColor="#2563eb" stopOpacity="0.08" />
              <stop offset="100%" stopColor="#0f172a" stopOpacity="0" />
            </radialGradient>

            <linearGradient id="logoOrbitGrad" gradientUnits="userSpaceOnUse" x1="196" y1="190" x2="128" y2="34">
              <stop offset="0%" stopColor="#2563eb" stopOpacity="0.05" />
              <stop offset="45%" stopColor="#06b6d4" stopOpacity="0.65" />
              <stop offset="85%" stopColor="#38bdf8" stopOpacity="0.95" />
              <stop offset="100%" stopColor="#e0f2fe" stopOpacity="1" />
            </linearGradient>

            <linearGradient id="logoBGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#ffffff" />
              <stop offset="65%" stopColor="#f1f5f9" />
              <stop offset="100%" stopColor="#93c5fd" />
            </linearGradient>

            <linearGradient id="logoBoltGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#7dd3fc" />
              <stop offset="100%" stopColor="#06b6d4" />
            </linearGradient>
          </defs>

          <rect x="0" y="0" width="256" height="256" rx="56" fill="url(#logoBgGrad)" />
          <rect
            x="3"
            y="3"
            width="250"
            height="250"
            rx="53"
            fill="none"
            stroke="#60a5fa"
            strokeOpacity="0.22"
            strokeWidth="2"
          />

          <circle cx="128" cy="128" r="96" fill="url(#logoCoreGlow)">
            <animate attributeName="r" values="88;102;88" dur="3.2s" repeatCount="indefinite" />
            <animate attributeName="opacity" values="0.75;1;0.75" dur="3.2s" repeatCount="indefinite" />
          </circle>

          <circle
            cx="128"
            cy="128"
            r="94"
            fill="none"
            stroke="#3b82f6"
            strokeOpacity="0.28"
            strokeWidth="4"
            strokeDasharray="6 10"
            strokeLinecap="round"
          />

          <g>
            <circle
              cx="128"
              cy="128"
              r="74"
              fill="none"
              stroke="#38bdf8"
              strokeOpacity="0.22"
              strokeWidth="2.5"
              strokeDasharray="16 26"
              strokeLinecap="round"
            />
            <animateTransform
              attributeName="transform"
              type="rotate"
              from="360 128 128"
              to="0 128 128"
              dur="9s"
              repeatCount="indefinite"
            />
          </g>

          <g>
            <path
              d="M 205 181.92 A 94 94 0 1 1 121.44 34.23"
              fill="none"
              stroke="url(#logoOrbitGrad)"
              strokeWidth="9"
              strokeLinecap="round"
            />
            <path d="M 141 34 L 118 21 L 124 34 L 118 47 Z" fill="#e0f2fe" />
            <circle cx="217.4" cy="98.95" r="4.5" fill="#38bdf8">
              <animate attributeName="opacity" values="0.35;1;0.35" dur="1.6s" repeatCount="indefinite" />
            </circle>
            <animateTransform
              attributeName="transform"
              type="rotate"
              from="0 128 128"
              to="360 128 128"
              dur="3.2s"
              repeatCount="indefinite"
            />
          </g>

          <g>
            <path
              fill="url(#logoBGrad)"
              fillRule="evenodd"
              d="M 88 66 L 133 66 C 154 66, 167 77, 167 95 C 167 108, 159 118, 146 123 C 162 128, 173 140, 173 159 C 173 180, 156 190, 134 190 L 88 190 Z M 110 86 L 130 86 C 140 86, 146 90, 146 98 C 146 106, 140 111, 130 111 L 110 111 Z M 110 131 L 132 131 C 144 131, 151 137, 151 150 C 151 163, 144 169, 132 169 L 110 169 Z"
            />
            <polygon points="88,115 108,128 88,141" fill="url(#logoBoltGrad)">
              <animate attributeName="opacity" values="0.7;1;0.7" dur="1.6s" repeatCount="indefinite" />
            </polygon>
          </g>
        </svg>
      </span>
      {withWordmark && <span>Backupino</span>}
    </>
  );
}
