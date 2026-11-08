import { useEffect, useRef, useState } from "react";
import { Check, Languages } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { FlagIR, FlagUS } from "./icons";

interface LanguageSwitcherProps {
  /** Footer variant opens the menu upward. */
  dropUp?: boolean;
}

/**
 * Icon-only language switcher with a theme/RTL-aware dropdown.
 * Shared by header (drop-down) and footer (drop-up) — both write through the
 * same AppContext, so they stay perfectly in sync.
 */
export function LanguageSwitcher({ dropUp = false }: LanguageSwitcherProps) {
  const { t, lang, switchLanguage } = useApp();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const onPointerDown = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", onPointerDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      document.removeEventListener("keydown", onKey);
    };
  }, [open]);

  const options = [
    { id: "en" as const, label: t("langNames.en"), Flag: FlagUS },
    { id: "fa" as const, label: t("langNames.fa"), Flag: FlagIR },
  ];

  return (
    <div className={`lang-switcher${dropUp ? " drop-up" : ""}`} ref={rootRef}>
      <button
        type="button"
        className={`icon-btn${open ? " open" : ""}`}
        aria-label={t("actions.language")}
        aria-expanded={open}
        aria-haspopup="listbox"
        title={t("actions.language")}
        onClick={() => setOpen((v) => !v)}
      >
        <Languages size={17} />
      </button>
      <div className={`lang-menu${open ? " open" : ""}`} role="listbox" aria-label={t("actions.language")}>
        {options.map(({ id, label, Flag }) => (
          <button
            key={id}
            type="button"
            role="option"
            aria-selected={lang === id}
            className={`lang-option${lang === id ? " active" : ""}`}
            onClick={() => {
              switchLanguage(id);
              setOpen(false);
            }}
          >
            <Flag />
            <span lang={id}>{label}</span>
            {lang === id && <Check size={14} className="check" aria-hidden="true" />}
          </button>
        ))}
      </div>
    </div>
  );
}
