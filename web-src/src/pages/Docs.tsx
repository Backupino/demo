import { useMemo, useState } from "react";
import { ChevronDown, Info, ListTree, Search, SearchX } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import type { DocArticle, DocBlock, DocTreeGroup } from "../lib/i18n";
import { Breadcrumbs, Callout, CodeBlock, PageHero } from "../components/ui";

/** Render a typed documentation block (heading/paragraph/list/code/note/table/qa/callout). */
function BlockRenderer({ block }: { block: DocBlock }) {
  switch (block.type) {
    case "heading":
      return <h2 id={block.id}>{block.text}</h2>;
    case "paragraph":
      return <p>{block.text}</p>;
    case "list": {
      const items = (block.items ?? []) as string[];
      return block.ordered ? (
        <ol>
          {items.map((item, i) => (
            <li key={i}>{item}</li>
          ))}
        </ol>
      ) : (
        <ul>
          {items.map((item, i) => (
            <li key={i}>{item}</li>
          ))}
        </ul>
      );
    }
    case "code":
      return <CodeBlock title={block.title ?? "code"} content={block.content ?? ""} lang={block.lang} />;
    case "note":
      return (
        <div className="doc-note">
          <Info size={18} aria-hidden="true" />
          <span>{block.text}</span>
        </div>
      );
    case "callout":
      return <Callout variant={block.variant ?? "info"} text={block.text ?? ""} />;
    case "table":
      return (
        <div className="table-wrap">
          <table className="compare-table">
            <thead>
              <tr>
                {(block.head ?? []).map((h, i) => (
                  <th key={i} scope="col" className={i === 1 ? "col-brand" : undefined}>
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {(block.rows ?? []).map((row, i) => (
                <tr key={i}>
                  {row.map((cell, j) => (
                    <td key={j} className={j === 1 ? "col-brand" : undefined}>
                      {cell}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      );
    case "qa":
      return (
        <div className="doc-qa">
          {((block.items ?? []) as { q: string; a: string }[]).map((item, i) => (
            <div key={i} className="doc-qa-item">
              <h3>{item.q}</h3>
              <p>{item.a}</p>
            </div>
          ))}
        </div>
      );
    default:
      return null;
  }
}

interface SidebarProps {
  tree: DocTreeGroup[];
  articles: Record<string, DocArticle>;
  active: string;
  activeAnchor: string | null;
  query: string;
  onQuery: (value: string) => void;
  onSelect: (slug: string, anchor?: string) => void;
}

function SidebarNav({ tree, articles, active, activeAnchor, query, onQuery, onSelect }: SidebarProps) {
  const { t } = useApp();
  return (
    <>
      {/* Search lives inside the sidebar column, spanning its exact width */}
      <div className="docs-search docs-side-search">
        <Search size={17} aria-hidden="true" />
        <input
          type="search"
          value={query}
          onChange={(e) => onQuery(e.target.value)}
          placeholder={t("docs.searchPlaceholder")}
          aria-label={t("docs.searchPlaceholder")}
        />
      </div>
      {tree.map((group) => {
        const q = query.trim().toLowerCase();
        const items = group.items.filter((item) => {
          if (!q) return true;
          const article = articles[item.slug];
          const childHit = item.children?.some((c) => c.title.toLowerCase().includes(q));
          const articleHit = article && `${article.title} ${article.intro}`.toLowerCase().includes(q);
          return articleHit || childHit;
        });
        if (items.length === 0) return null;
        return (
          <div className="docs-sidebar-group" key={group.id}>
            <h3>{group.title}</h3>
            <ul>
              {items.map((item) => {
                const article = articles[item.slug];
                if (!article) return null;
                return (
                  <li key={item.slug}>
                    <button
                      type="button"
                      className={active === item.slug && !activeAnchor ? "active" : ""}
                      aria-current={active === item.slug && !activeAnchor ? "page" : undefined}
                      onClick={() => onSelect(item.slug)}
                    >
                      {article.title}
                      {item.pro && <span className="nav-pro-badge">PRO</span>}
                    </button>
                    {item.children && active === item.slug && (
                      <div className="docs-tree-children">
                        {item.children.map((child) => (
                          <button
                            key={child.anchor}
                            type="button"
                            className={activeAnchor === child.anchor ? "active" : ""}
                            onClick={() => onSelect(item.slug, child.anchor)}
                          >
                            {child.title}
                          </button>
                        ))}
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          </div>
        );
      })}
    </>
  );
}

export default function Docs() {
  const { t, tr } = useApp();
  const tree = tr<DocTreeGroup[]>("docs.tree", []);
  const articles = tr<Record<string, DocArticle>>("docs.articles", {});

  const [slug, setSlug] = useState<string>("what-is");
  const [anchor, setAnchor] = useState<string | null>(null);
  const [query, setQuery] = useState("");
  const [mobileOpen, setMobileOpen] = useState(false);

  /** Keep the current selection valid against the live search filter. */
  const visibleSlugs = useMemo(() => {
    const q = query.trim().toLowerCase();
    return tree
      .flatMap((g) => g.items)
      .filter((item) => {
        const article = articles[item.slug];
        if (!article) return false;
        if (!q) return true;
        const childHit = item.children?.some((c) => c.title.toLowerCase().includes(q));
        return `${article.title} ${article.intro}`.toLowerCase().includes(q) || childHit;
      })
      .map((i) => i.slug);
  }, [tree, articles, query]);

  const noResults = query.trim() !== "" && visibleSlugs.length === 0;
  const shownSlug = !noResults && visibleSlugs.includes(slug) ? slug : (visibleSlugs[0] ?? slug);
  const article = noResults ? undefined : articles[shownSlug];

  const select = (next: string, nextAnchor?: string) => {
    const jump = () => {
      if (nextAnchor) {
        document.getElementById(nextAnchor)?.scrollIntoView({ behavior: "smooth", block: "start" });
      } else {
        document.querySelector(".doc-article")?.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    };
    if (next === shownSlug) {
      setAnchor(nextAnchor ?? null);
      jump();
    } else {
      setSlug(next);
      setAnchor(nextAnchor ?? null);
      window.setTimeout(jump, 120);
    }
    setMobileOpen(false);
  };

  return (
    <div className="docs-page">
      <PageHero label={t("docs.hero.label")} title={t("docs.hero.title")} lead={t("docs.hero.subtitle")} />

      <section className="section" style={{ paddingTop: "var(--space-6)" }}>
        <div className="container">
          <Breadcrumbs
            items={[
              { label: t("docs.breadcrumbHome"), href: href("home") },
              { label: t("docs.breadcrumbDocs") },
              ...(article ? [{ label: article.title }] : []),
            ]}
          />

          <div className="docs-toolbar">
            <button
              type="button"
              className={`docs-mobile-toggle${mobileOpen ? " open" : ""}`}
              aria-expanded={mobileOpen}
              onClick={() => setMobileOpen((v) => !v)}
            >
              <span style={{ display: "inline-flex", alignItems: "center", gap: 8 }}>
                <ListTree size={17} aria-hidden="true" />
                {t("docs.menuLabel")}
              </span>
              <ChevronDown size={17} aria-hidden="true" />
            </button>
          </div>

          {/* Mobile collapsible navigation panel (search travels with the sidebar) */}
          <div className={`docs-mobile-panel${mobileOpen ? " open" : ""}`}>
            <div className="docs-sidebar-inner">
              <SidebarNav
                tree={tree}
                articles={articles}
                active={shownSlug}
                activeAnchor={anchor}
                query={query}
                onQuery={setQuery}
                onSelect={select}
              />
            </div>
          </div>

          <div className="docs-layout">
            {/* Desktop sticky sidebar — search pinned to its top */}
            <aside className="docs-sidebar" aria-label={t("docs.menuLabel")}>
              <SidebarNav
                tree={tree}
                articles={articles}
                active={shownSlug}
                activeAnchor={anchor}
                query={query}
                onQuery={setQuery}
                onSelect={select}
              />
            </aside>

            {/* Article */}
            {article ? (
              <article className="doc-article" key={shownSlug}>
                <header className="doc-article-head">
                  <h2 className="doc-article-title">
                    {article.title}
                    {article.pro && <span className="nav-pro-badge doc-pro-badge">PRO</span>}
                  </h2>
                </header>
                <p className="doc-intro">{article.intro}</p>
                {article.blocks.map((block, i) => (
                  <div className="doc-block" key={i}>
                    <BlockRenderer block={block} />
                  </div>
                ))}
              </article>
            ) : (
              <div className="doc-article docs-empty">
                <SearchX size={40} aria-hidden="true" />
                <p>{t("docs.noResults")}</p>
              </div>
            )}
          </div>
        </div>
      </section>
    </div>
  );
}
