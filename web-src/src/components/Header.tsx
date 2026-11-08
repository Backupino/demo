import { useEffect, useState } from "react";
import { Moon, Sun, X } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href, type Route } from "../lib/router";
import { LanguageSwitcher } from "./LanguageSwitcher";
import { Logo } from "./Logo";

interface HeaderProps {
  route: Route;
}

const NAV_LINKS: { route: Route; key: string; pro?: boolean }[] = [
  { route: "home", key: "nav.home" },
  { route: "download", key: "nav.download" },
  { route: "pricing", key: "nav.pricing" },
  { route: "pro", key: "nav.pro", pro: true },
  { route: "docs", key: "nav.docs" },
  { route: "about", key: "nav.about" },
  { route: "contact", key: "nav.contact" },
];

function VersionTag() {
  const { t } = useApp();
  return <span className="version-tag">{t("global.version")}</span>;
}

function TrustChip() {
  return (
    <span className="bk-header-trust-chip" aria-label="WordPress.org rating and installs">
      <span className="bk-chip-star">★</span>
      <span className="bk-chip-rating">5.0</span>
      <span className="bk-chip-divider">·</span>
      <span className="bk-chip-installs">1,000+</span>
    </span>
  );
}

export function Header({ route }: HeaderProps) {
  const { t, theme, toggleThemeMode } = useApp();
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  /** Sticky header shadow on scroll */
  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  /** Close drawer on Escape + lock scroll while open */
  useEffect(() => {
    if (!menuOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setMenuOpen(false);
    };
    document.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [menuOpen]);

  /** Close the drawer whenever the route changes */
  useEffect(() => setMenuOpen(false), [route]);

  return (
    <>
      <a href="#main-content" className="skip-link">
        {t("nav.skip")}
      </a>
      <header className={`site-header${scrolled ? " scrolled" : ""}`}>
        <div className="container header-inner">
          <a href={href("home")} className="brand" aria-label="Backupino — Home">
            <Logo />
            <VersionTag />
            <TrustChip />
          </a>

          <nav className="main-nav" aria-label="Primary">
            {NAV_LINKS.map(({ route: r, key, pro }) => (
              <a key={r} href={href(r)} className={route === r ? "active" : ""} aria-current={route === r ? "page" : undefined}>
                {t(key)}
                {pro && <span className="nav-pro-badge">{t("nav.proBadge")}</span>}
              </a>
            ))}
          </nav>

          <div className="header-actions">
            <button
              type="button"
              className="icon-btn"
              onClick={toggleThemeMode}
              aria-label={t("actions.theme")}
              title={t("actions.theme")}
            >
              {theme === "dark" ? (
                <Sun size={17} className="theme-icon-sun" />
              ) : (
                <Moon size={17} className="theme-icon-moon" />
              )}
            </button>

            <LanguageSwitcher />

            <a href={href("login")} className="btn btn-outline btn-sm auth-btn-desktop">
              {t("nav.login")}
            </a>
            <a href={href("register")} className="btn btn-primary btn-sm">
              {t("nav.register")}
            </a>

            <button
              type="button"
              className={`hamburger${menuOpen ? " open" : ""}`}
              aria-label={menuOpen ? t("nav.closeMenu") : t("nav.openMenu")}
              aria-expanded={menuOpen}
              aria-controls="mobile-menu"
              onClick={() => setMenuOpen((v) => !v)}
            >
              <span />
              <span />
              <span />
            </button>
          </div>
        </div>
      </header>

      {/* Mobile slide-in menu */}
      <div id="mobile-menu" className={`mobile-menu${menuOpen ? " open" : ""}`} role="dialog" aria-modal="true" aria-label={t("nav.openMenu")}>
        <div className="mobile-menu-backdrop" onClick={() => setMenuOpen(false)} aria-hidden="true" />
        <div className="mobile-menu-panel">
          <div className="mobile-menu-head">
            <a href={href("home")} className="brand mobile-only" onClick={() => setMenuOpen(false)}>
              <Logo />
              <VersionTag />
            </a>
            <button type="button" className="icon-btn" onClick={() => setMenuOpen(false)} aria-label={t("nav.closeMenu")}>
              <X size={18} />
            </button>
          </div>

          <nav className="mobile-nav-links" aria-label="Mobile">
            {NAV_LINKS.map(({ route: r, key, pro }) => (
              <a key={r} href={href(r)} className={route === r ? "active" : ""} onClick={() => setMenuOpen(false)}>
                {t(key)}
                {pro && <span className="nav-pro-badge">{t("nav.proBadge")}</span>}
              </a>
            ))}
          </nav>

          <div className="mobile-menu-actions">
            <a href={href("login")} className="btn btn-outline btn-block" onClick={() => setMenuOpen(false)}>
              {t("nav.login")}
            </a>
            <a href={href("register")} className="btn btn-primary btn-block" onClick={() => setMenuOpen(false)}>
              {t("nav.register")}
            </a>
          </div>
        </div>
      </div>
    </>
  );
}
