import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { FileText, Plus } from "lucide-react";
import { EmptyState, Spinner } from "../../app/components/common/primitives";
import { StatusBadge } from "../../app/components/common/StatusBadge";
import { listMySubmissions } from "../../lib/submission-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDate } from "../../lib/date-format";
import type { Submission } from "../../lib/types";

const NAVY = "#1B2A4A";
const BLUE = "#2E6FA6";
const BLUE_DARK = "#1F4E6E";
const TEXT_SOFT = "#5B6B84";
const HAIRLINE = "#E1E5E9";

function StatCell({ value, label }: { value: number | string; label: string }) {
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

export function AuthorDashboardPage() {
  const [submissions, setSubmissions] = useState<Submission[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listMySubmissions()
      .then(setSubmissions)
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger vos soumissions.")));
  }, []);

  const counts = submissions
    ? {
        total: submissions.length,
        review: submissions.filter((s) => s.status === "under_review").length,
        accepted: submissions.filter((s) => s.status === "accepted" || s.status === "camera_ready_submitted").length,
        revision: submissions.filter((s) => s.status === "revision_required").length,
      }
    : null;

  return (
    <div>
      <div className="flex items-end justify-between gap-4 mb-6">
        <div>
          <div
            className="text-[11px] font-medium uppercase tracking-wider mb-1.5"
            style={{ color: BLUE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
          >
            Dossier — Auteur
          </div>
          <h1 className="text-2xl font-semibold" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
            Mes soumissions
          </h1>
          {submissions && (
            <div className="text-[12.5px] mt-1" style={{ color: TEXT_SOFT }}>
              {submissions.length} soumission{submissions.length > 1 ? "s" : ""}
            </div>
          )}
        </div>
        <Link
          to="/auteur/nouvelle-soumission"
          className="inline-flex items-center gap-2 px-4 py-2.5 text-white text-sm font-semibold rounded-sm transition-colors"
          style={{ background: BLUE }}
          onMouseEnter={(e) => (e.currentTarget.style.background = BLUE_DARK)}
          onMouseLeave={(e) => (e.currentTarget.style.background = BLUE)}
        >
          <Plus size={14} /> Nouvelle soumission
        </Link>
      </div>

      {error && <p className="text-sm text-[#B4472F] bg-[#FBE3DE] rounded-sm px-3 py-2 mb-4">{error}</p>}

      {!submissions && !error ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : submissions && submissions.length === 0 ? (
        <EmptyState
          icon={FileText}
          title="Aucune soumission pour le moment"
          desc="Déposez votre premier article pour une conférence ouverte aux soumissions."
          action={
            <Link
              to="/auteur/nouvelle-soumission"
              className="inline-flex items-center gap-2 px-4 py-2 text-white text-sm font-semibold rounded-sm transition-colors"
              style={{ background: BLUE }}
            >
              <Plus size={14} /> Soumettre un article
            </Link>
          }
        />
      ) : (
        <>
          {counts && (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-6">
              <StatCell value={counts.total} label="Total" />
              <StatCell value={counts.review} label="En relecture" />
              <StatCell value={counts.accepted} label="Acceptées" />
              <StatCell value={counts.revision} label="Révision demandée" />
            </div>
          )}

          <div className="bg-white" style={{ borderTop: `3px solid ${BLUE}`, boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
            <div className="px-5 py-3.5 border-b" style={{ borderColor: HAIRLINE }}>
              <span className="text-sm font-semibold" style={{ color: NAVY }}>
                Mes soumissions
              </span>
            </div>
            <div>
              {submissions?.map((s, i) => (
                <Link
                  key={s.id}
                  to={`/auteur/soumissions/${s.id}`}
                  className="flex items-center gap-4 px-5 py-4 border-b last:border-b-0 transition-colors hover:bg-[#EAF3FB]"
                  style={{ borderColor: HAIRLINE }}
                >
                  <span
                    className="text-[11px] w-6 shrink-0"
                    style={{ color: BLUE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
                  >
                    {String(i + 1).padStart(2, "0")}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="text-[13.5px] font-semibold truncate" style={{ color: NAVY }}>
                      {s.title}
                    </p>
                    <p
                      className="text-[11.5px] mt-1"
                      style={{ color: TEXT_SOFT, fontFamily: "'IBM Plex Mono', monospace" }}
                    >
                      {s.conference?.acronym ?? s.conference?.name} · {s.topic?.name} · soumis le {formatDate(s.submitted_at)}
                    </p>
                  </div>
                  <StatusBadge status={s.status} />
                </Link>
              ))}
            </div>
          </div>
        </>
      )}
    </div>
  );
}
