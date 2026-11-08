import {
  ArrowRight,
  Bot,
  CalendarClock,
  Check,
  CloudCog,
  CloudUpload,
  GitBranch,
  HardDriveUpload,
  History,
  KeyRound,
  Layers,
  LayoutDashboard,
  LifeBuoy,
  LockKeyhole,
  Minus,
  MoveRight,
  Network,
  Paintbrush,
  RefreshCw,
  ShieldCheck,
  Sparkles,
  Users,
} from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { Faq, PageHero, Reveal, SectionHead, useRowStagger, type FaqItem } from "../components/ui";
import { ProArt, SectionDivider } from "../components/art";

/* -------------------------------------------------------------------------- */

interface ProCard {
  title: string;
  desc: string;
  points: string[];
}

const CARD_ICONS = [
  CloudUpload, // 1 Cloud Storage
  HardDriveUpload, // 2 Download Host
  CalendarClock, // 3 Advanced Scheduling
  GitBranch, // 4 Incremental
  History, // 5 Point-in-Time
  LockKeyhole, // 7 Encryption
  Network, // 8 Multisite
  Paintbrush, // 9 White Label
  Users, // 10 Team Management
  LayoutDashboard, // 11 Centralized Panel
  Bot, // 12 Central Bot
  CloudCog, // 13 Dedicated Cloud
  KeyRound, // 14 Key Recovery
  LifeBuoy, // 15 Recovery Agent
  RefreshCw, // 16 Updates & Licensing
];

type Mark = "yes" | "no" | "mid";
type ProRow = { f: string; free: [Mark, string]; pro: [Mark, string] };

function TableMark({ mark, text }: { mark: Mark; text: string }) {
  if (mark === "yes") {
    return (
      <span className="cell-mark">
        <Check size={16} className="m-yes" aria-hidden="true" />
        {text && <span>{text}</span>}
      </span>
    );
  }
  return (
    <span className="cell-mark cell-empty">
      <Minus size={16} className="m-no" aria-hidden="true" />
      {text && <span>{text}</span>}
    </span>
  );
}

