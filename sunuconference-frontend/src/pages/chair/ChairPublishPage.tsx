import { useEffect, useState } from "react";
import { Globe, EyeOff } from "lucide-react";
import { toast } from "sonner";
import { Btn, Card, PageHeader, Spinner } from "../../app/components/common/primitives";
import { getMyConference, listSessions, publishProgram, unpublishProgram } from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDate } from "../../lib/date-format";
import type { Conference, ConferenceSession } from "../../lib/types";

export function ChairPublishPage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [sessions, setSessions] = useState<ConferenceSession[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    getMyConference()
      .then((c) => {
        setConference(c);
        if (c && c.status === "validated") {
          listSessions(c.id).then(setSessions);
        }
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger votre conférence.")));
  };

  useEffect(load, []);

  if (error) {
    return <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>;
  }

  if (conference === undefined) {
    return (
      <div className="flex justify-center py-16">
        <Spinner />
      </div>
    );
  }

  if (!conference || conference.status !== "validated") {
    return <p className="text-sm text-[#9CA3AF]">Cette section est disponible une fois votre conférence validée.</p>;
  }

  const scheduledCount = sessions.reduce((acc, s) => acc + (s.submissions?.length ?? 0), 0);

  const handlePublish = async () => {
    setBusy(true);
    try {
      const updated = await publishProgram(conference.id);
      setConference(updated);
      toast.success("Programme publié.");
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de publier le programme."));
    } finally {
      setBusy(false);
    }
  };

  const handleUnpublish = async () => {
    setBusy(true);
    try {
      const updated = await unpublishProgram(conference.id);
      setConference(updated);
      toast.success("Programme dépublié.");
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de dépublier le programme."));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="max-w-2xl">
      <PageHeader title="Publier le programme" subtitle="Rendez le programme visible sur la page publique de la conférence" />

      <Card className="p-6 mb-4">
        <div className="flex items-start gap-3">
          {conference.program_published ? (
            <Globe size={20} className="text-[#2F7A4F] shrink-0 mt-0.5" />
          ) : (
            <EyeOff size={20} className="text-[#9CA3AF] shrink-0 mt-0.5" />
          )}
          <div className="flex-1">
            <h3 className="text-sm font-bold text-[#1B2A4A]">
              {conference.program_published ? "Le programme est public" : "Le programme n'est pas encore public"}
            </h3>
            <p className="text-xs text-[#9CA3AF] mt-1 leading-relaxed">
              {conference.program_published
                ? "Toute personne visitant la page publique de la conférence peut consulter les sessions et leurs horaires."
                : "Une fois publié, les sessions programmées (date, salle, horaires, articles) seront visibles publiquement."}
            </p>
          </div>
        </div>

        <div className="mt-4 pt-4 border-t border-[rgba(28,28,46,0.07)] flex items-center justify-between">
          <p className="text-xs text-[#5B6B84]">
            {sessions.length} session{sessions.length > 1 ? "s" : ""} programmée{sessions.length > 1 ? "s" : ""}, {scheduledCount} article{scheduledCount > 1 ? "s" : ""} au total
          </p>
          {conference.program_published ? (
            <Btn variant="secondary" size="sm" onClick={handleUnpublish} disabled={busy}>
              Dépublier
            </Btn>
          ) : (
            <Btn size="sm" onClick={handlePublish} disabled={busy || sessions.length === 0}>
              Publier le programme
            </Btn>
          )}
        </div>
      </Card>

      {sessions.length > 0 && (
        <Card className="p-5">
          <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Aperçu</h3>
          <div className="space-y-2">
            {sessions.map((s) => (
              <div key={s.id} className="flex items-center justify-between text-sm p-2.5 bg-[#EDF0F2] rounded-sm">
                <span className="font-medium text-[#1B2A4A]">{s.title}</span>
                <span className="text-xs text-[#9CA3AF]">
                  {formatDate(s.session_date)} · {(s.submissions ?? []).length} article{(s.submissions ?? []).length > 1 ? "s" : ""}
                </span>
              </div>
            ))}
          </div>
        </Card>
      )}
    </div>
  );
}
