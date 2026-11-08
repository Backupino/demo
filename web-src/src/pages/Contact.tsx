import { useState, type FormEvent } from "react";
import {
  AlertTriangle,
  ArrowRight,
  CheckCircle2,
  Clock,
  ExternalLink,
  Globe,
  Mail,
  MessageSquare,
  Send,
  ShieldAlert,
  Sparkles,
  Zap,
} from "lucide-react";
import { useApp } from "../lib/AppContext";
import { Faq, PageHero, Reveal, SectionHead, type FaqItem } from "../components/ui";
import { ContactArt, SectionDivider } from "../components/art";
import { GithubIcon } from "../components/icons";

export default function Contact() {
  const { t, tr } = useApp();
  const [submitted, setSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    category: "tech",
    priority: "normal",
    siteUrl: "",
    message: "",
  });

  const channels = tr<
    { icon: string; title: string; desc: string; action: string; link: string; badge?: string }[]
  >("contact.channels.items", []);

  const categoryOptions = tr<{ id: string; label: string }[]>(
    "contact.form.categoryOptions",
    []
  );

  const slaItems = tr<{ title: string; desc: string }[]>("contact.sla.items", []);
  const faqItems = tr<FaqItem[]>("contact.faq.items", []);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      setSubmitted(true);
    }, 700);
  };

  const getChannelIcon = (iconName: string) => {
    switch (iconName) {
      case "telegram":
        return <Send size={22} />;
      case "github":
        return <GithubIcon size={22} />;
      case "mail":
        return <Mail size={22} />;
      case "forum":
        return <Globe size={22} />;
      default:
        return <MessageSquare size={22} />;
    }
  };

  return (
    <>
      {/* ============================= HERO ============================= */}
      <PageHero
        label={t("contact.hero.badge")}
        title={t("contact.hero.title")}
        lead={t("contact.hero.subtitle")}
        art={<ContactArt />}
      />

      {/* ===================== TWO-COLUMN LAYOUT ===================== */}
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <SectionHead
            label={t("contact.channels.label")}
            title={t("contact.channels.title")}
          />

          <div className="contact-layout">
            {/* Left Column: Direct Channels & Emergency Alert */}
            <div className="contact-sidebar">
              {/* Emergency Disaster Recovery Callout */}
              <Reveal delay={60}>
                <div className="emergency-card">
                  <div className="emergency-head">
                    <span className="emergency-pulse-dot" />
                    <span className="emergency-tag">{t("contact.emergency.badge")}</span>
                  </div>
                  <h3>{t("contact.emergency.title")}</h3>
                  <p>{t("contact.emergency.desc")}</p>
                  <a
                    href="https://t.me/Backupino_Bot"
                    target="_blank"
                    rel="noreferrer noopener"
                    className="btn btn-primary btn-sm"
                    style={{ background: "#dc2626", borderColor: "#b91c1c" }}
                  >
                    <Zap size={14} />
                    {t("contact.emergency.action")}
                  </a>
                </div>
              </Reveal>

              {/* Fast Direct Communication Channels */}
              <div className="contact-channels-list">
                {channels.map((ch, i) => (
                  <Reveal key={ch.title} delay={100 + i * 60}>
                    <a
                      href={ch.link}
                      target="_blank"
                      rel="noreferrer noopener"
                      className={`channel-card-item ${ch.icon === "telegram" ? "tg" : ch.icon === "github" ? "gh" : ch.icon === "mail" ? "mail" : "wp"}`}
                    >
                      <div className="channel-card-icon">{getChannelIcon(ch.icon)}</div>
                      <div className="channel-card-content">
                        <h3>
                          {ch.title}
                          {ch.badge && <span className="channel-badge">{ch.badge}</span>}
                        </h3>
                        <p>{ch.desc}</p>
                        <span className="channel-action-link">
                          {ch.action}
                          <ExternalLink size={13} className="flip-rtl" />
                        </span>
                      </div>
                    </a>
                  </Reveal>
                ))}
              </div>
            </div>

            {/* Right Column: Support Form Card */}
            <Reveal delay={120}>
              <div className="contact-form-card">
                <div style={{ marginBottom: 28 }}>
                  <span className="section-label">{t("contact.form.label")}</span>
                  <h3 style={{ fontSize: "1.45rem", fontWeight: 700, margin: "6px 0 8px 0" }}>
                    {t("contact.form.title")}
                  </h3>
                  <p style={{ color: "var(--text-secondary)", fontSize: "0.9375rem", margin: 0 }}>
                    {t("contact.form.subtitle")}
                  </p>
                </div>

                {submitted ? (
                  <div style={{ textAlign: "center", padding: "40px 16px" }}>
                    <CheckCircle2
                      size={54}
                      color="var(--success)"
                      style={{ margin: "0 auto 16px auto" }}
                    />
                    <h4 style={{ fontSize: "1.4rem", fontWeight: 700, marginBottom: 12 }}>
                      {t("contact.form.successTitle")}
                    </h4>
                    <p
                      style={{
                        color: "var(--text-secondary)",
                        maxWidth: 480,
                        margin: "0 auto 28px auto",
                        lineHeight: 1.7,
                      }}
                    >
                      {t("contact.form.successDesc")}
                    </p>
                    <button
                      type="button"
                      className="btn btn-primary"
                      onClick={() => {
                        setSubmitted(false);
                        setFormData({
                          name: "",
                          email: "",
                          category: "tech",
                          priority: "normal",
                          siteUrl: "",
                          message: "",
                        });
                      }}
                    >
                      {t("contact.form.sendAnother")}
                    </button>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit} className="form-grid">
                    {/* Row 1: Name & Email */}
                    <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: 16 }}>
                      <div className="form-field">
                        <label htmlFor="c-name">{t("contact.form.name")} *</label>
                        <input
                          id="c-name"
                          type="text"
                          required
                          placeholder={t("contact.form.namePlaceholder")}
                          value={formData.name}
                          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                          style={{
                            width: "100%",
                            padding: "12px 14px",
                            borderRadius: "var(--radius-input)",
                            border: "1px solid var(--border)",
                            background: "var(--bg-primary)",
                            color: "var(--text-primary)",
                          }}
                        />
                      </div>

                      <div className="form-field">
                        <label htmlFor="c-email">{t("contact.form.email")} *</label>
                        <input
                          id="c-email"
                          type="email"
                          required
                          placeholder={t("contact.form.emailPlaceholder")}
                          value={formData.email}
                          onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                          style={{
                            width: "100%",
                            padding: "12px 14px",
                            borderRadius: "var(--radius-input)",
                            border: "1px solid var(--border)",
                            background: "var(--bg-primary)",
                            color: "var(--text-primary)",
                            direction: "ltr",
                          }}
                        />
                      </div>
                    </div>

                    {/* Row 2: Category & Priority */}
                    <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: 16 }}>
                      <div className="form-field">
                        <label htmlFor="c-category">{t("contact.form.category")}</label>
                        <select
                          id="c-category"
                          value={formData.category}
                          onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                        >
                          {categoryOptions.map((opt) => (
                            <option key={opt.id} value={opt.id}>
                              {opt.label}
                            </option>
                          ))}
                        </select>
                      </div>

                      <div className="form-field">
                        <label>{t("contact.form.priority")}</label>
                        <div className="priority-selector">
                          <button
                            type="button"
                            className={`priority-btn ${formData.priority === "normal" ? "active" : ""}`}
                            onClick={() => setFormData({ ...formData, priority: "normal" })}
                          >
                            <Clock size={15} />
                            عادی
                          </button>
                          <button
                            type="button"
                            className={`priority-btn urgent ${formData.priority === "urgent" ? "urgent active" : ""}`}
                            onClick={() => setFormData({ ...formData, priority: "urgent" })}
                          >
                            <AlertTriangle size={15} />
                            فوری (بحرانی)
                          </button>
                        </div>
                      </div>
                    </div>

                    {/* Row 3: Website URL */}
                    <div className="form-field">
                      <label htmlFor="c-siteUrl">{t("contact.form.siteUrl")}</label>
                      <input
                        id="c-siteUrl"
                        type="url"
                        placeholder={t("contact.form.siteUrlPlaceholder")}
                        value={formData.siteUrl}
                        onChange={(e) => setFormData({ ...formData, siteUrl: e.target.value })}
                        style={{
                          width: "100%",
                          padding: "12px 14px",
                          borderRadius: "var(--radius-input)",
                          border: "1px solid var(--border)",
                          background: "var(--bg-primary)",
                          color: "var(--text-primary)",
                          direction: "ltr",
                        }}
                      />
                    </div>

                    {/* Row 4: Message */}
                    <div className="form-field">
                      <label htmlFor="c-message">{t("contact.form.message")} *</label>
                      <textarea
                        id="c-message"
                        required
                        rows={5}
                        placeholder={t("contact.form.messagePlaceholder")}
                        value={formData.message}
                        onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                      />
                    </div>

                    <button
                      type="submit"
                      className="btn btn-primary btn-lg"
                      style={{ width: "100%", justifyContent: "center", marginTop: 8 }}
                      disabled={loading}
                    >
                      {loading ? (
                        t("contact.form.sending")
                      ) : (
                        <>
                          <Send size={18} />
                          {t("contact.form.submit")}
                        </>
                      )}
                    </button>
                  </form>
                )}
              </div>
            </Reveal>
          </div>
        </div>
      </section>

      <SectionDivider toAlt />

      {/* ===================== SLA & GUARANTEES ===================== */}
      <section className="section section-alt" id="sla">
        <div className="container">
          <SectionHead
            label={t("contact.sla.label")}
            title={t("contact.sla.title")}
          />
          <div className="features-grid">
            {slaItems.map((item, i) => (
              <Reveal key={item.title} delay={i * 90}>
                <div className="feature-card">
                  <span className="feature-icon" aria-hidden="true">
                    {i === 0 && <Clock size={22} />}
                    {i === 1 && <Sparkles size={22} />}
                    {i === 2 && <ShieldAlert size={22} />}
                  </span>
                  <h3>{item.title}</h3>
                  <p>{item.desc}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <SectionDivider toAlt={false} flip />

      {/* ===================== FAQ ACCORDION ===================== */}
      <section className="section" id="faq">
        <div className="container" style={{ maxWidth: 900 }}>
          <SectionHead
            label={t("contact.faq.label")}
            title={t("contact.faq.title")}
            subtitle={t("contact.faq.subtitle")}
          />
          <Reveal>
            <Faq items={faqItems} />
          </Reveal>
        </div>
      </section>
    </>
  );
}
