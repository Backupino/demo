import { createContext, useCallback, useContext, useEffect, useState } from "react";
import type { ReactNode } from "react";
import {
  applyLangAttributes,
  getInitialLang,
  persistLang,
  translate,
  translateRaw,
  type Lang,
} from "./i18n";
import { applyTheme, getInitialTheme, toggleTheme, type Theme } from "./theme";

interface AppContextValue {
  lang: Lang;
  theme: Theme;
  switchLanguage: (lang: Lang) => void;
  toggleThemeMode: () => void;
  /** String translation by dot-path key. */
  t: (key: string) => string;
  /** Raw (array/object) translation by dot-path key. */
  tr: <T>(key: string, fallback: T) => T;
}

const AppContext = createContext<AppContextValue | null>(null);

export function AppProvider({ children }: { children: ReactNode }) {
  const [lang, setLang] = useState<Lang>(() => getInitialLang());
  const [theme, setTheme] = useState<Theme>(() => getInitialTheme());

  useEffect(() => {
    applyTheme(theme);
  }, [theme]);

  useEffect(() => {
    applyLangAttributes(lang);
    persistLang(lang);
  }, [lang]);

  const switchLanguage = useCallback((next: Lang) => setLang(next), []);

  const toggleThemeMode = useCallback(() => {
    setTheme((current) => toggleTheme(current));
  }, []);

  const t = useCallback((key: string) => translate(lang, key), [lang]);
  const tr = useCallback(
    <T,>(key: string, fallback: T): T => translateRaw<T>(lang, key, fallback),
    [lang]
  );

  return (
    <AppContext.Provider value={{ lang, theme, switchLanguage, toggleThemeMode, t, tr }}>
      {children}
    </AppContext.Provider>
  );
}

export function useApp(): AppContextValue {
  const ctx = useContext(AppContext);
  if (!ctx) throw new Error("useApp must be used within AppProvider");
  return ctx;
}
