/**
 * art.tsx — Theme-aware inline SVG illustrations for page heroes.
 * All colors derive from CSS variables (var(--accent), var(--accent-light),
 * var(--success), …) so every illustration adapts to dark/light themes.
 */

interface ArtProps {
  className?: string;
}

/* ---------- Home: shield + data blocks + circular backup cycle + clouds ---------- */
export function HomeArt({ className }: ArtProps) {
  return (
    <svg viewBox="0 0 400 300" fill="none" className={className} aria-hidden="true" role="presentation">
      {/* clouds */}
      <g className="art-float-slow" opacity="0.9">
        <path d="M62 78c0-10 8-18 18-18 2 0 4 .4 5.8 1.1C89.4 54 97.5 48 107 48c13.3 0 24 10.7 24 24 0 .6 0 1.2-.1 1.8.6-.1 1.2-.1 1.9-.1 8.8 0 16 7.2 16 16H62V78z" fill="var(--accent-light)" />
        <path d="M292 52c0-8 6.5-14.5 14.5-14.5 1.6 0 3.2.3 4.6.9C314 34.5 320.5 30 328 30c10.5 0 19 8.5 19 19 0 .5 0 1-.1 1.4.5 0 1 .1 1.5.1 7 0 12.6 5.6 12.6 12.5H292V52z" fill="var(--accent-light)" opacity="0.7" />
      </g>
      {/* circular cycle arrows */}
      <g stroke="var(--accent)" strokeWidth="3" strokeLinecap="round" className="art-spin-slow" style={{ transformOrigin: "200px 165px" }}>
        <path d="M263 115a74 74 0 0 1 6 30" opacity="0.55" />
        <path d="M137 215a74 74 0 0 1-6-30" opacity="0.55" />
        <path d="m256 102 9 15-17-3z" fill="var(--accent)" stroke="none" opacity="0.55" />
        <path d="m144 228-9-15 17 3z" fill="var(--accent)" stroke="none" opacity="0.55" />
      </g>
      {/* data blocks flowing in */}
      <g className="art-float-a">
        <rect x="58" y="128" width="34" height="26" rx="6" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="2.5" />
        <path d="M65 141h20" stroke="var(--accent)" strokeWidth="2.5" strokeLinecap="round" opacity="0.6" />
      </g>
      <g className="art-float-b">
        <rect x="70" y="170" width="34" height="26" rx="6" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="2.5" opacity="0.85" />
        <path d="M77 183h20" stroke="var(--accent)" strokeWidth="2.5" strokeLinecap="round" opacity="0.5" />
      </g>
      <g className="art-float-c">
        <rect x="88" y="96" width="34" height="26" rx="6" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="2.5" opacity="0.7" />
        <path d="M95 109h20" stroke="var(--accent)" strokeWidth="2.5" strokeLinecap="round" opacity="0.45" />
      </g>
      {/* shield */}
      <g className="art-shield">
        <path d="M200 68l78 28v58c0 52-34 84-78 102-44-18-78-50-78-102V96l78-28z" fill="var(--accent-light)" stroke="var(--accent)" strokeWidth="4" strokeLinejoin="round" />
        <path d="M200 88l58 21v45c0 40-25 65-58 80-33-15-58-40-58-80v-45l58-21z" fill="var(--card-bg)" opacity="0.75" />
        <path d="m176 160 16 16 34-36" stroke="var(--accent)" strokeWidth="7" strokeLinecap="round" strokeLinejoin="round" />
      </g>
      {/* dots */}
      <circle cx="318" cy="205" r="5" fill="var(--success)" className="art-pulse-dot" />
      <circle cx="86" cy="232" r="4" fill="var(--accent)" opacity="0.5" />
      <circle cx="330" cy="120" r="3.5" fill="var(--accent)" opacity="0.4" />
    </svg>
  );
}

/* ---------- About: open-source node network ---------- */
export function AboutArt({ className }: ArtProps) {
  return (
    <svg viewBox="0 0 400 300" fill="none" className={className} aria-hidden="true" role="presentation">
      <g stroke="var(--accent)" strokeWidth="2" opacity="0.35">
        <path d="M200 150 96 88M200 150l104-62M200 150l-118 56M200 150l118 56M200 150V64M200 150v92" />
        <path d="M96 88 82 206M304 88l14 118M96 88h208M82 206h236" strokeDasharray="3 7" />
      </g>
      {[
        [200, 64, 7],
        [96, 88, 9],
        [304, 88, 9],
        [82, 206, 9],
        [318, 206, 9],
        [200, 242, 7],
      ].map(([cx, cy, r], i) => (
        <g key={i} className={`art-node art-node-${(i % 3) + 1}`}>
          <circle cx={cx} cy={cy} r={Number(r) + 7} fill="var(--accent-light)" />
          <circle cx={cx} cy={cy} r={r} fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="2.5" />
        </g>
      ))}
      {/* central emblem */}
      <g className="art-shield">
        <circle cx="200" cy="150" r="42" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="4" />
        <circle cx="200" cy="150" r="42" fill="var(--accent-light)" opacity="0.55" />
        <path d="M182 138h36c0 22-8 32-18 40-10-8-18-18-18-40z" fill="none" stroke="var(--accent)" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" />
      </g>
      <circle cx="258" cy="52" r="4" fill="var(--success)" className="art-pulse-dot" />
      <circle cx="140" cy="255" r="4" fill="var(--accent)" opacity="0.45" />
    </svg>
  );
}

