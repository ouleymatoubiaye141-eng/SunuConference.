import { useEffect, useState, type FormEvent } from "react";
import { Calendar, Clock, MapPin, Pencil, Plus, Trash2, X } from "lucide-react";
import { toast } from "sonner";
import { Btn, Card, EmptyState, FInput, FSelect, Modal, PageHeader, Spinner } from "../../app/components/common/primitives";
import {
  assignSubmissionToSession,
  createSession,
  deleteSession,
  getMyConference,
  listChairSubmissions,
  listSessions,
  removeSubmissionFromSession,
  updateSession,
  type SessionInput,
} from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDate } from "../../lib/date-format";
import type { Conference, ConferenceSession, Submission } from "../../lib/types";

function formatTimeRange(start: string, end: string): string {
  return `${start.slice(0, 5)} – ${end.slice(0, 5)}`;
}

export function ChairSessionsPage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [sessions, setSessions] = useState<ConferenceSession[] | null>(null);
  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [formOpen, setFormOpen] = useState<"new" | ConferenceSession | null>(null);

  const load = () => {
    getMyConference()
      .then((c) => {
        setConference(c);
        if (c && c.status === "validated") {
          listSessions(c.id).then(setSessions);
          listChairSubmissions(c.id).then(setSubmissions);
        }
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les sessions.")));
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

  const acceptedPapers = submissions.filter((s) => s.status === "accepted" || s.status === "camera_ready_submitted");

  const handleDelete = async (session: ConferenceSession) => {
    if (!confirm(`Supprimer la session « ${session.title} » ? Les articles programmés seront déprogrammés.`)) return;
    try {
      await deleteSession(conference.id, session.id);
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de supprimer cette session."));
    }
  };

  return (
    <div>
      <PageHeader
        title="Sessions"
        subtitle="Composez le programme en organisant les articles acceptés par créneau"
        action={
          <Btn size="sm" onClick={() => setFormOpen("new")}>
            <Plus size={13} /> Nouvelle session
          </Btn>
        }
      />

      {!sessions ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : sessions.length === 0 ? (
        <EmptyState icon={Calendar} title="Aucune session pour le moment" desc="Créez des sessions (date, horaire, salle) pour y programmer les articles acceptés." />
      ) : (
        <div className="space-y-3">
          {sessions.map((session) => (
            <SessionRow
              key={session.id}
              session={session}
              conferenceId={conference.id}
              acceptedPapers={acceptedPapers}
              onEdit={() => setFormOpen(session)}
              onDelete={() => handleDelete(session)}
              onChanged={load}
            />
          ))}
        </div>
      )}

      {formOpen && (
        <SessionFormModal
          conferenceId={conference.id}
          session={formOpen === "new" ? null : formOpen}
          onClose={() => setFormOpen(null)}
          onSaved={() => {
            setFormOpen(null);
            load();
          }}
        />
      )}
    </div>
  );
}

function SessionRow({
  session,
  conferenceId,
  acceptedPapers,
  onEdit,
  onDelete,
  onChanged,
}: {
  session: ConferenceSession;
  conferenceId: number;
  acceptedPapers: Submission[];
  onEdit: () => void;
  onDelete: () => void;
  onChanged: () => void;
}) {
  const [selectedPaperId, setSelectedPaperId] = useState("");
  const [busy, setBusy] = useState(false);

  const scheduled = session.submissions ?? [];
  const scheduledIds = new Set(scheduled.map((s) => s.id));
  // Un article accepté ne peut être programmé que dans une seule session à la fois.
  const availablePapers = acceptedPapers.filter(
    (p) => !scheduledIds.has(p.id) && (!p.conference_session_id || p.conference_session_id === session.id),
  );

  const handleAssign = async () => {
    if (!selectedPaperId) return;
    setBusy(true);
    try {
      await assignSubmissionToSession(conferenceId, session.id, Number(selectedPaperId));
      setSelectedPaperId("");
      onChanged();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de programmer cet article."));
    } finally {
      setBusy(false);
    }
  };

  const handleRemove = async (submissionId: number) => {
    setBusy(true);
    try {
      await removeSubmissionFromSession(conferenceId, session.id, submissionId);
      onChanged();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de retirer cet article."));
    } finally {
      setBusy(false);
    }
  };

  return (
    <Card className="p-4">
      <div className="flex items-start justify-between gap-4 mb-3">
        <div className="min-w-0">
          <p className="text-sm font-semibold text-[#1B2A4A]">{session.title}</p>
          <div className="flex items-center gap-3 mt-1 flex-wrap text-xs text-[#9CA3AF]">
            <span className="flex items-center gap-1">
              <Calendar size={11} /> {formatDate(session.session_date)}
            </span>
            <span className="flex items-center gap-1">
              <Clock size={11} /> {formatTimeRange(session.start_time, session.end_time)}
            </span>
            {session.room && (
              <span className="flex items-center gap-1">
                <MapPin size={11} /> {session.room}
              </span>
            )}
          </div>
        </div>
        <div className="flex items-center gap-1 shrink-0">
          <button onClick={onEdit} className="p-1.5 rounded-sm hover:bg-gray-100 text-[#9CA3AF] hover:text-[#1B2A4A] transition-colors">
            <Pencil size={13} />
          </button>
          <button onClick={onDelete} className="p-1.5 rounded-sm hover:bg-[#FBE3DE] text-[#9CA3AF] hover:text-[#B4472F] transition-colors">
            <Trash2 size={13} />
          </button>
        </div>
      </div>

      {scheduled.length === 0 ? (
        <p className="text-xs text-[#9CA3AF] mb-3">Aucun article programmé dans cette session.</p>
      ) : (
        <div className="space-y-1.5 mb-3">
          {scheduled.map((paper) => (
            <div key={paper.id} className="flex items-center justify-between gap-2 p-2 bg-[#EDF0F2] rounded-sm">
              <div className="min-w-0">
                <p className="text-xs font-medium text-[#1B2A4A] truncate">{paper.title}</p>
                <p className="text-[10px] text-[#9CA3AF] truncate">{paper.author?.name}</p>
              </div>
              <button onClick={() => handleRemove(paper.id)} disabled={busy} className="text-[#9CA3AF] hover:text-[#B4472F] shrink-0">
                <X size={12} />
              </button>
            </div>
          ))}
        </div>
      )}

      <div className="flex items-center gap-2">
        <FSelect
          className="flex-1"
          value={selectedPaperId}
          onChange={(e) => setSelectedPaperId(e.target.value)}
          options={[
            { label: availablePapers.length ? "Programmer un article accepté…" : "Aucun article accepté disponible", value: "" },
            ...availablePapers.map((p) => ({ label: p.title, value: String(p.id) })),
          ]}
        />
        <Btn size="sm" onClick={handleAssign} disabled={!selectedPaperId || busy}>
          <Plus size={12} /> Ajouter
        </Btn>
      </div>
    </Card>
  );
}

function SessionFormModal({
  conferenceId,
  session,
  onClose,
  onSaved,
}: {
  conferenceId: number;
  session: ConferenceSession | null;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [form, setForm] = useState<SessionInput>({
    title: session?.title ?? "",
    room: session?.room ?? "",
    session_date: session?.session_date.slice(0, 10) ?? "",
    start_time: session?.start_time.slice(0, 5) ?? "",
    end_time: session?.end_time.slice(0, 5) ?? "",
  });
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const update = (field: keyof SessionInput, value: string) => setForm((f) => ({ ...f, [field]: value }));

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      if (session) {
        await updateSession(conferenceId, session.id, form);
      } else {
        await createSession(conferenceId, form);
      }
      onSaved();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'enregistrer cette session."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal title={session ? "Modifier la session" : "Nouvelle session"} onClose={onClose}>
      <form className="space-y-4" onSubmit={handleSubmit}>
        <FInput label="Titre" value={form.title} onChange={(e) => update("title", e.target.value)} required />
        <FInput label="Salle" value={form.room ?? ""} onChange={(e) => update("room", e.target.value)} />
        <FInput label="Date" type="date" value={form.session_date} onChange={(e) => update("session_date", e.target.value)} required />
        <div className="grid grid-cols-2 gap-4">
          <FInput label="Début" type="time" value={form.start_time} onChange={(e) => update("start_time", e.target.value)} required />
          <FInput label="Fin" type="time" value={form.end_time} onChange={(e) => update("end_time", e.target.value)} required />
        </div>
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <div className="flex justify-end gap-3">
          <Btn variant="secondary" onClick={onClose}>
            Annuler
          </Btn>
          <Btn type="submit" disabled={isSubmitting}>
            {isSubmitting ? "Enregistrement…" : "Enregistrer"}
          </Btn>
        </div>
      </form>
    </Modal>
  );
}
