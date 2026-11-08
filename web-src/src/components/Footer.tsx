import { Globe, Mail, Moon, Scale, Sun } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { GithubIcon } from "./icons";
import { LanguageSwitcher } from "./LanguageSwitcher";
import { Logo } from "./Logo";

const GITHUB_PLUGIN = "https://github.com/Backupino/plugin";
const GITHUB_ISSUES = "https://github.com/Backupino/plugin/issues";
const WP_PROFILE = "https://profiles.wordpress.org/backupino/";

export function Footer() {
  const { t, theme, toggleThemeMode } = useApp();

  return (
    <footer className="site-footer">
      <div className="container">
        <div className="footer-main">
          <div className="footer-brand">
            <a href={href("home")} className="brand" aria-label="Backupino">
              <Logo />
            </a>
            <p>{t("footer.tagline")}</p>
            <div className="footer-social">
              <a className="icon-btn" href={GITHUB_PLUGIN} target="_blank" rel="noreferrer noopener" aria-label="GitHub">
                <GithubIcon size={16} />
              </a>
              <a className="icon-btn" href={WP_PROFILE} target="_blank" rel="noreferrer noopener" aria-label="WordPress.org">
                <Globe size={16} />
              </a>
              <a className="icon-btn" href={`mailto:${t("footer.email")}`} aria-label={t("footer.emailLabel")}>
                <Mail size={16} />
              </a>
            </div>
          </div>

          <div className="footer-col">
            <h3>{t("footer.product")}</h3>
            <ul>
              <li><a href={href("home")}>{t("footer.links.home")}</a></li>
              <li><a href="#/?scroll=features">{t("footer.links.features")}</a></li>
              <li><a href={href("pricing")}>{t("footer.links.pricing")}</a></li>
              <li><a href={href("pro")}>{t("footer.links.pro")}</a></li>
              <li><a href={href("docs")}>{t("footer.links.docs")}</a></li>
            </ul>
          </div>

          <div className="footer-col">
            <h3>{t("footer.company")}</h3>
            <ul>
              <li><a href={href("about")}>{t("footer.links.about")}</a></li>
              <li><a href={href("privacy")}>{t("footer.links.privacy")}</a></li>
              <li><a href={href("privacy")}>{t("footer.links.terms")}</a></li>
            </ul>
          </div>

          <div className="footer-col">
            <h3>{t("footer.opensource")}</h3>
            <ul>
              <li>
                <a href={GITHUB_PLUGIN} target="_blank" rel="noreferrer noopener">
                  <GithubIcon size={14} /> {t("footer.links.github")}
                </a>
              </li>
              <li>
                <a href={GITHUB_PLUGIN} target="_blank" rel="noreferrer noopener">
                  {t("footer.links.contribute")}
                </a>
              </li>
              <li>
                <a href={GITHUB_ISSUES} target="_blank" rel="noreferrer noopener">
                  {t("footer.links.issues")}
                </a>
              </li>
              <li>
                <a href={WP_PROFILE} target="_blank" rel="noreferrer noopener">
                  <Globe size={14} /> {t("footer.links.wpProfile")}
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div className="footer-bottom">
          <span className="footer-copy-line">
            {t("footer.copyright")} <span className="version-tag">{t("global.version")}</span>
          </span>
          <span className="footer-gpl">
            <Scale size={14} /> {t("footer.gpl")}
          </span>
          <div className="mini-controls">
            <button type="button" className="icon-btn" onClick={toggleThemeMode} aria-label={t("actions.theme")}>
              {theme === "dark" ? <Sun size={16} /> : <Moon size={16} />}
            </button>
            <LanguageSwitcher dropUp />
          </div>
        </div>
      </div>
    </footer>
  );
}
