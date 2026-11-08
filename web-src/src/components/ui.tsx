import { useEffect, useRef, useState } from "react";
import type { ReactNode, RefObject } from "react";
import { AlertOctagon, AlertTriangle, ArrowUp, Check, ChevronDown, Copy, Info, Lightbulb, Sparkles, Terminal } from "lucide-react";
import { useApp } from "../lib/AppContext";

/* ==========================================================================
   Reveal — IntersectionObserver entrance animations (fire once)
   variants: up | fade | left | right | scale
   ========================================================================== */
export type RevealVariant = "up" | "fade" | "left" | "right" | "scale";

interface RevealProps {
  children: ReactNode;
  delay?: number;
  className?: string;
  as?: "div" | "section" | "article" | "li" | "span";
  variant?: RevealVariant;
}

const VARIANT_CLASS: Record<RevealVariant, string> = {
  up: "",
  fade: "fade-only",
  left: "fade-left",
  right: "fade-right",
  scale: "scale-in",
};

export function Reveal({ children, delay = 0, className = "", as = "div", variant = "up" }: RevealProps) {
  const ref = useRef<HTMLElement | null>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setVisible(true);
            io.unobserve(entry.target); /* animate once */
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    io.observe(el);
    return () => io.disconnect();
  }, []);

  const Tag = as as "div";
  return (
    <Tag
      ref={ref as never}
      data-delay={delay}
      className={`reveal${VARIANT_CLASS[variant] ? ` ${VARIANT_CLASS[variant]}` : ""}${visible ? " visible" : ""}${className ? ` ${className}` : ""}`}
      style={{ ["--reveal-delay" as never]: `${delay}ms` }}
    >
      {children}
    </Tag>
  );
}

/* ==========================================================================
   useRowStagger — alternating slide-in for comparison table rows
   ========================================================================== */
export function useRowStagger<T extends HTMLElement>(dep: unknown): RefObject<T | null> {
  const ref = useRef<T | null>(null);
  useEffect(() => {
    const root = ref.current;
    if (!root) return;
    const rows = Array.from(root.querySelectorAll("tbody tr"));
    rows.forEach((row, i) => {
      row.classList.add("tr-anim", i % 2 === 0 ? "fade-left" : "fade-right");
      (row as HTMLElement).style.transitionDelay = `${(i % 8) * 55}ms`;
    });
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: "0px 0px -30px 0px" }
    );
    rows.forEach((row) => io.observe(row));
    return () => io.disconnect();
  }, [dep]);
  return ref;
}

/* ==========================================================================
   SectionHead — label pill + title + subtitle
   ========================================================================== */
export function SectionHead({ label, title, subtitle }: { label: string; title: string; subtitle?: string }) {
  return (
    <Reveal className="section-head">
      <span className="section-label">{label}</span>
      <h2 className="section-title">{title}</h2>
      {subtitle ? <p className="section-subtitle">{subtitle}</p> : null}
    </Reveal>
  );
}

/* ==========================================================================
   PageHero — inner-page hero strip (optional decorative art layer)
   ========================================================================== */
interface PageHeroProps {
  label: string;
  title: ReactNode;
  lead?: string;
  updated?: string;
  art?: ReactNode;
}

export function PageHero({ label, title, lead, updated, art }: PageHeroProps) {
  return (
    <div className="page-hero">
      {art ? <div className="page-hero-art" aria-hidden="true">{art}</div> : null}
      <div className="container">
        <Reveal>
          <span className="section-label">{label}</span>
          <h1>{title}</h1>
          {lead ? <p className="lead">{lead}</p> : null}
          {updated ? <span className="badge updated">{updated}</span> : null}
        </Reveal>
      </div>
    </div>
  );
}

/* ==========================================================================
   FAQ — single-open accordion with smooth height transition + ARIA state
   ========================================================================== */
export interface FaqItem {
  q: string;
  a: string;
}

