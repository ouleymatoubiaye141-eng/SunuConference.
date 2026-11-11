import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { CheckCircle, Clock, FileText, Grid, Plus, Users, Calendar } from "lucide-react";
import { EmptyState, Spinner } from "../../app/components/common/primitives";
import { getMyConference, listChairSubmissions, listChairTopics, listConferenceReviewers } from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDateRange } from "../../lib/date-format";
import { ChairConferenceFormPage } from "./ChairConferenceFormPage";
import type { Conference } from "../../lib/types";

const NAVY = "#1B2A4A";
const BLUE = "#2E6FA6";
const BLUE_DARK = "#1F4E6E";
const TEXT_SOFT = "#5B6B84";
const HAIRLINE = "#E1E5E9";

function StatCell({ value, label }: { value: React.ReactNode; label: string }) {
  return (
    <div className="bg-white p-4" style={{ borderTop: `3px solid ${BLUE}`, boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
      <div className="text-xl font-medium" style={{ fontFamily: "'IBM Plex Mono', monospace", color: NAVY }}>
        {value}
      </div>
      <div className="text-[10.5px] uppercase tracking-wider mt-1" style={{ color: TEXT_SOFT }}>
        {label}
      </div>
    </div>
  );
}

function Shortcut({ to, icon: Icon, title, desc }: { to: string; icon: React.ElementType; title: string; desc: string }) {
  return (
    <Link
      to={to}
      className="block bg-white rounded-sm p-5 border transition-colors"
      style={{ borderColor: HAIRLINE }}
      onMouseEnter={(e) => (e.currentTarget.style.borderColor = BLUE)}
      onMouseLeave={(e) => (e.currentTarget.style.borderColor = HAIRLINE)}
    >
      <Icon size={17} style={{ color: BLUE, marginBottom: 8 }} />
      <p className="text-[13.5px] font-semibold" style={{ color: NAVY }}>
        {title}
      </p>
      <p className="text-[11.5px] mt-1 leading-relaxed" style={{ color: TEXT_SOFT }}>
        {desc}
      </p>
    </Link>
  );
}

function Eyebrow({ children }: { children: React.ReactNode }) {
  return (
    <div
      className="text-[11px] font-medium uppercase tracking-wider mb-1.5"
      style={{ color: BLUE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
    >
      {children}
    </div>
  );
}

export function ChairDashboardPage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [error, setError] = useState<string | null>(null);
  const [topicsCount, setTopicsCount] = useState<number | null>(null);
  const [reviewersCount, setReviewersCount] = useState<number | null>(null);
  const [submissionsCount, setSubmissionsCount] = useState<number | null>(null);

  const load = () => {
    getMyConference()
      .then((c) => {
        setConference(c);
        if (c && c.status === "validated") {
          listChairTopics(c.id).then((t) => setTopicsCount(t.length)).catch(() => setTopicsCount(null));
          listConferenceReviewers(c.id).then((r) => setReviewersCount(r.length)).catch(() => setReviewersCount(null));
          listChairSubmissions(c.id).then((s) => setSubmissionsCount(s.length)).catch(() => setSubmissionsCount(null));
        }
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger votre conférence.")));
  };

  useEffect(load, []);

  if (error) {
    return <p className="text-sm text-[#B4472F] bg-[#FBE3DE] rounded-sm px-3 py-2">{error}</p>;
  }

  if (conference === undefined) {
    return (
      <div className="flex justify-center py-16">
        <Spinner />
      </div>
    );
  }

  if (conference === null) {
    return (
      <div>
        <Eyebrow>Dossier — PC Chair</Eyebrow>
        <h1 className="text-2xl font-semibold mb-6" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
          Vue d'ensemble
        </h1>
        <EmptyState
          icon={Plus}
          title="Vous n'avez pas encore de conférence"
          desc="Créez votre conférence pour commencer : elle sera soumise à validation par un administrateur."
          action={
            <Link
              to="/chair/creer"
              className="inline-flex items-center gap-2 px-4 py-2 text-white text-sm font-semibold rounded-sm transition-colors"
              style={{ background: BLUE }}
            >
              <Plus size={14} /> Créer ma conférence
            </Link>
          }
        />
      </div>
    );
  }

  if (conference.status === "pending") {
    return (
      <div>
        <Eyebrow>Dossier — PC Chair</Eyebrow>
        <h1 className="text-2xl font-semibold mb-6" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
          Vue d'ensemble
        </h1>
        <div className="bg-white p-6" style={{ borderTop: "3px solid #B8842E", boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
          <div className="flex items-start gap-3">
            <Clock size={18} style={{ color: "#B8842E", flexShrink: 0, marginTop: 2 }} />
            <div>
              <h2 className="text-sm font-bold" style={{ color: NAVY }}>
                {conference.name}
              </h2>
              <p className="text-sm mt-1 leading-relaxed" style={{ color: TEXT_SOFT }}>
                Votre conférence est en attente de validation par un administrateur. Vous pourrez créer des topics,
                inviter des relecteurs et gérer les soumissions une fois qu'elle sera validée.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (conference.status === "rejected") {
    return (
      <div className="space-y-5">
        <div>
          <Eyebrow>Dossier — PC Chair</Eyebrow>
          <h1 className="text-2xl font-semibold" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
            Vue d'ensemble
          </h1>
        </div>
        <div className="bg-white p-5" style={{ borderTop: "3px solid #B4472F", boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
          <h2 className="text-sm font-bold" style={{ color: "#B4472F" }}>
            Conférence rejetée
          </h2>
          {conference.rejection_reason && (
            <p className="text-sm mt-1 leading-relaxed" style={{ color: TEXT_SOFT }}>
              {conference.rejection_reason}
            </p>
          )}
          <p className="text-xs mt-2" style={{ color: "#9CA3AF" }}>
            Modifiez les informations ci-dessous puis enregistrez pour la resoumettre à validation.
          </p>
        </div>
        <ChairConferenceFormPage conference={conference} onSaved={load} />
      </div>
    );
  }

  // status === "validated"
  return (
    <div>
      <div className="flex items-start justify-between mb-6 gap-4">
        <div>
          <Eyebrow>Conférence active</Eyebrow>
          <h1 className="text-[22px] font-semibold leading-tight" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
            {conference.name}
          </h1>
          <p className="text-[12.5px] mt-1" style={{ color: TEXT_SOFT }}>
            {formatDateRange(conference.start_date, conference.end_date)}
          </p>
        </div>
        <Link
          to="/chair/dates-limites"
          className="px-4 py-2 text-[12.5px] font-semibold rounded-sm border bg-white shrink-0"
          style={{ borderColor: HAIRLINE, color: BLUE_DARK }}
        >
          Paramètres
        </Link>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-6">
        <StatCell value={topicsCount ?? "—"} label="Topics" />
        <StatCell value={reviewersCount ?? "—"} label="Relecteurs" />
        <StatCell value={submissionsCount ?? "—"} label="Soumissions" />
        <StatCell
          value={
            <span
              className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-sm text-[10.5px] font-bold uppercase tracking-wide"
              style={{ background: "#E1F3E7", color: "#2F7A4F" }}
            >
              <CheckCircle size={11} /> Validée
            </span>
          }
          label="Statut"
        />
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
        <Shortcut
          to="/chair/assignations"
          icon={Grid}
          title="Topics & assignations"
          desc="Gérer les pistes thématiques et assigner des relecteurs par compétence."
        />
        <Shortcut to="/chair/relecteurs" icon={Users} title="Relecteurs" desc="Inviter des relecteurs et gérer leurs accès." />
        <Shortcut
          to="/chair/soumissions"
          icon={FileText}
          title="Soumissions"
          desc="Assigner des relecteurs et enregistrer les décisions."
        />
        <Shortcut
          to="/chair/sessions"
          icon={Calendar}
          title="Sessions & programme"
          desc="Composer les sessions puis publier le programme."
        />
      </div>
    </div>
  );
}
