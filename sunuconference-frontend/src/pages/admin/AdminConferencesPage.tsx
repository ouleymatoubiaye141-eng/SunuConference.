import { useEffect, useMemo, useState } from "react";
import { Globe } from "lucide-react";
import { Card, EmptyState, PageHeader, Spinner } from "../../app/components/common/primitives";
import { listAllConferences } from "../../lib/admin-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDateRange } from "../../lib/date-format";
import type { Conference, ConferenceStatus } from "../../lib/types";

const STATUS_META: Record<ConferenceStatus, { label: string; bg: string; text: string }> = {
  pending: { label: "En attente", bg: "#F5EBD8", text: "#8C6420" },
  validated: { label: "Validée", bg: "#E1F3E7", text: "#2F7A4F" },
  rejected: { label: "Rejetée", bg: "#FBE3DE", text: "#8C3320" },
};

const TABS: { key: "all" | ConferenceStatus; label: string }[] = [
  { key: "all", label: "Toutes" },
  { key: "pending", label: "En attente" },
  { key: "validated", label: "Validées" },
  { key: "rejected", label: "Rejetées" },
];

export function AdminConferencesPage() {
  const [conferences, setConferences] = useState<Conference[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState<"all" | ConferenceStatus>("all");

  useEffect(() => {
    listAllConferences()
      .then(setConferences)
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les conférences.")));
  }, []);

  const filtered = useMemo(
    () => (tab === "all" ? conferences ?? [] : (conferences ?? []).filter((c) => c.status === tab)),
    [conferences, tab],
  );

  return (
    <div>
      <PageHeader title="Toutes les conférences" subtitle={conferences ? `${conferences.length} conférence(s) au total` : undefined} />

      {error && <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2 mb-4">{error}</p>}

      <div className="flex gap-1 mb-5 bg-[#E7ECF3] p-1 rounded-sm w-fit">
        {TABS.map((t) => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={`px-3 py-1.5 text-xs font-semibold rounded-sm transition-colors ${
              tab === t.key ? "bg-white text-[#1B2A4A] shadow-sm" : "text-[#5B6B84] hover:text-[#1B2A4A]"
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {conferences === null ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : filtered.length === 0 ? (
        <EmptyState icon={Globe} title="Aucune conférence" desc="Aucune conférence ne correspond à ce filtre." />
      ) : (
        <div className="space-y-3">
          {filtered.map((conference) => {
            const meta = STATUS_META[conference.status];
            return (
              <Card key={conference.id} className="p-4 flex items-center justify-between gap-4">
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <span
                      className="text-[10px] font-semibold px-2 py-0.5 rounded-sm shrink-0"
                      style={{ backgroundColor: meta.bg, color: meta.text }}
                    >
                      {meta.label}
                    </span>
                    <h3 className="text-sm font-semibold text-[#1B2A4A] truncate">{conference.name}</h3>
                  </div>
                  <p className="text-xs text-[#9CA3AF] mt-1">
                    {conference.chair?.name ?? "—"} · {formatDateRange(conference.start_date, conference.end_date)}
                  </p>
                </div>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}