export function Faq({ items, defaultOpen = 0 }: { items: FaqItem[]; defaultOpen?: number | null }) {
  const [openIndex, setOpenIndex] = useState<number | null>(defaultOpen);
  const refs = useRef<(HTMLDivElement | null)[]>([]);

  return (
    <div className="faq-list">
      {items.map((item, i) => {
        const open = openIndex === i;
        return (
          <Reveal key={i} delay={i * 60}>
            <div className={`faq-item${open ? " open" : ""}`}>
              <button
                type="button"
                className="faq-question"
                aria-expanded={open}
                aria-controls={`faq-panel-${i}`}
                id={`faq-button-${i}`}
                onClick={() => setOpenIndex(open ? null : i)}
              >
                <span>{item.q}</span>
                <ChevronDown size={18} className="faq-chevron" aria-hidden="true" />
              </button>
              <div
                id={`faq-panel-${i}`}
                role="region"
                aria-labelledby={`faq-button-${i}`}
                className="faq-answer"
                ref={(el) => {
                  refs.current[i] = el;
                }}
                style={{ maxHeight: open ? `${(refs.current[i]?.scrollHeight ?? 480) + 40}px` : "0px" }}
              >
                <div className="faq-answer-inner">{item.a}</div>
              </div>
            </div>
          </Reveal>
        );
      })}
    </div>
  );
}

/* ==========================================================================
   CodeBlock — line numbers, language label, copy feedback, light highlighting
   ========================================================================== */

type TokenType = "kw" | "str" | "com" | "num" | "flag";

const KEYWORDS: Record<string, RegExp> = {
  bash: /\b(wp|backupino|curl|sudo|wget|git|composer|php|cd|mkdir|export)\b/,
  php: /\b(defined|exit|function|return|if|else|foreach|require|include|echo|new|use|const|class)\b/,
  json: /^$/,
  sql: /\b(SET|INSERT|INTO|VALUES|CREATE|TABLE|SELECT|FROM|WHERE|FOREIGN_KEY_CHECKS)\b/i,
  text: /^$/,
};

function tokenizeLine(line: string, lang: string): ReactNode[] {
  const nodes: ReactNode[] = [];
  // Combined matcher: comments | strings | flags | numbers | keywords
  const kw = KEYWORDS[lang] ?? KEYWORDS.text;
  const master = new RegExp(
    [
      "(?<com>#.*$|//.*$)",
      '(?<str>"[^"\\n]*"|\'[^\'\\n]*\')',
      lang === "bash" ? "(?<flag>--[a-zA-Z0-9_-]+(?:=[^\\s]+)?)" : "",
      "(?<num>\\b\\d+(?:\\.\\d+)?\\b)",
      kw.source !== "^$" ? `(?<kw>${kw.source.replace(/^\\b/, "").replace(/\\b$/, "")})` : "",
    ]
      .filter(Boolean)
      .join("|"),
    lang === "sql" ? "gi" : "g"
  );

  let last = 0;
  let m: RegExpExecArray | null;
  let k = 0;
  const regex = new RegExp(master.source, master.flags.replace("g", "") + "g");
  while ((m = regex.exec(line)) !== null) {
    if (m.index > last) nodes.push(line.slice(last, m.index));
    const groups = m.groups ?? {};
    const type: TokenType | null = groups.com
      ? "com"
      : groups.str
        ? "str"
        : groups.flag
          ? "flag"
          : groups.num
            ? "num"
            : groups.kw
              ? "kw"
              : null;
    nodes.push(
      type ? (
        <span key={k++} className={`tok-${type}`}>
          {m[0]}
        </span>
      ) : (
        m[0]
      )
    );
    last = m.index + m[0].length;
    if (m[0].length === 0) regex.lastIndex++;
  }
  if (last < line.length) nodes.push(line.slice(last));
  return nodes;
}

function detectLang(title: string, explicit?: string): string {
  if (explicit) return explicit;
  const t = title.toLowerCase();
  if (t.includes("json") || t.includes("manifest")) return "json";
  if (t.includes("sql")) return "sql";
  if (t.includes("php") && !t.includes("installer.php") || t.includes("wp-config")) return "php";
  if (t.includes("url")) return "text";
  return "bash";
}

