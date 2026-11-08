import { useEffect, useState } from "react";
import {
  ArrowRight,
  BadgeCheck,
  BellRing,
  Check,
  Code2,
  Database,
  DatabaseBackup,
  EyeOff,
  Globe,
  LayoutDashboard,
  Link2,
  MessageSquare,
  MonitorSmartphone,
  PackageCheck,
  PanelTop,
  Send,
  Server,
  Network,
  ShoppingCart,
  ShieldCheck,
  SquareTerminal,
  TimerReset,
  WifiOff,
} from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { Faq, Reveal, SectionHead, type FaqItem } from "../components/ui";
import { HomeArt, SectionDivider } from "../components/art";
import { GithubIcon } from "../components/icons";

/* --------------------------------------------------------------------------
   Animated pipeline preview — mimics the real 4-stage backup dashboard
   -------------------------------------------------------------------------- */
function PipelineDemo() {
  const { tr } = useApp();
  const stages = tr<string[]>("home.visual.stages", ["Scan", "Database", "Files", "Finalize"]);

  const [progress, setProgress] = useState(0); // 0..400 across 4 stages

  useEffect(() => {
    let paused = false;
    const id = window.setInterval(() => {
      if (paused) return;
      setProgress((p) => {
        const next = p + 1.4 + Math.random() * 2.4;
        if (next >= 400) {
          paused = true;
          window.setTimeout(() => {
            setProgress(0);
            paused = false;
          }, 1600);
          return 400;
        }
        return next;
      });
    }, 50);
    return () => window.clearInterval(id);
  }, []);

  const files = Math.round((progress / 400) * 8421);
  const rows = Math.round((progress / 400) * 28436);
  const size = ((progress / 400) * 186.4).toFixed(1);

  return (
    <div className="backup-card" role="img" aria-label="Backupino live backup pipeline preview">
      <div className="backup-card-head">
        <span className="traffic-dots" aria-hidden="true">
          <span />
          <span />
          <span />
        </span>
        <span className="backup-card-title">{tr("home.visual.windowTitle", "wp-admin → Backupino")}</span>
      </div>
      <div className="backup-card-body">
        {stages.map((stage, i) => {
          const start = i * 100;
          const local = Math.min(100, Math.max(0, progress - start));
          const done = local >= 100;
          return (
            <div key={stage}>
              <div className="pipeline-stage-row">
                <span className="pipeline-stage-name">
                  {done ? <Check size={15} aria-hidden="true" /> : <TimerReset size={15} aria-hidden="true" />}
                  {stage}
                </span>
                <span className="pipeline-stage-pct">{Math.round(local)}%</span>
              </div>
              <div className="pipeline-track" role="progressbar" aria-valuenow={Math.round(local)} aria-valuemin={0} aria-valuemax={100} aria-label={stage}>
                <div className={`pipeline-fill${done ? " done" : ""}`} style={{ width: `${local}%` }} />
              </div>
            </div>
          );
        })}
        <div className="backup-card-stats">
          <div className="mini-stat">
            <b>{files.toLocaleString("en-US")}</b>
            <span>{tr("home.visual.statFiles", "files archived")}</span>
          </div>
          <div className="mini-stat">
            <b>{rows.toLocaleString("en-US")}</b>
            <span>{tr("home.visual.statRows", "rows streamed")}</span>
          </div>
          <div className="mini-stat">
            <b>{size} MB</b>
            <span>{tr("home.visual.statSize", "package size")}</span>
          </div>
        </div>
      </div>
    </div>
  );
}

/* -------------------------------------------------------------------------- */

const FEATURE_ICONS = [TimerReset, PackageCheck, DatabaseBackup, Link2, BellRing, WifiOff];

const PLATFORM_ICONS = [
  Globe,
  Code2,
  Database,
  ShoppingCart,
  Send,
  MessageSquare,
  SquareTerminal,
  Server,
  Network,
  MonitorSmartphone,
  PanelTop,
  LayoutDashboard,
];

