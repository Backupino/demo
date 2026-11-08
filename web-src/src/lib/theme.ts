/**
 * theme.ts — Dark/Light theme system
 * 1. Check localStorage for `theme`
 * 2. Fall back to prefers-color-scheme
 * 3. Apply `data-theme` on <html> and persist the choice
 */

export type Theme = "light" | "dark";

const THEME_KEY = "theme";

export function getInitialTheme(): Theme {
  try {
    const saved = localStorage.getItem(THEME_KEY);
    if (saved === "light" || saved === "dark") return saved;
  } catch {
    /* private mode */
  }
  if (typeof window !== "undefined" && window.matchMedia) {
    return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
  }
  return "light";
}

export function applyTheme(theme: Theme) {
  document.documentElement.setAttribute("data-theme", theme);
  const metaTheme = document.querySelector('meta[name="theme-color"]');
  if (metaTheme) metaTheme.setAttribute("content", theme === "dark" ? "#0f172a" : "#2563eb");
}

export function toggleTheme(current: Theme): Theme {
  const next: Theme = current === "dark" ? "light" : "dark";
  applyTheme(next);
  try {
    localStorage.setItem(THEME_KEY, next);
  } catch {
    /* no-op */
  }
  window.dispatchEvent(new CustomEvent<Theme>("backupino:theme", { detail: next }));
  return next;
}