export function CodeBlock({ title, content, lang }: { title: string; content: string; lang?: string }) {
  const { t } = useApp();
  const [copied, setCopied] = useState(false);
  const language = detectLang(title, lang);
  const lines = content.split("\n");

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(content);
    } catch {
      const ta = document.createElement("textarea");
      ta.value = content;
      ta.style.position = "fixed";
      ta.style.opacity = "0";
      document.body.appendChild(ta);
      ta.select();
      document.execCommand("copy");
      document.body.removeChild(ta);
    }
    setCopied(true);
    window.setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="code-block">
      <div className="code-block-head">
        <span className="code-block-title">
          <Terminal size={13} aria-hidden="true" />
          {title}
        </span>
        <span className="code-block-tools">
          <span className="cb-lang">{language}</span>
          <button type="button" className={`copy-btn${copied ? " copied" : ""}`} onClick={copy}>
            {copied ? <Check size={13} aria-hidden="true" /> : <Copy size={13} aria-hidden="true" />}
            {copied ? t("actions.copied") : t("actions.copy")}
          </button>
        </span>
      </div>
      <pre tabIndex={0}>
        <code>
          {lines.map((line, i) => (
            <span className="cb-row" key={i}>
              <span className="cb-ln" aria-hidden="true">
                {i + 1}
              </span>
              <span className="cb-tx">{tokenizeLine(line, language)}</span>
            </span>
          ))}
        </code>
      </pre>
    </div>
  );
}

/* ==========================================================================
   Callout — docs alert boxes (info / warning / danger / tip / pro)
   ========================================================================== */
const CALLOUT_ICON: Record<string, typeof Info> = {
  info: Info,
  warning: AlertTriangle,
  danger: AlertOctagon,
  tip: Lightbulb,
  pro: Sparkles,
};

export function Callout({ variant, text }: { variant: string; text: string }) {
  const Icon = CALLOUT_ICON[variant] ?? Info;
  return (
    <div className={`callout callout-${variant}`} role={variant === "danger" || variant === "warning" ? "alert" : "note"}>
      <span className="callout-icon" aria-hidden="true">
        <Icon size={17} />
      </span>
      <div className="callout-content">
        {variant === "pro" && <span className="nav-pro-badge callout-pro-badge">PRO</span>}
        <span>{text}</span>
      </div>
    </div>
  );
}

/* ==========================================================================
   Breadcrumbs
   ========================================================================== */
export interface Crumb {
  label: string;
  href?: string;
}

export function Breadcrumbs({ items }: { items: Crumb[] }) {
  return (
    <nav className="breadcrumbs" aria-label="Breadcrumb">
      {items.map((crumb, i) => {
        const last = i === items.length - 1;
        return (
          <span key={i} style={{ display: "inline-flex", alignItems: "center", gap: 8 }}>
            {i > 0 && (
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round" className="flip-rtl" aria-hidden="true">
                <path d="m9 18 6-6-6-6" />
              </svg>
            )}
            {crumb.href && !last ? (
              <a href={crumb.href}>{crumb.label}</a>
            ) : (
              <span className="current" aria-current="page">
                {crumb.label}
              </span>
            )}
          </span>
        );
      })}
    </nav>
  );
}

/* ==========================================================================
   BackToTop — smooth fade + scale in/out after 300px of scroll
   ========================================================================== */
export function BackToTop() {
  const { t } = useApp();
  const [show, setShow] = useState(false);

  useEffect(() => {
    const onScroll = () => setShow(window.scrollY > 300);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <button
      type="button"
      className={`back-top${show ? " show" : ""}`}
      onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      aria-label={t("actions.backToTop")}
      tabIndex={show ? 0 : -1}
    >
      <ArrowUp size={19} aria-hidden="true" />
    </button>
  );
}