export default function Pro() {
  const { t, tr } = useApp();

  const cards = tr<ProCard[]>("pro.cards", []);
  const freePoints = tr<string[]>("pro.relation.freePoints", []);
  const proPoints = tr<string[]>("pro.relation.proPoints", []);
  const steps = tr<{ title: string; desc: string }[]>("pro.spotlight.steps", []);
  const licenseItems = tr<{ title: string; desc: string }[]>("pro.licensing.items", []);
  const faqItems = tr<FaqItem[]>("pro.faq.items", []);
  const compareRows = tr<ProRow[]>("pricing.proCompare.rows", []);

  const compareTableRef = useRowStagger<HTMLDivElement>(compareRows);

  const firstRow = cards.slice(0, 5);
  const secondRow = cards.slice(5);

  return (
    <>
      {/* ============================= HERO ============================= */}
      <PageHero
        label={t("pro.hero.label")}
        title={<span className="pro-hero-title">{t("pro.hero.title")}</span>}
        lead={t("pro.hero.subtitle")}
        art={<ProArt />}
      />

      <section style={{ paddingBottom: "var(--space-6)" }}>
        <div className="container">
          <Reveal delay={100}>
            <div className="btn-row" style={{ display: "flex", justifyContent: "center", gap: 14, flexWrap: "wrap" }}>
              <a href={href("pricing")} className="btn btn-primary btn-lg">
                {t("pro.hero.ctaPrimary")}
                <ArrowRight size={17} className="flip-rtl" aria-hidden="true" />
              </a>
              <a href={href("docs")} className="btn btn-outline btn-lg">
                {t("pro.hero.ctaSecondary")}
              </a>
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* ===================== RELATIONSHIP FREE ↔ PRO ===================== */}
      <section className="section section-alt" id="relation">
        <div className="container">
          <SectionHead label={t("pro.relation.label")} title={t("pro.relation.title")} subtitle={t("pro.relation.subtitle")} />
          <Reveal>
            <div className="relation">
              <div className="relation-card">
                <div className="relation-card-head">
                  <span className="feature-icon" aria-hidden="true">
                    <Sparkles size={20} />
                  </span>
                  <div>
                    <h3>{t("pro.relation.freeTitle")}</h3>
                    <span className="relation-sub">{t("pro.relation.freeSub")}</span>
                  </div>
                </div>
                <ul>
                  {freePoints.map((point) => (
                    <li key={point}>
                      <Check size={15} aria-hidden="true" />
                      <span>{point}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <div className="relation-arrow" aria-hidden="true">
                <span className="relation-plus">+</span>
                <MoveRight size={34} className="flip-rtl-target" />
              </div>

              <div className="relation-card relation-card-pro">
                <div className="relation-card-head">
                  <span className="feature-icon feature-icon-pro" aria-hidden="true">
                    <Layers size={20} />
                  </span>
                  <div>
                    <h3>
                      {t("pro.relation.proTitle")} <span className="nav-pro-badge">{t("nav.proBadge")}</span>
                    </h3>
                    <span className="relation-sub">{t("pro.relation.proSub")}</span>
                  </div>
                </div>
                <ul>
                  {proPoints.map((point) => (
                    <li key={point}>
                      <Check size={15} aria-hidden="true" />
                      <span>{point}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </Reveal>
          <Reveal delay={120}>
            <p className="relation-tagline">{t("pro.relation.tagline")}</p>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />
      {/* ========================= PRO FEATURE CARDS ======================= */}
      <section className="section" id="pro-features">
        <div className="container">
          <SectionHead label={t("pro.features.label")} title={t("pro.features.title")} subtitle={t("pro.features.subtitle")} />

          <div className="features-grid">
            {firstRow.map((card, i) => {
              const Icon = CARD_ICONS[i % CARD_ICONS.length];
              return (
                <Reveal key={card.title} delay={(i % 3) * 80}>
                  <article className="feature-card pro-feature-card">
                    <span className="feature-icon" aria-hidden="true">
                      <Icon size={22} />
                    </span>
                    <h3>{card.title}</h3>
                    <p>{card.desc}</p>
                    <ul className="pro-points">
                      {card.points.map((point) => (
                        <li key={point}>
                          <Check size={13} aria-hidden="true" />
                          <span>{point}</span>
                        </li>
                      ))}
                    </ul>
                  </article>
                </Reveal>
              );
            })}
          </div>

          {/* Card 6 — Secure In-Place Restore spotlight */}
          <Reveal>
            <article className="feature-card pro-spotlight">
              <div className="pro-spotlight-head">
                <span className="feature-icon" aria-hidden="true">
                  <ShieldCheck size={24} />
                </span>
                <div>
                  <h3>{t("pro.spotlight.title")}</h3>
                  <p>{t("pro.spotlight.desc")}</p>
                </div>
              </div>
              <div className="restore-steps">
                {steps.map((step, i) => (
                  <div className="restore-step" key={step.title}>
                    <span className="rs-num" aria-hidden="true">
                      {i + 1}
                    </span>
                    <div>
                      <h4>{step.title}</h4>
                      <p>{step.desc}</p>
                    </div>
                  </div>
                ))}
              </div>
            </article>
          </Reveal>

          <div className="features-grid" style={{ marginTop: "var(--space-3)" }}>
            {secondRow.map((card, i) => {
              const Icon = CARD_ICONS[(i + 5) % CARD_ICONS.length];
              return (
                <Reveal key={card.title} delay={(i % 3) * 80}>
                  <article className="feature-card pro-feature-card">
                    <span className="feature-icon" aria-hidden="true">
                      <Icon size={22} />
                    </span>
                    <h3>{card.title}</h3>
                    <p>{card.desc}</p>
                    <ul className="pro-points">
                      {card.points.map((point) => (
                        <li key={point}>
                          <Check size={13} aria-hidden="true" />
                          <span>{point}</span>
                        </li>
                      ))}
                    </ul>
                  </article>
                </Reveal>
              );
            })}
          </div>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* ====================== FREE VS PRO COMPARISON ===================== */}
      <section className="section section-alt" id="free-vs-pro">
        <div className="container">
          <SectionHead label={t("pro.compare.label")} title={t("pro.compare.title")} subtitle={t("pro.compare.subtitle")} />
          <Reveal>
            <div className="table-wrap" ref={compareTableRef}>
              <table className="compare-table">
                <thead>
                  <tr>
                    {tr<string[]>("pro.compare.columns", []).map((col, i) => (
                      <th key={col} scope="col" className={i === 2 ? "col-brand" : undefined}>
                        {col}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {compareRows.map((row) => (
                    <tr key={row.f} className={row.free[0] === "no" ? "row-pro" : undefined}>
                      <td>{row.f}</td>
                      <td>
                        <TableMark mark={row.free[0]} text={row.free[1]} />
                      </td>
                      <td className="col-brand">
                        <TableMark mark={row.pro[0]} text={row.pro[1]} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />
      {/* ========================= LICENSING RULES ========================= */}
      <section className="section" id="licensing">
        <div className="container">
          <SectionHead label={t("pro.licensing.label")} title={t("pro.licensing.title")} subtitle={t("pro.licensing.subtitle")} />
          <div className="license-grid">
            {licenseItems.map((item, i) => (
              <Reveal key={item.title} delay={(i % 2) * 80}>
                <div className="license-item">
                  <span className="li-num" aria-hidden="true">
                    {i + 1}
                  </span>
                  <div>
                    <h3>{item.title}</h3>
                    <p>{item.desc}</p>
                  </div>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* ============================== CTA =============================== */}
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <Reveal>
            <div className="cta-banner">
              <h2>{t("pro.cta.title")}</h2>
              <p>{t("pro.cta.subtitle")}</p>
              <div className="btn-row">
                <a href={href("pricing")} className="btn btn-white btn-lg">
                  {t("pro.cta.button")}
                  <ArrowRight size={17} className="flip-rtl" aria-hidden="true" />
                </a>
              </div>
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt flip />
      {/* ============================== FAQ =============================== */}
      <section className="section section-alt" id="pro-faq">
        <div className="container">
          <SectionHead label={t("pro.faq.label")} title={t("pro.faq.title")} />
          <Faq items={faqItems} />
        </div>
      </section>
    </>
  );
}