export default function Home() {
  const { t, tr } = useApp();

  const features = tr<{ title: string; desc: string }[]>("home.features.items", []);
  const steps = tr<{ title: string; desc: string }[]>("home.how.steps", []);
  const platforms = tr<string[]>("home.platforms.items", []);
  const stats = tr<{ value: string; label: string }[]>("home.trust.stats", []);
  const badges = tr<string[]>("home.trust.badges", []);
  const faqItems = tr<FaqItem[]>("home.faq.items", []);

  return (
    <>
      {/* ============================= HERO ============================= */}
      <section className="hero">
        <div className="hero-bg" aria-hidden="true">
          <span className="hero-glow hero-glow-1" />
          <span className="hero-glow hero-glow-2" />
          <span className="hero-glow hero-glow-3" />
        </div>
        <div className="container hero-inner">
          <div className="hero-copy">
            <Reveal>
              <span className="hero-badge">
                <span className="pulse-dot" aria-hidden="true" />
                {t("home.hero.badge")}
              </span>
            </Reveal>
            <Reveal delay={80}>
              <h1 className="hero-title">
                {t("home.hero.titleA")}{" "}
                <span className="text-gradient">{t("home.hero.titleB")}</span>{" "}
                {t("home.hero.titleC")}
              </h1>
            </Reveal>
            <Reveal delay={160}>
              <p className="hero-subtitle">{t("home.hero.subtitle")}</p>
            </Reveal>
            <Reveal delay={240}>
              <div className="hero-ctas">
                <a href={href("register")} className="btn btn-primary btn-lg">
                  {t("home.hero.ctaPrimary")}
                  <ArrowRight size={17} className="flip-rtl" aria-hidden="true" />
                </a>
                <a href={href("docs")} className="btn btn-outline btn-lg">
                  {t("home.hero.ctaSecondary")}
                </a>
              </div>
            </Reveal>
            <Reveal delay={320}>
              <div className="hero-note">
                {[t("home.hero.note1"), t("home.hero.note2"), t("home.hero.note3")].map((note) => (
                  <span key={note}>
                    <Check size={14} aria-hidden="true" />
                    {note}
                  </span>
                ))}
              </div>
            </Reveal>
          </div>

          <Reveal delay={200} className="hero-visual">
            <div className="hero-art-deco">
              <HomeArt />
            </div>
            <PipelineDemo />
            <div className="float-chip float-chip-1" aria-hidden="true">
              <span className="float-chip-icon">
                <Send size={16} />
              </span>
              <span>
                {t("home.visual.chip1Title")}
                <small>{t("home.visual.chip1Sub")}</small>
              </span>
            </div>
            <div className="float-chip float-chip-2" aria-hidden="true">
              <span className="float-chip-icon">
                <PackageCheck size={16} />
              </span>
              <span>
                {t("home.visual.chip2Title")}
                <small>{t("home.visual.chip2Sub")}</small>
              </span>
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* =========================== FEATURES =========================== */}
      <section className="section section-alt" id="features">
        <div className="container">
          <SectionHead label={t("home.features.label")} title={t("home.features.title")} subtitle={t("home.features.subtitle")} />
          <div className="features-grid">
            {features.map((feature, i) => {
              const Icon = FEATURE_ICONS[i % FEATURE_ICONS.length];
              return (
                <Reveal key={feature.title} delay={(i % 3) * 90}>
                  <article className="feature-card">
                    <span className="feature-icon" aria-hidden="true">
                      <Icon size={22} />
                    </span>
                    <h3>{feature.title}</h3>
                    <p>{feature.desc}</p>
                  </article>
                </Reveal>
              );
            })}
          </div>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />
      {/* ========================== HOW IT WORKS ========================= */}
      <section className="section" id="how">
        <div className="container">
          <SectionHead label={t("home.how.label")} title={t("home.how.title")} subtitle={t("home.how.subtitle")} />
          <div className="steps">
            {steps.map((step, i) => (
              <Reveal key={step.title} delay={i * 120}>
                <div className="step">
                  <span className="step-num" aria-hidden="true">
                    {i + 1}
                  </span>
                  <h3>{step.title}</h3>
                  <p>{step.desc}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* =========================== PLATFORMS =========================== */}
      <section className="section section-alt" id="platforms">
        <div className="container">
          <SectionHead label={t("home.platforms.label")} title={t("home.platforms.title")} subtitle={t("home.platforms.subtitle")} />
          <div className="platforms-grid">
            {platforms.map((name, i) => {
              const Icon = PLATFORM_ICONS[i % PLATFORM_ICONS.length];
              return (
                <Reveal key={name} delay={(i % 4) * 60}>
                  <div className="platform-item">
                    <Icon size={19} aria-hidden="true" />
                    <span>{name}</span>
                  </div>
                </Reveal>
              );
            })}
          </div>
        </div>
      </section>

      {/* ============================= TRUST ============================= */}
      <section className="section" id="trust">
        <div className="container">
          <SectionHead label={t("home.trust.label")} title={t("home.trust.title")} />
          <div className="stats-grid">
            {stats.map((stat, i) => (
              <Reveal key={stat.label} delay={i * 80} variant="scale">
                <div className="stat-card">
                  <div className="stat-value">{stat.value}</div>
                  <div className="stat-label">{stat.label}</div>
                </div>
              </Reveal>
            ))}
          </div>
          <Reveal delay={200}>
            <div className="trust-badges">
              {[ShieldCheck, BadgeCheck, EyeOff, WifiOff, BadgeCheck].map((Icon, i) => (
                <span className="badge" key={badges[i] ?? i}>
                  <Icon size={14} aria-hidden="true" />
                  {badges[i]}
                </span>
              ))}
            </div>
          </Reveal>
        </div>
      </section>

      {/* =========================== CTA BANNER ========================== */}
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <Reveal>
            <div className="cta-banner">
              <h2>{t("home.cta.title")}</h2>
              <p>{t("home.cta.subtitle")}</p>
              <div className="btn-row">
                <a href={href("register")} className="btn btn-white btn-lg">
                  {t("home.cta.button")}
                  <ArrowRight size={17} className="flip-rtl" aria-hidden="true" />
                </a>
                <a
                  href="https://github.com/Backupino/plugin"
                  target="_blank"
                  rel="noreferrer noopener"
                  className="btn btn-glass btn-lg"
                >
                  <GithubIcon size={17} />
                  {t("home.cta.github")}
                </a>
              </div>
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt flip />
      {/* ============================== FAQ ============================= */}
      <section className="section section-alt" id="faq" style={{ paddingBottom: "var(--space-12)" }}>
        <div className="container">
          <SectionHead label={t("home.faq.label")} title={t("home.faq.title")} subtitle={t("home.faq.subtitle")} />
          <Faq items={faqItems} />
        </div>
      </section>
    </>
  );
}