/* ---------- Pricing: ascending tier bars + checkmarks ---------- */
export function PricingArt({ className }: ArtProps) {
  return (
    <svg viewBox="0 0 400 300" fill="none" className={className} aria-hidden="true" role="presentation">
      <g opacity="0.5" stroke="var(--accent)" strokeWidth="2" strokeDasharray="4 8">
        <path d="M40 240h320" />
      </g>
      <g className="art-grow art-grow-1" style={{ transformOrigin: "90px 240px" }}>
        <rect x="58" y="172" width="64" height="68" rx="10" fill="var(--accent-light)" stroke="var(--accent)" strokeWidth="2.5" />
        <path d="m76 204 9 9 15-17" stroke="var(--accent)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.7" />
      </g>
      <g className="art-grow art-grow-2" style={{ transformOrigin: "168px 240px" }}>
        <rect x="136" y="134" width="64" height="106" rx="10" fill="var(--accent-light)" stroke="var(--accent)" strokeWidth="2.5" />
        <path d="m154 166 9 9 15-17" stroke="var(--accent)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.8" />
        <path d="m154 188 9 9 15-17" stroke="var(--accent)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.55" />
      </g>
      <g className="art-grow art-grow-3" style={{ transformOrigin: "246px 240px" }}>
        <rect x="214" y="96" width="64" height="144" rx="10" fill="var(--accent)" stroke="var(--accent)" strokeWidth="2.5" />
        <path d="m232 128 9 9 15-17" stroke="var(--accent-contrast)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" />
        <path d="m232 150 9 9 15-17" stroke="var(--accent-contrast)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.7" />
        <path d="m232 172 9 9 15-17" stroke="var(--accent-contrast)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" opacity="0.45" />
      </g>
      <g className="art-grow art-grow-4" style={{ transformOrigin: "324px 240px" }}>
        <rect x="292" y="60" width="64" height="180" rx="10" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="3" strokeDasharray="7 6" />
        <circle cx="324" cy="90" r="13" fill="var(--accent-light)" stroke="var(--accent)" strokeWidth="2.5" />
        <path d="m318 90 4.5 4.5 8-9" stroke="var(--accent)" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
      </g>
      {/* trending arrow */}
      <g className="art-float-slow" stroke="var(--success)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round">
        <path d="M62 148 150 116l76-26 96-38" />
        <path d="M310 44h16v16" />
      </g>
      <circle cx="70" cy="70" r="4" fill="var(--accent)" opacity="0.4" className="art-pulse-dot" />
    </svg>
  );
}

