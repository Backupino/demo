/**
 * router.ts — Tiny hash router mapping friendly routes to pages.
 * Keeps the multi-page experience (`#/about`, `#/pricing`, …) inside a
 * single-file build while staying shareable and bookmarkable.
 */

import { useEffect, useState } from "react";

export type Route =
  | "home"
  | "about"
  | "pricing"
  | "pro"
  | "privacy"
  | "docs"
  | "login"
  | "register";

export const ROUTES: Route[] = ["home", "about", "pricing", "pro", "privacy", "docs", "login", "register"];

export function href(route: Route): string {
  return route === "home" ? "#/" : `#/${route}`;
}

export function parseHash(): Route {
  const raw = window.location.hash.replace(/^#\/?/, "").split(/[?#]/)[0];
  const first = raw.split("/")[0] as Route;
  return ROUTES.includes(first) ? first : "home";
}

export function useRoute(): Route {
  const [route, setRoute] = useState<Route>(() => parseHash());
  useEffect(() => {
    const onHashChange = () => setRoute(parseHash());
    window.addEventListener("hashchange", onHashChange);
    return () => window.removeEventListener("hashchange", onHashChange);
  }, []);
  return route;
}

/** Read an in-page scroll target from the hash query (e.g. `#/?scroll=features`). */
export function scrollTargetFromHash(): string | null {
  const match = window.location.hash.match(/[?&]scroll=([\w-]+)/);
  return match ? match[1] : null;
}

export function navigate(route: Route) {
  window.location.hash = href(route).slice(1);
}
