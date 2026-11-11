import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { FileText } from "lucide-react";
import { EmptyState, Spinner } from "../../app/components/common/primitives";
import { StatusBadge } from "../../app/components/common/StatusBadge";
import { getMyConference, listChairSubmissions } from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import type { Conference, Submission } from "../../lib/types";

const NAVY = "#1B2A4A";
const BLUE = "#2E6FA6";
const BLUE_DARK = "#1F4E6E";
const TEXT_SOFT = "#5B6B84";
const HAIRLINE = "#E1E5E9";

export function ChairSubmissionsPage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [submissions, setSubmissions] = useState<Submission[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getMyConference()
      .then((c) => {
        setConference(c);
        if (c && c.status === "validated") {
          return listChairSubmissions(c.id).then(setSubmissions);
        }
        return undefined;
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les soumissions.")));
  }, []);

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

  if (!conference || conference.status !== "validated") {
    return <p className="text-sm" style={{ color: TEXT_SOFT }}>Cette section est disponible une fois votre conférence validée.</p>;
  }

  return (
    <div>
      <div className="mb-6">
        <div
          className="text-[11px] font-medium uppercase tracking-wider mb-1.5"
          style={{ color: BLUE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
        >
          Dossier — PC Chair
        </div>
        <h1 className="text-2xl font-semibold" style={{ color: NAVY, fontFamily: "'Zilla Slab', serif" }}>
          Soumissions
        </h1>
        {submissions && (
          <div className="text-[12.5px] mt-1" style={{ color: TEXT_SOFT }}>
            {submissions.length} soumission{submissions.length > 1 ? "s" : ""}
          </div>
        )}
      </div>

      {!submissions ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : submissions.length === 0 ? (
        <EmptyState icon={FileText} title="Aucune soumission pour le moment" desc="Les articles soumis par les auteurs apparaîtront ici." />
      ) : (
        <div className="bg-white" style={{ borderTop: `3px solid ${BLUE}`, boxShadow: "0 1px 2px rgba(20,30,45,0.06)" }}>
          <div className="px-5 py-3.5 border-b" style={{ borderColor: HAIRLINE }}>
            <span className="text-sm font-semibold" style={{ color: NAVY }}>
              Toutes les soumissions
            </span>
          </div>
          <div>
            {submissions.map((s, i) => (
              <Link
                key={s.id}
                to={`/chair/soumissions/${s.id}`}
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
                    {s.topic?.name} · {s.author?.name}
                  </p>
                </div>
                <StatusBadge status={s.status} />
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
