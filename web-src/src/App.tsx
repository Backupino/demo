import { useEffect, type ComponentType } from "react";
import { AppProvider, useApp } from "./lib/AppContext";
import { scrollTargetFromHash, useRoute, type Route } from "./lib/router";
import { Header } from "./components/Header";
import { Footer } from "./components/Footer";
import { BackToTop } from "./components/ui";
import Home from "./pages/Home";
import Download from "./pages/Download";
import About from "./pages/About";
import Pricing from "./pages/Pricing";
import Pro from "./pages/Pro";
import Privacy from "./pages/Privacy";
import Docs from "./pages/Docs";
import Contact from "./pages/Contact";
import Login from "./pages/Login";
import Register from "./pages/Register";

const PAGES: Record<Route, ComponentType> = {
  home: Home,
  download: Download,
  about: About,
  pricing: Pricing,
  pro: Pro,
  privacy: Privacy,
  docs: Docs,
  contact: Contact,
  login: Login,
  register: Register,
};

function Shell() {
  const route = useRoute();
  const { t, lang } = useApp();

  /** Unique, localized document title per page */
  useEffect(() => {
    document.title = t(`meta.titles.${route}`);
  }, [route, lang, t]);

  /** Scroll to top on page change, honoring `?scroll=` in-page targets */
  useEffect(() => {
    const target = scrollTargetFromHash();
    if (target) {
      const id = window.setTimeout(() => {
        document.getElementById(target)?.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 120);
      return () => window.clearTimeout(id);
    }
    window.scrollTo({ top: 0, behavior: "instant" as ScrollBehavior });
  }, [route]);

  /** In-page `?scroll=` navigation while staying on the same page */
  useEffect(() => {
    const onHash = () => {
      const target = scrollTargetFromHash();
      if (target) {
        document.getElementById(target)?.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    };
    window.addEventListener("hashchange", onHash);
    return () => window.removeEventListener("hashchange", onHash);
  }, []);

  const Page = PAGES[route];

  return (
    <div className="app-shell" style={{ display: "flex", flexDirection: "column", minHeight: "100vh" }}>
      <Header route={route} />
      <main id="main-content">
        <Page />
      </main>
      <Footer />
      <BackToTop />
    </div>
  );
}

export default function App() {
  return (
    <AppProvider>
      <Shell />
    </AppProvider>
  );
}