/* ---------- Pro: rocket launching from a dashboard panel ---------- */
export function ProArt({ className }: ArtProps) {
  return (
    <svg viewBox="0 0 400 300" fill="none" className={className} aria-hidden="true" role="presentation">
      {/* dashboard panel */}
      <g className="art-float-slow">
        <rect x="62" y="176" width="276" height="92" rx="14" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="3" />
        <rect x="62" y="176" width="276" height="30" rx="14" fill="var(--accent-light)" />
        <circle cx="82" cy="191" r="4" fill="var(--accent)" />
        <circle cx="96" cy="191" r="4" fill="var(--accent)" opacity="0.55" />
        <circle cx="110" cy="191" r="4" fill="var(--accent)" opacity="0.3" />
        <rect x="80" y="220" width="70" height="10" rx="5" fill="var(--accent-light)" />
        <rect x="80" y="240" width="46" height="10" rx="5" fill="var(--accent-light)" opacity="0.7" />
        <rect x="250" y="218" width="70" height="34" rx="8" fill="var(--accent)" opacity="0.16" />
        <path d="m262 240 8 8 14-16" stroke="var(--accent)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" />
      </g>
      {/* rocket */}
      <g className="art-rocket">
        <path d="M200 32c26 20 38 52 34 96l-34 18-34-18c-4-44 8-76 34-96z" fill="var(--accent-light)" stroke="var(--accent)" strokeWidth="3.5" strokeLinejoin="round" />
        <circle cx="200" cy="86" r="13" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="3" />
        <path d="m166 128-16 26 22-8M234 128l16 26-22-8" stroke="var(--accent)" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" fill="var(--accent-light)" />
        <path d="M188 150c2 10 5 16 12 22 7-6 10-12 12-22" stroke="var(--warning)" strokeWidth="4" strokeLinecap="round" fill="none" />
        <path d="M196 168c1 6 2 9 4 12 2-3 3-6 4-12" stroke="var(--error)" strokeWidth="3.5" strokeLinecap="round" fill="none" />
      </g>
      {/* gears */}
      <g className="art-spin-slow" style={{ transformOrigin: "318px 96px" }} stroke="var(--accent)" strokeWidth="3" opacity="0.75">
        <circle cx="318" cy="96" r="16" fill="var(--card-bg)" />
        <path d="M318 72v10M318 110v10M294 96h10M332 96h10M301 79l7 7M328 106l7 7M335 79l-7 7M308 106l-7 7" strokeLinecap="round" />
      </g>
      <g className="art-spin-rev" style={{ transformOrigin: "76px 80px" }} stroke="var(--accent)" strokeWidth="2.5" opacity="0.55">
        <circle cx="76" cy="80" r="11" fill="var(--card-bg)" />
        <path d="M76 64v7M76 89v7M60 80h7M85 80h7M65 69l5 5M82 86l5 5M87 69l-5 5M70 86l-5 5" strokeLinecap="round" />
      </g>
      {/* cloud */}
      <path className="art-float-b" d="M272 158c0-8 6-14 14-14 1.5 0 3 .3 4.4.9C293.4 141 300 136 308 136c10 0 18 8 18 18 0 .4 0 .8-.1 1.2H272v2.8z" fill="var(--accent-light)" opacity="0.8" />
    </svg>
  );
}

/* ---------- Auth: padlock + secure connection nodes ---------- */
export function AuthArt({ className }: ArtProps) {
  return (
    <svg viewBox="0 0 360 360" fill="none" className={className} aria-hidden="true" role="presentation">
      <g stroke="var(--accent)" strokeWidth="2" opacity="0.3" strokeDasharray="4 8">
        <path d="M180 176 74 84M180 176l106-92M180 176l-96 100M180 176l96 100" />
      </g>
      {[
        [74, 84],
        [286, 84],
        [84, 276],
        [276, 276],
      ].map(([cx, cy], i) => (
        <g key={i} className={`art-node art-node-${(i % 3) + 1}`}>
          <circle cx={cx} cy={cy} r="16" fill="var(--accent-light)" />
          <circle cx={cx} cy={cy} r="9" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="2.5" />
        </g>
      ))}
      {/* padlock */}
      <g className="art-pulse-soft">
        <rect x="138" y="158" width="84" height="72" rx="14" fill="var(--card-bg)" stroke="var(--accent)" strokeWidth="4" />
        <path d="M156 158v-16c0-16 10-28 24-28s24 12 24 28v16" stroke="var(--accent)" strokeWidth="4.5" fill="none" strokeLinecap="round" />
        <circle cx="180" cy="188" r="9" fill="var(--accent)" />
        <path d="M180 196v13" stroke="var(--accent)" strokeWidth="5" strokeLinecap="round" />
      </g>
      {/* key */}
      <g className="art-float-b" transform="translate(232 196) rotate(24)">
        <circle cx="0" cy="0" r="12" fill="none" stroke="var(--warning)" strokeWidth="4" />
        <path d="M10 8 30 28M24 22l6-2M28 26l6-2" stroke="var(--warning)" strokeWidth="4" strokeLinecap="round" />
      </g>
      <circle cx="180" cy="60" r="4.5" fill="var(--success)" className="art-pulse-dot" />
      <circle cx="52" cy="180" r="3.5" fill="var(--accent)" opacity="0.4" />
      <circle cx="308" cy="180" r="3.5" fill="var(--accent)" opacity="0.4" />
    </svg>
  );
}

/* ---------- Wave divider between alternating section backgrounds ---------- */
export function SectionDivider({ toAlt = true, flip = false }: { toAlt?: boolean; flip?: boolean }) {
  return (
    <div className={`section-divider${toAlt ? " to-alt" : " to-main"}${flip ? " flip" : ""}`} aria-hidden="true">
      <svg viewBox="0 0 1440 60" preserveAspectRatio="none">
        <path d="M0 20 C480 60 960 0 1440 40 L1440 60 L0 60 Z" />
      </svg>
    </div>
  );
}
