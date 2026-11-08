/**
 * i18n.ts — Bilingual system (English default / Persian)
 * - Loads the correct dictionary, resolves nested dot-keys
 * - Switching to FA sets <html lang="fa" dir="rtl"> and swaps to the Vazirmatn font
 * - Switching to EN sets <html lang="en" dir="ltr"> and swaps to Inter
 * - Choice persists in localStorage under `lang`; default is `en`
 */

import en from "../i18n/en.json";
import fa from "../i18n/fa.json";

export type Lang = "en" | "fa";

const LANG_KEY = "lang";
const dictionaries: Record<Lang, unknown> = { en, fa };

export function getInitialLang(): Lang {
  try {
    const saved = localStorage.getItem(LANG_KEY);
    if (saved === "en" || saved === "fa") return saved;
  } catch {
    /* no-op */
  }
  return "en";
}

export function applyLangAttributes(lang: Lang) {
  const html = document.documentElement;
  html.setAttribute("lang", lang);
  html.setAttribute("dir", lang === "fa" ? "rtl" : "ltr");
}

export function persistLang(lang: Lang) {
  try {
    localStorage.setItem(LANG_KEY, lang);
  } catch {
    /* no-op */
  }
}

/** Resolve a nested key path like "home.hero.title" against the active dictionary. */
export function resolve(lang: Lang, path: string): unknown {
  let node: unknown = dictionaries[lang];
  for (const part of path.split(".")) {
    if (node === null || typeof node !== "object") return undefined;
    node = (node as Record<string, unknown>)[part];
  }
  return node;
}

/** String translation — falls back to English, then to the key itself. */
export function translate(lang: Lang, path: string): string {
  const value = resolve(lang, path);
  if (typeof value === "string") return value;
  const fallback = resolve("en", path);
  return typeof fallback === "string" ? fallback : path;
}

/** Typed (arrays / objects) translation — falls back to English. */
export function translateRaw<T>(lang: Lang, path: string, fallback: T): T {
  const value = resolve(lang, path);
  if (value === undefined || value === null) {
    const fb = resolve("en", path);
    return fb === undefined || fb === null ? fallback : (fb as T);
  }
  return value as T;
}

/** Docs article typing shared across pages. */
export interface DocBlock {
  type: "paragraph" | "heading" | "list" | "code" | "note" | "table" | "qa" | "callout";
  text?: string;
  ordered?: boolean;
  items?: string[] | { q: string; a: string }[];
  title?: string;
  content?: string;
  head?: string[];
  rows?: string[][];
  /** Anchor id for headings (sidebar deep links). */
  id?: string;
  /** Code language for the label + highlighter. */
  lang?: string;
  /** Callout variant. */
  variant?: "info" | "warning" | "danger" | "tip" | "pro";
}

export interface DocArticle {
  title: string;
  intro: string;
  pro?: boolean;
  blocks: DocBlock[];
}

export interface DocCategory {
  id: string;
  title: string;
}

export interface DocTreeChild {
  anchor: string;
  title: string;
}

export interface DocTreeItem {
  slug: string;
  pro?: boolean;
  children?: DocTreeChild[];
}

export interface DocTreeGroup {
  id: string;
  title: string;
  items: DocTreeItem[];
}
