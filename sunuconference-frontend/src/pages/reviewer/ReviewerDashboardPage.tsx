import { Link } from "react-router-dom";
import { FileText } from "lucide-react";
import { EmptyState, Spinner } from "../../app/components/common/primitives";
import { listAssignedSubmissions } from "../../lib/reviewer-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDate } from "../../lib/date-format";
import type { ReviewerAssignment } from "../../lib/types";
import { useEffect, useState } from "react";

const NAVY = "#1B2A4A";
const BLUE = "#2E6FA6";
const BLUE_DARK = "#1F4E6E";
const TEXT_SOFT = "#5B6B84";
const HAIRLINE = "#E1E5E9";

const STATUS_META: Record<string, { label: string; bg: string; text: string }> = {
  not_started: { label: "À évaluer", bg: "#EAF3FB", text: "#1F4E6E" },
  draft: { label: "Brouillon", bg: "#E7ECF3", text: "#3C4A63" },
  submitted: { label: "Rapport envoyé", bg: "#E1F3E7", text: "#2F7A4F" },
  conflict_declared: { label: "Conflit déclaré", bg: "#FBE3DE", text: "#B4472F" },
};

function assignmentStatus(a: ReviewerAssignment): string {
  return a.review?.status ?? "not_started";
}

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

export function ReviewerDashboardPage() {
  const [assignments, setAssignments] = useState<ReviewerAssignment[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listAssignedSubmissions()
      .then(setAssignments)
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger vos assignations.")));
  }, []);

  const total = assignments?.length ?? 0;
  const submittedCount = assignments?.filter((a) => assignmentStatus(a) === "submitted").length ?? 0;
  const pendingCount = assignments?.filter((a) => assignmentStatus(a) === "not_started" || assignmentStatus(a) === "draft").length ?? 0;

  return (
    <div>
      <div className="mb-6">
        <div
          className="text-[11px] font-medium uppercase tracking-wider mb-1.5"
          style={{ color: BLUE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
        >
          Dossier — Relecteur
        </div>
        <h1 className="text-2xl font-semibold" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
          Articles assignés
        </h1>
        <div className="text-[12.5px] mt-1" style={{ color: TEXT_SOFT }}>
          Vos évaluations en cours
        </div>
      </div>

      {error && <p className="text-sm text-[#B4472F] bg-[#FBE3DE] rounded-sm px-3 py-2 mb-4">{error}</p>}

      {!assignments && !error ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : (
        <>
          <div className="grid grid-cols-3 gap-3.5 mb-6">
            <StatCell value={total} label="Articles assignés" />
            <StatCell value={submittedCount} label="Évaluations rendues" />
            <StatCell value={pendingCount} label="En attente" />
          </div>

          {assignments && assignments.length === 0 ? (
            <EmptyState
              icon={FileText}
              title="Aucune assignation pour le moment"
              desc="Le PC Chair ne vous a pas encore assigné d'article à évaluer."
            />
          ) : (
            <div className="bg-white" style={{ borderTop: `3px solid ${BLUE}`, boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
              <div className="px-5 py-3.5 border-b" style={{ borderColor: HAIRLINE }}>
                <span className="text-sm font-semibold" style={{ color: NAVY }}>
                  Mes assignations
                </span>
              </div>
              <div>
                {assignments?.map((a, i) => {
                  const meta = STATUS_META[assignmentStatus(a)];
                  return (
                    <Link
                      key={a.id}
                      to={`/relecteur/soumissions/${a.id}`}
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
                          {a.submission?.title}
                        </p>
                        <p
                          className="text-[11.5px] mt-1"
                          style={{ color: TEXT_SOFT, fontFamily: "'IBM Plex Mono', monospace" }}
                        >
                          {a.submission?.topic?.name} · assigné le {formatDate(a.assigned_at)}
                        </p>
                      </div>
                      <span
                        className="inline-flex items-center px-2.5 py-1 rounded-sm text-[11px] font-semibold"
                        style={{ backgroundColor: meta.bg, color: meta.text }}
                      >
                        {meta.label}
                      </span>
                    </Link>
                  );
                })}
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}
