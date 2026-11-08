import { useEffect, useState } from "react";
import { useApp } from "../lib/AppContext";
import { PageHero } from "../components/ui";

interface TocItem {
  id: string;
  label: string;
}

interface PrivacySection {
  id: string;
  title: string;
  paras: string[];
  list?: string[];
}

export default function Privacy() {
  const { t, tr } = useApp();
  const toc = tr<TocItem[]>("privacy.toc", []);
  const sections = tr<PrivacySection[]>("privacy.sections", []);
  const [active, setActive] = useState<string>(sections[0]?.id ?? "");

  /** Scrollspy — highlight the in-view section inside the TOC */
  useEffect(() => {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) setActive(entry.target.id);
        });
      },
      { rootMargin: "-30% 0px -60% 0px", threshold: 0 }
    );
    sections.forEach((s) => {
      const el = document.getElementById(s.id);
      if (el) io.observe(el);
    });
    return () => io.disconnect();
  }, [sections]);

  const jump = (id: string) => (e: React.MouseEvent) => {
    e.preventDefault();
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
    setActive(id);
  };

  return (
    <>
      <PageHero
        label={t("privacy.hero.label")}
        title={t("privacy.hero.title")}
        lead={t("privacy.hero.subtitle")}
        updated={t("privacy.hero.updated")}
      />

      <section className="section">
        <div className="container privacy-layout">
          {/* Table of contents */}
          <aside className="toc" aria-label={t("privacy.tocTitle")}>
            <h2>{t("privacy.tocTitle")}</h2>
            <nav>
              {toc.map((item) => (
                <a
                  key={item.id}
                  href={`#${item.id}`}
                  className={active === item.id ? "active" : ""}
                  onClick={jump(item.id)}
                >
                  {item.label}
                </a>
              ))}
            </nav>
          </aside>

          {/* Legal body */}
          <article className="legal-body">
            {sections.map((section, i) => (
              <section key={section.id} id={section.id} className="legal-section">
                <h2>
                  <span className="legal-num" aria-hidden="true">
                    {i + 1}
                  </span>
                  {section.title}
                </h2>
                {section.paras.map((para, j) => (
                  <p key={j}>{para}</p>
                ))}
                {section.list && (
                  <ul>
                    {section.list.map((item, j) => (
                      <li key={j}>{item}</li>
                    ))}
                  </ul>
                )}
              </section>
            ))}
          </article>
        </div>
      </section>
    </>
  );
}
