import * as React from "react";
import { ChevronLeft, X, CheckCircle } from "lucide-react";

const NAVY = "#1B2A4A";
const BLUE = "#2E6FA6";
const BLUE_DARK = "#1F4E6E";
const BLUE_LIGHT = "#EAF3FB";
const TEXT_SOFT = "#5B6B84";
const HAIRLINE = "#E1E5E9";

export function Btn({
  children,
  variant = "primary",
  size = "md",
  onClick,
  className = "",
  style: s = {},
  disabled = false,
  type = "button",
}: {
  children: React.ReactNode;
  variant?: "primary" | "secondary" | "ghost" | "danger" | "success";
  size?: "sm" | "md" | "lg";
  onClick?: () => void;
  className?: string;
  style?: React.CSSProperties;
  disabled?: boolean;
  type?: "button" | "submit";
}) {
  const sz = { sm: "px-3 py-1.5 text-xs gap-1.5", md: "px-4 py-2 text-sm gap-2", lg: "px-6 py-3 text-base gap-2" }[size];
  const vr = {
    primary: "bg-[#2E6FA6] text-white hover:bg-[#1F4E6E] active:bg-[#163A54]",
    secondary: "bg-white text-[#1B2A4A] border border-[#E1E5E9] hover:bg-[#EAF3FB]",
    ghost: "text-[#5B6B84] hover:bg-[#EAF3FB]",
    danger: "bg-[#FBE3DE] text-[#B4472F] border border-[#F0C4B8] hover:bg-[#F7D3C8]",
    success: "bg-[#E1F3E7] text-[#2F7A4F] border border-[#C3E5CE] hover:bg-[#D2ECDB]",
  }[variant];
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      style={{ borderRadius: 2, ...s }}
      className={`inline-flex items-center font-semibold transition-all cursor-pointer ${sz} ${vr} ${
        disabled ? "opacity-40 cursor-not-allowed" : ""
      } ${className}`}
    >
      {children}
    </button>
  );
}

export function FInput({
  label,
  placeholder,
  type = "text",
  value,
  defaultValue,
  onChange,
  hint,
  error,
  required,
  disabled,
  className = "",
  name,
  autoComplete,
}: {
  label?: string;
  placeholder?: string;
  type?: string;
  value?: string;
  defaultValue?: string;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  hint?: string;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  className?: string;
  name?: string;
  autoComplete?: string;
}) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-sm font-medium text-[#1B2A4A] mb-1.5">
          {label}
          {required && <span className="text-[#B4472F]"> *</span>}
        </label>
      )}
      <input
        type={type}
        name={name}
        value={value}
        defaultValue={defaultValue}
        onChange={onChange}
        placeholder={placeholder}
        required={required}
        disabled={disabled}
        autoComplete={autoComplete}
        style={{ borderRadius: 2 }}
        className={`w-full px-3 py-2 text-sm bg-white border focus:outline-none focus:ring-2 placeholder:text-[#A9B4C4] disabled:bg-[#F4F6F8] disabled:text-[#9CA3AF] disabled:cursor-not-allowed ${
          error
            ? "border-[#F0C4B8] focus:ring-[#B4472F]/15 focus:border-[#B4472F]/40"
            : "border-[#D3E4F2] focus:ring-[#2E6FA6]/15 focus:border-[#2E6FA6]/50"
        }`}
      />
      {error ? (
        <p className="text-[11px] text-[#B4472F] mt-1">{error}</p>
      ) : (
        hint && <p className="text-[11px] text-[#9CA3AF] mt-1">{hint}</p>
      )}
    </div>
  );
}

