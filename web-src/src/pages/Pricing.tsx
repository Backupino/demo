import { ArrowRight, Check, Info, Minus, X } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { Faq, PageHero, Reveal, SectionHead, useRowStagger, type FaqItem } from "../components/ui";
import { PricingArt, SectionDivider } from "../components/art";

type Mark = "yes" | "mid" | "no";
type Row = { f: string; b: [Mark, string]; d: [Mark, string]; u: [Mark, string]; a: [Mark, string] };
type ProRow = { f: string; free: [Mark, string]; pro: [Mark, string] };

interface AttrValue {
  s: "yes" | "no" | "text";
  t?: string;
}

interface Plan {
  name: string;
  price: string;
  period: string;
  note: string;
  desc: string;
  cta: string;
  style: "outline" | "standard" | "popular" | "premium";
  popular: boolean;
  attrs: Record<string, AttrValue>;
}

const ATTR_ORDER = [
  "domains",
  "cloudConnections",
  "cloudVersions",
  "dedicatedCloud",
  "multisite",
  "whitelabel",
  "teamMembers",
  "support",
  "domainChanges",
];

function CellMark({ mark, text }: { mark: Mark; text: string }) {
  const Icon = mark === "yes" ? Check : mark === "no" ? X : Minus;
  const cls = mark === "yes" ? "m-yes" : mark === "no" ? "m-no" : "m-mid";
  return (
    <span className="cell-mark">
      <Icon size={16} className={cls} aria-hidden="true" />
      {text && <span>{text}</span>}
    </span>
  );
}

function AttrCell({ value }: { value: AttrValue }) {
  if (value.s === "yes") {
    return (
      <span className="attr-value">
        <Check size={15} className="a-yes" aria-hidden="true" />
      </span>
    );
  }
  if (value.s === "no") {
    return (
      <span className="attr-value">
        <Minus size={15} className="a-no" aria-hidden="true" />
      </span>
    );
  }
  return <span className="attr-value">{value.t}</span>;
}

