import { useState } from "react";
import type { FormEvent } from "react";
import { AlertCircle, CheckCircle2, Eye, EyeOff, Lock, Mail, User, UserPlus } from "lucide-react";
import { useApp } from "../lib/AppContext";
import { href } from "../lib/router";
import { Logo } from "../components/Logo";
import { AuthArt } from "../components/art";
import { GithubIcon, GoogleIcon } from "../components/icons";

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

/** Password strength: 0 empty → 1 weak → 2 medium → 3 strong. */
function passwordStrength(pw: string): number {
  if (!pw) return 0;
  let score = 0;
  if (pw.length >= 8) score += 1;
  if (pw.length >= 8 && /[a-z]/.test(pw) && /[A-Z]/.test(pw) && /\d/.test(pw)) score += 1;
  if (pw.length >= 10 && /[^a-zA-Z0-9]/.test(pw)) score += 1;
  return Math.max(1, score);
}

interface FieldErrors {
  name?: string;
  email?: string;
  password?: string;
  confirm?: string;
  agree?: string;
}

export default function Register() {
  const { t, tr } = useApp();
  const strengthNames = tr<string[]>("auth.register.strength", ["Weak", "Medium", "Strong"]);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [agree, setAgree] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [message, setMessage] = useState<{ kind: "error" | "success"; text: string } | null>(null);

  const strength = passwordStrength(password);

  const validate = (): boolean => {
    const next: FieldErrors = {};
    if (!name.trim()) next.name = t("auth.errors.required");
    if (!email.trim()) next.email = t("auth.errors.required");
    else if (!EMAIL_RE.test(email.trim())) next.email = t("auth.errors.invalidEmail");
    if (!password) next.password = t("auth.errors.required");
    else if (password.length < 8) next.password = t("auth.errors.shortPassword");
    else if (strength < 2) next.password = t("auth.errors.weakPassword");
    if (!confirm) next.confirm = t("auth.errors.required");
    else if (confirm !== password) next.confirm = t("auth.errors.mismatch");
    if (!agree) next.agree = t("auth.errors.mustAgree");
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    setMessage(null);
    if (validate()) {
      setMessage({ kind: "success", text: t("auth.register.success") });
    }
  };

  const fieldError = (key: keyof FieldErrors, id: string) => (
    <span id={id} className={`field-error${errors[key] ? " show" : ""}`}>
      <AlertCircle size={13} aria-hidden="true" /> {errors[key]}
    </span>
  );

  return (
    <div className="auth-page">
      <div className="auth-bg" aria-hidden="true" />
      <div className="container flex-center">
        <div className="auth-frame">
          <div className="auth-card">
          <div className="auth-head">
            <Logo size={48} withWordmark={false} />
            <h1>{t("auth.register.title")}</h1>
            <p>{t("auth.register.subtitle")}</p>
          </div>

          <form className="form-grid" onSubmit={onSubmit} noValidate>
            {message && (
              <div className={`form-message ${message.kind} show`} role={message.kind === "error" ? "alert" : "status"}>
                {message.kind === "success" ? <CheckCircle2 size={17} aria-hidden="true" /> : <AlertCircle size={17} aria-hidden="true" />}
                <span>{message.text}</span>
              </div>
            )}

            <div className="form-field">
              <label htmlFor="reg-name">{t("auth.register.name")}</label>
              <div className="input-wrap">
                <User size={16} aria-hidden="true" />
                <input
                  id="reg-name"
                  type="text"
                  autoComplete="name"
                  placeholder={t("auth.register.namePlaceholder")}
                  value={name}
                  className={errors.name ? "invalid" : ""}
                  aria-invalid={!!errors.name}
                  aria-describedby={errors.name ? "reg-name-error" : undefined}
                  onChange={(e) => setName(e.target.value)}
                />
              </div>
              {fieldError("name", "reg-name-error")}
            </div>

            <div className="form-field">
              <label htmlFor="reg-email">{t("auth.register.email")}</label>
              <div className="input-wrap">
                <Mail size={16} aria-hidden="true" />
                <input
                  id="reg-email"
                  type="email"
                  autoComplete="email"
                  placeholder={t("auth.register.emailPlaceholder")}
                  value={email}
                  className={errors.email ? "invalid" : ""}
                  aria-invalid={!!errors.email}
                  aria-describedby={errors.email ? "reg-email-error" : undefined}
                  onChange={(e) => setEmail(e.target.value)}
                />
              </div>
              {fieldError("email", "reg-email-error")}
            </div>

            <div className="form-field">
              <label htmlFor="reg-password">{t("auth.register.password")}</label>
              <div className="input-wrap">
                <Lock size={16} aria-hidden="true" />
                <input
                  id="reg-password"
                  type={showPassword ? "text" : "password"}
                  autoComplete="new-password"
                  placeholder={t("auth.register.passwordPlaceholder")}
                  value={password}
                  className={errors.password ? "invalid" : ""}
                  aria-invalid={!!errors.password}
                  aria-describedby={`reg-password-help${errors.password ? " reg-password-error" : ""}`}
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
              <div className="strength" data-level={strength} id="reg-password-help" aria-live="polite">
                <div className="strength-track" aria-hidden="true">
                  <span className="strength-seg" />
                  <span className="strength-seg" />
                  <span className="strength-seg" />
                </div>
                <span className="strength-label">
                  {t("auth.register.strengthLabel")}: {strength > 0 ? strengthNames[strength - 1] : "—"}
                </span>
              </div>
              {fieldError("password", "reg-password-error")}
            </div>

            <div className="form-field">
              <label htmlFor="reg-confirm">{t("auth.register.confirm")}</label>
              <div className="input-wrap">
                <Lock size={16} aria-hidden="true" />
                <input
                  id="reg-confirm"
                  type={showPassword ? "text" : "password"}
                  autoComplete="new-password"
                  placeholder={t("auth.register.confirmPlaceholder")}
                  value={confirm}
                  className={errors.confirm ? "invalid" : ""}
                  aria-invalid={!!errors.confirm}
                  aria-describedby={errors.confirm ? "reg-confirm-error" : undefined}
                  onChange={(e) => setConfirm(e.target.value)}
                />
              </div>
              {fieldError("confirm", "reg-confirm-error")}
            </div>

            <div className="form-field">
              <label className="checkbox">
                <input
                  type="checkbox"
                  checked={agree}
                  aria-invalid={!!errors.agree}
                  aria-describedby={errors.agree ? "reg-agree-error" : undefined}
                  onChange={(e) => setAgree(e.target.checked)}
                />
                <span className="box" aria-hidden="true">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3.4" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                  </svg>
                </span>
                <span>
                  {t("auth.register.agreePrefix")}{" "}
                  <a href={href("privacy")}>{t("auth.register.agreeLink")}</a>
                </span>
              </label>
              {fieldError("agree", "reg-agree-error")}
            </div>

            <button type="submit" className="btn btn-primary btn-block btn-lg">
              <UserPlus size={17} aria-hidden="true" />
              {t("auth.register.submit")}
            </button>
          </form>

          <div className="divider">{t("auth.register.or")}</div>

          <div className="social-grid">
            <button type="button" className="social-btn">
              <GithubIcon size={17} />
              {t("auth.register.github")}
            </button>
            <button type="button" className="social-btn">
              <GoogleIcon size={17} />
              {t("auth.register.google")}
            </button>
          </div>

          <p className="auth-switch">
            {t("auth.register.haveAccount")} <a href={href("login")}>{t("auth.register.signIn")}</a>
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
