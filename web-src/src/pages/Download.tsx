import { useState } from "react";
import {
  BookOpen,
  Check,
  Copy,
  Cpu,
  Download as DownloadIcon,
  ExternalLink,
  FileArchive,
  FileCode2,
  HardDrive,
  Layers,
  Server,
  ShieldCheck,
  Sparkles,
  Terminal,
} from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { Faq, PageHero, Reveal, SectionHead, type FaqItem } from "../components/ui";
import { DownloadArt, SectionDivider } from "../components/art";

const WP_ORG_DOWNLOAD = "https://downloads.wordpress.org/plugin/backupino.zip";
const GITHUB_DOWNLOAD_MIRROR = "https://github.com/Backupino/plugin/releases/latest/download/backupino.zip";
const GITHUB_RELEASE_PAGE = "https://github.com/Backupino/plugin/releases/tag/v1.0.3";

export default function Download() {
  const { t, tr } = useApp();
  const [copiedZip, setCopiedZip] = useState(false);
  const [copiedCmd, setCopiedCmd] = useState(false);

  const methods = tr<{ step: string; title: string; desc: string; cmd?: string }[]>(
    "download.methods.items",
    []
  );

  const requirements = tr<{ name: string; value: string }[]>(
    "download.requirements.items",
    []
  );

  const pluginFeatures = tr<string[]>("download.packages.plugin.features", []);
  const installerFeatures = tr<string[]>("download.packages.installer.features", []);
  const stats = tr<{ value: string; label: string }[]>("download.hero.stats", []);
  const faqItems = tr<FaqItem[]>("download.faq.items", []);

  const copyText = (text: string, setter: (v: boolean) => void) => {
    navigator.clipboard?.writeText(text);
    setter(true);
    setTimeout(() => setter(false), 2000);
  };

  return (
    <>
      {/* ============================= HERO ============================= */}
      <PageHero
        label={t("download.hero.badge")}
        title={
          <>
            {t("download.hero.titleA")}{" "}
            <span className="text-gradient">{t("download.hero.titleB")}</span>
          </>
        }
        lead={t("download.hero.subtitle")}
        art={<DownloadArt />}
      />

      {/* ======================= PACKAGES SHOWCASE ======================= */}
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <SectionHead
            label={t("download.hero.badge")}
            title={t("download.packages.plugin.title")}
            subtitle={t("download.hero.subtitle")}
          />

          <div className="download-cards-grid">
            {/* 1. Official Core Plugin Package */}
            <Reveal delay={80}>
              <div className="download-card primary">
                <span className="download-card-badge">
                  <Sparkles size={12} />
                  {t("download.packages.plugin.badge")}
                </span>

                <div className="download-card-head">
                  <div className="download-icon-box">
                    <FileArchive size={28} />
                  </div>
                  <div>
                    <h3 className="download-card-title">{t("download.packages.plugin.title")}</h3>
                    <div className="download-card-meta">
                      <span className="download-meta-chip">{t("download.packages.plugin.version")}</span>
                      <span className="download-meta-chip">{t("download.packages.plugin.size")}</span>
                      <span className="download-meta-chip">{t("download.packages.plugin.format")}</span>
                    </div>
                  </div>
                </div>

                <p className="download-card-desc">{t("download.packages.plugin.desc")}</p>

                <ul className="plan-features">
                  {pluginFeatures.map((feat) => (
                    <li key={feat}>
                      <Check size={16} className="check-icon" aria-hidden="true" />
                      <span>{feat}</span>
                    </li>
                  ))}
                </ul>

                {/* Primary Direct Download from WordPress.org */}
                <a
                  href={WP_ORG_DOWNLOAD}
                  className="btn btn-primary btn-lg"
                  style={{ width: "100%", justifyContent: "center", marginBottom: 12 }}
                  download
                >
                  <DownloadIcon size={18} />
                  {t("download.packages.plugin.primaryCta")}
                </a>

                {/* Secondary Mirror Download from GitHub */}
                <a
                  href={GITHUB_DOWNLOAD_MIRROR}
                  className="btn btn-outline btn-sm"
                  style={{ width: "100%", justifyContent: "center", marginBottom: 14 }}
                  download
                >
                  <DownloadIcon size={14} />
                  {t("download.packages.plugin.mirrorCta")}
                </a>

                {/* Checksum SHA-256 copy box */}
                <div className="download-hash-box">
                  <code>sha256: {t("download.packages.plugin.checksum")}</code>
                  <button
                    type="button"
                    className="icon-btn"
                    style={{ width: 28, height: 28, flexShrink: 0 }}
                    onClick={() => copyText(t("download.packages.plugin.checksum"), setCopiedZip)}
                    title={t("download.checksums.copy")}
                  >
                    {copiedZip ? <Check size={14} color="var(--success)" /> : <Copy size={14} />}
                  </button>
                </div>
              </div>
            </Reveal>

            {/* 2. Standalone Recovery Installer (Bundled Explanation) */}
            <Reveal delay={160}>
              <div className="download-card">
                <span className="download-card-badge">
                  {t("download.packages.installer.badge")}
                </span>

                <div className="download-card-head">
                  <div className="download-icon-box" style={{ background: "rgba(16, 185, 129, 0.14)", color: "var(--success)" }}>
                    <FileCode2 size={28} />
                  </div>
                  <div>
                    <h3 className="download-card-title">{t("download.packages.installer.title")}</h3>
                    <div className="download-card-meta">
                      <span className="download-meta-chip">{t("download.packages.installer.version")}</span>
                      <span className="download-meta-chip">{t("download.packages.installer.size")}</span>
                      <span className="download-meta-chip">{t("download.packages.installer.format")}</span>
                    </div>
                  </div>
                </div>

                <p className="download-card-desc">{t("download.packages.installer.desc")}</p>

                <ul className="plan-features">
                  {installerFeatures.map((feat) => (
                    <li key={feat}>
                      <Check size={16} className="check-icon" aria-hidden="true" />
                      <span>{feat}</span>
                    </li>
                  ))}
                </ul>

                <a
                  href={href("docs")}
                  className="btn btn-outline btn-lg"
                  style={{ width: "100%", justifyContent: "center" }}
                >
                  <BookOpen size={18} />
                  {t("download.packages.installer.learnMoreCta")}
                </a>

                <div
                  style={{
                    background: "var(--bg-secondary)",
                    padding: "10px 14px",
                    borderRadius: "var(--radius-button)",
                    fontSize: "0.8125rem",
                    color: "var(--text-secondary)",
                    marginTop: 14,
                    lineHeight: 1.6,
                    border: "1px solid var(--border)",
                  }}
                >
                  <span style={{ fontWeight: 600, color: "var(--text-primary)" }}>نکته مهم:</span> فایل installer.php بدون نیاز به دانلود مستقل، همواره در پکیج‌های فشرده بکاپ شما قرار می‌گیرد و از پیشخوان وردپرس با یک کلیک قابل خروجی گرفتن است.
                </div>
              </div>
            </Reveal>
          </div>

          {/* Release & Trust Stats Strip */}
          <Reveal delay={240}>
            <div className="download-stats-strip">
              {stats.map((st) => (
                <div key={st.label} className="download-stat-item">
                  <b>{st.value}</b>
                  <span>{st.label}</span>
                </div>
              ))}
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />

      {/* ===================== 3 INSTALLATION METHODS ===================== */}
      <section className="section section-alt" id="installation">
        <div className="container">
          <SectionHead
            label={t("download.methods.label")}
            title={t("download.methods.title")}
            subtitle={t("download.methods.subtitle")}
          />

          <div className="steps">
            {methods.map((method, i) => (
              <Reveal key={method.step} delay={i * 100}>
                <div className="step-card">
                  <div className="step-num">{method.step}</div>
                  <div className="step-content">
                    <h3 style={{ fontSize: "1.1rem", fontWeight: 700, margin: "0 0 8px 0" }}>{method.title}</h3>
                    <p style={{ margin: 0, color: "var(--text-secondary)" }}>{method.desc}</p>
                    {method.cmd && (
                      <div className="terminal-window">
                        <div className="terminal-header">
                          <span className="terminal-dots">
                            <span />
                            <span />
                            <span />
                          </span>
                          <span className="terminal-title">bash — wp-cli</span>
                        </div>
                        <div className="terminal-body">
                          <div className="terminal-line">
                            <div>
                              <span className="terminal-prompt">$</span>
                              <span className="terminal-cmd">{method.cmd}</span>
                            </div>
                            <button
                              type="button"
                              className="icon-btn"
                              style={{ width: 26, height: 26, color: "#94a3b8" }}
                              onClick={() => copyText(method.cmd || "", setCopiedCmd)}
                              title="Copy command"
                            >
                              {copiedCmd ? <Check size={14} color="var(--success)" /> : <Copy size={14} />}
                            </button>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />

      {/* ===================== SYSTEM REQUIREMENTS ===================== */}
      <section className="section" id="requirements">
        <div className="container">
          <SectionHead
            label={t("download.requirements.label")}
            title={t("download.requirements.title")}
            subtitle={t("download.requirements.subtitle")}
          />

          <div className="features-grid">
            {requirements.map((req, i) => (
              <Reveal key={req.name} delay={(i % 3) * 80}>
                <div className="feature-card" style={{ display: "flex", gap: 16 }}>
                  <span className="feature-icon" aria-hidden="true" style={{ marginTop: 2 }}>
                    {i === 0 && <Cpu size={22} />}
                    {i === 1 && <HardDrive size={22} />}
                    {i === 2 && <Layers size={22} />}
                    {i === 3 && <HardDrive size={22} />}
                    {i === 4 && <ShieldCheck size={22} />}
                    {i === 5 && <Server size={22} />}
                  </span>
                  <div>
                    <h3 style={{ margin: "0 0 4px 0", fontSize: "1.05rem" }}>{req.name}</h3>
                    <p style={{ margin: 0, color: "var(--text-secondary)", fontSize: "0.9375rem" }}>{req.value}</p>
                  </div>
                </div>
              </Reveal>
            ))}
          </div>

          <Reveal delay={200}>
            <div
              style={{
                marginTop: 36,
                padding: "16px 24px",
                background: "var(--bg-secondary)",
                borderRadius: "var(--radius-card)",
                border: "1px solid var(--border)",
                display: "flex",
                flexWrap: "wrap",
                alignItems: "center",
                justifyContent: "space-between",
                gap: 16,
              }}
            >
              <div style={{ display: "flex", flexWrap: "wrap", alignItems: "center", gap: 14, fontSize: "0.875rem", color: "var(--text-secondary)" }}>
                <span>{t("download.hero.latestRelease")}</span>
                <span>•</span>
                <span>{t("download.hero.releaseDate")}</span>
                <span>•</span>
                <span>{t("download.hero.license")}</span>
                <span>•</span>
                <span>{t("download.hero.testedUpTo")}</span>
              </div>
              <a
                href={GITHUB_RELEASE_PAGE}
                target="_blank"
                rel="noreferrer noopener"
                className="btn btn-outline btn-sm"
              >
                Release Notes <ExternalLink size={14} />
              </a>
            </div>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />

      {/* ===================== FAQ ACCORDION ===================== */}
      <section className="section section-alt" id="faq">
        <div className="container" style={{ maxWidth: 900 }}>
          <SectionHead
            label={t("download.faq.label")}
            title={t("download.faq.title")}
            subtitle={t("download.faq.subtitle")}
          />
          <Reveal>
            <Faq items={faqItems} />
          </Reveal>
        </div>
      </section>
    </>
  );
}
