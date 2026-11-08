import { useState } from "react";
import type { FormEvent } from "react";
import { AlertCircle, CheckCircle2, Eye, EyeOff, Lock, LogIn, Mail } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { Logo } from "../components/Logo";
import { AuthArt } from "../components/art";
import { GithubIcon, GoogleIcon } from "../components/icons";

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export default function Login() {
  const { t } = useApp();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState<{ email?: string; password?: string }>({});
  const [message, setMessage] = useState<{ kind: "error" | "success"; text: string } | null>(null);

  const validate = (): boolean => {
    const next: typeof errors = {};
    if (!email.trim()) next.email = t("auth.errors.required");
    else if (!EMAIL_RE.test(email.trim())) next.email = t("auth.errors.invalidEmail");
    if (!password) next.password = t("auth.errors.required");
    else if (password.length < 8) next.password = t("auth.errors.shortPassword");
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    setMessage(null);
    if (validate()) {
      setMessage({ kind: "success", text: t("auth.login.success") });
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-bg" aria-hidden="true" />
      <div className="container flex-center">
        <div className="auth-frame">
          <div className="auth-card">
          <div className="auth-head">
            <Logo size={48} withWordmark={false} />
            <h1>{t("auth.login.title")}</h1>
            <p>{t("auth.login.subtitle")}</p>
          </div>

          <form className="form-grid" onSubmit={onSubmit} noValidate>
            {message && (
              <div className={`form-message ${message.kind} show`} role={message.kind === "error" ? "alert" : "status"}>
                {message.kind === "success" ? <CheckCircle2 size={17} aria-hidden="true" /> : <AlertCircle size={17} aria-hidden="true" />}
                <span>{message.text}</span>
              </div>
            )}

            <div className="form-field">
              <label htmlFor="login-email">{t("auth.login.email")}</label>
              <div className="input-wrap">
                <Mail size={16} aria-hidden="true" />
                <input
                  id="login-email"
                  type="email"
                  autoComplete="email"
                  placeholder={t("auth.login.emailPlaceholder")}
                  value={email}
                  className={errors.email ? "invalid" : ""}
                  aria-invalid={!!errors.email}
                  aria-describedby={errors.email ? "login-email-error" : undefined}
                  onChange={(e) => setEmail(e.target.value)}
                />
              </div>
              <span id="login-email-error" className={`field-error${errors.email ? " show" : ""}`}>
                <AlertCircle size={13} aria-hidden="true" /> {errors.email}
              </span>
            </div>

            <div className="form-field">
              <label htmlFor="login-password">
                {t("auth.login.password")}
                <a href={href("login")} onClick={(e) => e.preventDefault()}>
                  {t("auth.login.forgot")}
                </a>
              </label>
              <div className="input-wrap">
                <Lock size={16} aria-hidden="true" />
                <input
                  id="login-password"
                  type={showPassword ? "text" : "password"}
                  autoComplete="current-password"
                  placeholder={t("auth.login.passwordPlaceholder")}
                  value={password}
                  className={errors.password ? "invalid" : ""}
                  aria-invalid={!!errors.password}
                  aria-describedby={errors.password ? "login-password-error" : undefined}
                  onChange={(e) => setPassword(e.target.value)}
                />
                <button
                  type="button"
                  className="password-toggle"
                  onClick={() => setShowPassword((v) => !v)}
                  aria-label={showPassword ? t("auth.login.hidePassword") : t("auth.login.showPassword")}
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
              <span id="login-password-error" className={`field-error${errors.password ? " show" : ""}`}>
                <AlertCircle size={13} aria-hidden="true" /> {errors.password}
              </span>
            </div>

            <div className="form-row">
              <label className="checkbox">
                <input type="checkbox" checked={remember} onChange={(e) => setRemember(e.target.checked)} />
                <span className="box" aria-hidden="true">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.4" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                  </svg>
                </span>
                {t("auth.login.remember")}
              </label>
            </div>

            <button type="submit" className="btn btn-primary btn-block btn-lg">
              <LogIn size={17} aria-hidden="true" />
              {t("auth.login.submit")}
            </button>
          </form>

          <div className="divider">{t("auth.login.or")}</div>

          <div className="social-grid">
            <button type="button" className="social-btn">
              <GithubIcon size={17} />
              {t("auth.login.github")}
            </button>
            <button type="button" className="social-btn">
              <GoogleIcon size={17} />
              {t("auth.login.google")}
            </button>
          </div>

          <p className="auth-switch">
            {t("auth.login.noAccount")}{" "}
            <a href={href("register")}>{t("auth.login.createAccount")}</a>
          </p>
          </div>
          <aside className="auth-art" aria-hidden="true">
            <AuthArt />
          </aside>
        </div>
      </div>
    </div>
  );
}
