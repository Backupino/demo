import {
  ArrowUpRight,
  Banknote,
  BookOpen,
  Bug,
  CalendarClock,
  Globe,
  HardDriveDownload,
  Languages,
  ShieldCheck,
  Star,
  TimerOff,
  Trash2,
  Unlock,
  WifiOff,
} from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { PageHero, Reveal, SectionHead } from "../components/ui";
import { AboutArt, SectionDivider } from "../components/art";
import { GithubIcon } from "../components/icons";

const WHY_ICONS = [Banknote, TimerOff, Languages, HardDriveDownload, CalendarClock];
const VALUE_ICONS = [Unlock, WifiOff, Trash2, ShieldCheck];
const COMMUNITY_ICONS = [GithubIcon, BookOpen, Globe, Bug];
const COMMUNITY_URLS = [
  "https://github.com/Backupino/plugin",
  "https://github.com/Backupino/documents",
  "https://profiles.wordpress.org/backupino/",
  "https://github.com/Backupino/plugin/issues",
];

export default function About() {
  const { t, tr } = useApp();

  const whyItems = tr<{ title: string; desc: string }[]>("about.why.items", []);
  const values = tr<{ title: string; desc: string }[]>("about.values.items", []);
  const versions = tr<{ tag: string; title: string; desc: string }[]>("about.versioning.items", []);
  const cards = tr<{ title: string; desc: string; link: string }[]>("about.community.cards", []);

  return (
    <>
      <PageHero label={t("about.hero.label")} title={t("about.hero.title")} lead={t("about.hero.subtitle")} art={<AboutArt />} />

      {/* Mission */}
      <section className="section">
        <div className="container">
          <Reveal className="prose-block">
            <span className="section-label">{t("about.mission.label")}</span>
            <h2 className="section-title" style={{ marginBottom: "var(--space-3)" }}>
              {t("about.mission.title")}
            </h2>
            <p>{t("about.mission.p1")}</p>
            <blockquote>{t("about.mission.quote")}</blockquote>
            <p>{t("about.mission.p2")}</p>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* Why we built it */}
      <section className="section section-alt">
        <div className="container">
          <SectionHead label={t("about.why.label")} title={t("about.why.title")} subtitle={t("about.why.subtitle")} />
          <div className="why-grid">
            {whyItems.map((item, i) => {
              const Icon = WHY_ICONS[i % WHY_ICONS.length];
              return (
                <Reveal key={item.title} delay={(i % 2) * 100}>
                  <article className="why-card">
                    <span className="why-icon" aria-hidden="true">
                      <Icon size={21} />
                    </span>
                    <div>
                      <h3>{item.title}</h3>
                      <p>{item.desc}</p>
                    </div>
                  </article>
                </Reveal>
              );
            })}
          </div>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />
      {/* Core values */}
      <section className="section">
        <div className="container">
          <SectionHead label={t("about.values.label")} title={t("about.values.title")} />
          <div className="values-grid">
            {values.map((value, i) => {
              const Icon = VALUE_ICONS[i % VALUE_ICONS.length];
              return (
                <Reveal key={value.title} delay={i * 80}>
                  <article className="value-card">
                    <span className="feature-icon" aria-hidden="true">
                      <Icon size={20} />
                    </span>
                    <h3>{value.title}</h3>
                    <p>{value.desc}</p>
                  </article>
                </Reveal>
              );
            })}
          </div>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* Versioning discipline */}
      <section className="section section-alt">
        <div className="container">
          <SectionHead label={t("about.versioning.label")} title={t("about.versioning.title")} subtitle={t("about.versioning.subtitle")} />
          <div className="container-narrow version-list" style={{ marginInline: "auto" }}>
            {versions.map((v, i) => (
              <Reveal key={v.tag} delay={i * 90}>
                <div className="version-card">
                  <span className="version-chip">{v.tag}</span>
                  <div>
                    <h3>{v.title}</h3>
                    <p>{v.desc}</p>
                  </div>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />
      {/* Open source CTA */}
      <section className="section" style={{ paddingBottom: 0 }}>
        <div className="container">
          <Reveal>
            <div className="cta-banner">
              <h2>{t("about.opensource.title")}</h2>
              <p>{t("about.opensource.desc")}</p>
              <div className="btn-row">
                <a href="https://github.com/Backupino/plugin" target="_blank" rel="noreferrer noopener" className="btn btn-white btn-lg">
                  <Star size={17} aria-hidden="true" />
                  {t("about.opensource.star")}
                </a>
                <a href="https://github.com/Backupino/plugin/issues" target="_blank" rel="noreferrer noopener" className="btn btn-glass btn-lg">
                  <Bug size={17} aria-hidden="true" />
                  {t("about.opensource.issue")}
                </a>
              </div>
            </div>
          </Reveal>
        </div>
      </section>

      {/* Community */}
      <section className="section">
        <div className="container">
          <SectionHead label={t("about.community.label")} title={t("about.community.title")} subtitle={t("about.community.subtitle")} />
          <div className="community-grid">
            {cards.map((card, i) => {
              const Icon = COMMUNITY_ICONS[i % COMMUNITY_ICONS.length];
              return (
                <Reveal key={card.title} delay={i * 80}>
                  <article className="community-card">
                    <span className="feature-icon" style={{ marginBottom: 0 }} aria-hidden="true">
                      <Icon size={20} />
                    </span>
                    <h3>{card.title}</h3>
                    <p>{card.desc}</p>
                    <a className="card-link" href={COMMUNITY_URLS[i]} target="_blank" rel="noreferrer noopener">
                      {card.link}
                      <ArrowUpRight size={14} aria-hidden="true" />
                    </a>
                  </article>
                </Reveal>
              );
            })}
          </div>

          <Reveal delay={150}>
            <div style={{ textAlign: "center", marginTop: "var(--space-6)" }}>
              <a href={href("docs")} className="btn btn-outline btn-lg">
                <BookOpen size={17} aria-hidden="true" />
                {t("footer.links.docs")}
              </a>
            </div>
          </Reveal>
        </div>
      </section>
    </>
  );
}