export function FTextarea({
  label,
  placeholder,
  rows = 4,
  value,
  defaultValue,
  onChange,
  hint,
  error,
  required,
  className = "",
  name,
}: {
  label?: string;
  placeholder?: string;
  rows?: number;
  value?: string;
  defaultValue?: string;
  onChange?: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
  hint?: string;
  error?: string;
  required?: boolean;
  className?: string;
  name?: string;
}) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-sm font-medium text-[#1B2A4A] mb-1.5">
          {label}
          {required && <span className="text-[#B4472F]"> *</span>}
        </label>
      )}
      <textarea
        name={name}
        placeholder={placeholder}
        rows={rows}
        value={value}
        defaultValue={defaultValue}
        onChange={onChange}
        required={required}
        style={{ borderRadius: 2 }}
        className={`w-full px-3 py-2 text-sm bg-white border focus:outline-none focus:ring-2 placeholder:text-[#A9B4C4] resize-none ${
          error ? "border-[#F0C4B8] focus:ring-[#B4472F]/15" : "border-[#D3E4F2] focus:ring-[#2E6FA6]/15"
        }`}
      />
      {error ? (
        <p className="text-[11px] text-[#B4472F] mt-1">{error}</p>
      ) : (
        hint && <p className="text-[11px] text-[#9CA3AF] mt-1">{hint}</p>
      )}
    </div>
  );
}

export function FSelect({
  label,
  options,
  value,
  onChange,
  className = "",
  name,
  required,
}: {
  label?: string;
  options: { label: string; value: string }[];
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLSelectElement>) => void;
  className?: string;
  name?: string;
  required?: boolean;
}) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-sm font-medium text-[#1B2A4A] mb-1.5">
          {label}
          {required && <span className="text-[#B4472F]"> *</span>}
        </label>
      )}
      <select
        name={name}
        value={value}
        onChange={onChange}
        required={required}
        style={{ borderRadius: 2 }}
        className="w-full px-3 py-2 text-sm bg-white border border-[#D3E4F2] focus:outline-none focus:ring-2 focus:ring-[#2E6FA6]/15"
      >
        {options.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
    </div>
  );
}

export function FFileInput({
  label,
  onChange,
  accept = "application/pdf",
  hint,
  error,
  required,
  className = "",
  fileName,
}: {
  label?: string;
  onChange: (file: File | null) => void;
  accept?: string;
  hint?: string;
  error?: string;
  required?: boolean;
  className?: string;
  fileName?: string | null;
}) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-sm font-medium text-[#1B2A4A] mb-1.5">
          {label}
          {required && <span className="text-[#B4472F]"> *</span>}
        </label>
      )}
      <input
        type="file"
        accept={accept}
        required={required}
        onChange={(e) => onChange(e.target.files?.[0] ?? null)}
        className="w-full text-sm text-[#5B6B84] file:mr-3 file:py-1.5 file:px-3 file:border-0 file:text-xs file:font-semibold file:bg-[#EAF3FB] file:text-[#1F4E6E] hover:file:bg-[#D3E4F2] file:cursor-pointer cursor-pointer"
      />
      {fileName && <p className="text-[11px] text-[#2F7A4F] mt-1">Fichier sélectionné : {fileName}</p>}
      {error ? (
        <p className="text-[11px] text-[#B4472F] mt-1">{error}</p>
      ) : (
        hint && <p className="text-[11px] text-[#9CA3AF] mt-1">{hint}</p>
      )}
    </div>
  );
}