export default function Pricing() {
  const { t, tr } = useApp();

  const plans = tr<Plan[]>("pricing.plans", []);
  const attrLabels = tr<Record<string, string>>("pricing.attrLabels", {});
  const proRows = tr<ProRow[]>("pricing.proCompare.rows", []);
  const proColumns = tr<string[]>("pricing.proCompare.columns", []);
  const columns = tr<string[]>("pricing.comparison.columns", []);
  const rows = tr<Row[]>("pricing.comparison.rows", []);
  const domainRules = tr<FaqItem[]>("pricing.domainRules.items", []);
  const faqItems = tr<FaqItem[]>("pricing.faq.items", []);

  const proTableRef = useRowStagger<HTMLDivElement>(proRows);
  const matrixTableRef = useRowStagger<HTMLDivElement>(rows);

  const ctaClass = (style: Plan["style"]) =>
    style === "popular" ? "btn btn-primary btn-block" : style === "premium" ? "btn btn-white btn-block" : "btn btn-outline btn-block";

  return (
    <>
      <PageHero label={t("pricing.hero.label")} title={t("pricing.hero.title")} lead={t("pricing.hero.subtitle")} art={<PricingArt />} />

      {/* ============================ PLANS ============================ */}
      <section className="section">
        <div className="container">
          <div className="pricing-grid-4">
            {plans.map((plan, i) => (
              <Reveal key={plan.name} delay={i * 80}>
                <article className={`price-card tier-${plan.style}${plan.popular ? " popular" : ""}`}>
                  {plan.popular && <span className="popular-tag">{t("pricing.popularTag")}</span>}
                  <h3 className="price-name">{plan.name}</h3>
                  <p className="price-desc">{plan.desc}</p>
                  <div className="price-amount">
                    <span className="price-value">{plan.price}</span>
                    {plan.period && <span className="price-period">/ {plan.period}</span>}
                  </div>
                  <p className="plan-note">{plan.note || "\u00A0"}</p>
                  <div className="plan-attrs">
                    {ATTR_ORDER.map((key) => (
                      <div className="plan-attr" key={key}>
                        <span className="attr-label">{attrLabels[key] ?? key}</span>
                        <AttrCell value={plan.attrs[key]} />
                      </div>
                    ))}
                  </div>
                  <a href={href("register")} className={ctaClass(plan.style)}>
                    {plan.cta}
                  </a>
                </article>
              </Reveal>
            ))}
          </div>

          <Reveal delay={200}>
            <p className="pricing-note mt-4" style={{ color: "var(--text-muted)", fontSize: "0.9375rem", display: "flex", alignItems: "center", justifyContent: "center", gap: 8 }}>
              <Info size={16} style={{ color: "var(--accent)", flexShrink: 0 }} aria-hidden="true" />
              {t("pricing.freeNote")}
            </p>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* ======================= FREE VS PRO TABLE ======================= */}
      <section className="section section-alt">
        <div className="container">
          <SectionHead label={t("pricing.proCompare.label")} title={t("pricing.proCompare.title")} subtitle={t("pricing.proCompare.subtitle")} />
          <Reveal>
            <div className="table-wrap" ref={proTableRef}>
              <table className="compare-table">
                <thead>
                  <tr>
                    {proColumns.map((col, i) => (
                      <th key={col} scope="col" className={i === 2 ? "col-brand" : undefined}>
                        {col}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {proRows.map((row) => (
                    <tr key={row.f} className={row.free[0] === "no" ? "row-pro" : undefined}>
                      <td>{row.f}</td>
                      <td>
                        <CellMark mark={row.free[0]} text={row.free[1]} />
                      </td>
                      <td className="col-brand">
                        <CellMark mark={row.pro[0]} text={row.pro[1]} />
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
      {/* ====================== COMPETITOR MATRIX ======================= */}
      <section className="section">
        <div className="container">
          <SectionHead
            label={t("pricing.comparison.label")}
            title={t("pricing.comparison.title")}
            subtitle={t("pricing.comparison.subtitle")}
          />
          <Reveal>
            <div className="table-wrap" ref={matrixTableRef}>
              <table className="compare-table">
                <thead>
                  <tr>
                    {columns.map((col, i) => (
                      <th key={col} scope="col" className={i === 1 ? "col-brand" : undefined}>
                        {col}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.f}>
                      <td>{row.f}</td>
                      <td className="col-brand">
                        <CellMark mark={row.b[0]} text={row.b[1]} />
                      </td>
                      <td>
                        <CellMark mark={row.d[0]} text={row.d[1]} />
                      </td>
                      <td>
                        <CellMark mark={row.u[0]} text={row.u[1]} />
                      </td>
                      <td>
                        <CellMark mark={row.a[0]} text={row.a[1]} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Reveal>
          <Reveal delay={120}>
            <p className="mt-2" style={{ textAlign: "center", color: "var(--text-muted)", fontSize: "0.875rem" }}>
              <a href="#/docs">{t("pricing.comparison.note")} →</a>
            </p>
          </Reveal>
        </div>
      </section>

      <SectionDivider toAlt />
      {/* ========================= DOMAIN RULES ========================== */}
      <section className="section section-alt">
        <div className="container">
          <SectionHead label={t("pricing.domainRules.label")} title={t("pricing.domainRules.title")} subtitle={t("pricing.domainRules.subtitle")} />
          <Faq items={domainRules} />
        </div>
      </section>

      <SectionDivider toAlt={false} flip />
      {/* ========================== PRICING FAQ ========================== */}
      <section className="section">
        <div className="container">
          <SectionHead label={t("pricing.faq.label")} title={t("pricing.faq.title")} />
          <Faq items={faqItems} />
        </div>
      </section>

      {/* ============================== CTA ============================== */}
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <Reveal>
            <div className="cta-banner">
              <h2>{t("pricing.cta.title")}</h2>
              <p>{t("pricing.cta.subtitle")}</p>
              <div className="btn-row">
                <a href={href("register")} className="btn btn-white btn-lg">
                  {t("pricing.cta.button")}
                  <ArrowRight size={17} className="flip-rtl" aria-hidden="true" />
                </a>
              </div>
            </div>
          </Reveal>
        </div>
      </section>
    </>
  );
}