/** Carte "dossier" : fond blanc, fine bordure supérieure bleue — signature de l'identité SunuConference. */
export function Card({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return (
    <div
      className={`bg-white ${className}`}
      style={{ borderTop: `3px solid ${BLUE}`, boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}
    >
      {children}
    </div>
  );
}

export function StatCard({
  label,
  value,
  delta,
  icon: Icon,
  color,
}: {
  label: string;
  value: string | number;
  delta?: string;
  icon: React.ElementType;
  color?: string;
}) {
  const c = color ?? BLUE;
  return (
    <div className="bg-white p-4" style={{ borderTop: `3px solid ${c}`, boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
      <div className="flex items-start justify-between">
        <div>
          <p
            className="text-[10.5px] font-medium uppercase tracking-wider mb-1"
            style={{ color: TEXT_SOFT, fontFamily: "'IBM Plex Mono', monospace" }}
          >
            {label}
          </p>
          <p
            className="text-2xl font-medium leading-none"
            style={{ color: NAVY, fontFamily: "'IBM Plex Mono', monospace" }}
          >
            {value}
          </p>
          {delta && (
            <p className="text-xs mt-1.5" style={{ color: TEXT_SOFT }}>
              {delta}
            </p>
          )}
        </div>
        <div style={{ backgroundColor: c + "18" }} className="p-2 shrink-0">
          <Icon size={16} style={{ color: c }} />
        </div>
      </div>
    </div>
  );
}

export function PageHeader({
  title,
  subtitle,
  back,
  onBack,
  action,
  eyebrow,
}: {
  title: string;
  subtitle?: string;
  back?: string;
  onBack?: () => void;
  action?: React.ReactNode;
  eyebrow?: string;
}) {
  return (
    <div className="mb-6">
      {back && (
        <button
          onClick={onBack}
          className="flex items-center gap-1.5 text-sm mb-3 transition-colors"
          style={{ color: TEXT_SOFT }}
        >
          <ChevronLeft size={14} /> {back}
        </button>
      )}
      <div className="flex items-start justify-between gap-4">
        <div>
          {eyebrow && (
            <div
              className="text-[11px] font-medium uppercase tracking-wider mb-1.5"
              style={{ color: BLUE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
            >
              {eyebrow}
            </div>
          )}
          <h1 className="text-2xl font-semibold" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
            {title}
          </h1>
          {subtitle && (
            <p className="text-[12.5px] mt-1" style={{ color: TEXT_SOFT }}>
              {subtitle}
            </p>
          )}
        </div>
        {action}
      </div>
    </div>
  );
}

export function Modal({
  title,
  children,
  onClose,
  wide = false,
}: {
  title: string;
  children: React.ReactNode;
  onClose: () => void;
  wide?: boolean;
}) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-[#0F1522]/45 backdrop-blur-sm" onClick={onClose} />
      <div
        className={`relative bg-white shadow-2xl w-full ${wide ? "max-w-2xl" : "max-w-md"} max-h-[88vh] overflow-y-auto`}
        style={{ borderTop: `3px solid ${BLUE}` }}
      >
        <div className="flex items-center justify-between px-5 py-4 border-b" style={{ borderColor: HAIRLINE }}>
          <h2 className="text-base font-semibold" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
            {title}
          </h2>
          <button onClick={onClose} className="p-1.5 hover:bg-[#EAF3FB] transition-colors" style={{ color: TEXT_SOFT }}>
            <X size={14} />
          </button>
        </div>
        <div className="p-5">{children}</div>
      </div>
    </div>
  );
}

export function EmptyState({
  icon: Icon,
  title,
  desc,
  action,
}: {
  icon: React.ElementType;
  title: string;
  desc: string;
  action?: React.ReactNode;
}) {
  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <div className="w-14 h-14 flex items-center justify-center mb-4" style={{ background: BLUE_LIGHT }}>
        <Icon size={22} style={{ color: BLUE }} />
      </div>
      <h3 className="text-sm font-semibold mb-1" style={{ color: NAVY }}>
        {title}
      </h3>
      <p className="text-xs mb-4 max-w-xs leading-relaxed" style={{ color: TEXT_SOFT }}>
        {desc}
      </p>
      {action}
    </div>
  );
}

export function StepBar({ steps, current, color }: { steps: string[]; current: number; color?: string }) {
  const c = color ?? BLUE;
  return (
    <div className="flex items-center mb-8">
      {steps.map((s, i) => (
        <div key={s} className="flex items-center flex-1">
          <div className="flex flex-col items-center shrink-0">
            <div
              style={
                i + 1 === current
                  ? { backgroundColor: c, color: "white" }
                  : i + 1 < current
                    ? { backgroundColor: BLUE_DARK, color: "white" }
                    : { backgroundColor: "#EDF0F2", color: TEXT_SOFT }
              }
              className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-all"
            >
              {i + 1 < current ? <CheckCircle size={13} /> : i + 1}
            </div>
            <span
              className="text-[10px] mt-1.5 text-center leading-tight max-w-[80px]"
              style={i + 1 === current ? { color: c, fontWeight: 600 } : { color: TEXT_SOFT }}
            >
              {s}
            </span>
          </div>
          {i < steps.length - 1 && (
            <div className="flex-1 h-px mx-2 mb-4 transition-colors" style={{ background: i + 1 < current ? BLUE_DARK : HAIRLINE }} />
          )}
        </div>
      ))}
    </div>
  );
}

export function Spinner({ className = "" }: { className?: string }) {
  return (
    <div
      className={`inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent ${className}`}
      role="status"
      aria-label="Chargement"
      style={{ color: "#2E6FA6" }}
    />
  );
}
