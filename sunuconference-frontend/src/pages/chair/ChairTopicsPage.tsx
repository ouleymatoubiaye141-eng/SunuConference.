import { useEffect, useState, type FormEvent } from "react";
import { ChevronDown, ChevronRight, Pencil, Plus, Trash2, UserPlus, X } from "lucide-react";
import { toast } from "sonner";
import { Btn, Card, EmptyState, FInput, FSelect, FTextarea, Modal, PageHeader, Spinner } from "../../app/components/common/primitives";
import {
  assignReviewerToTopic,
  createTopic,
  deleteTopic,
  getMyConference,
  listChairTopics,
  listConferenceReviewers,
  listTopicReviewers,
  unassignReviewerFromTopic,
  updateTopic,
} from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import type { Conference, Topic, User } from "../../lib/types";

export function ChairTopicsPage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [topics, setTopics] = useState<Topic[] | null>(null);
  const [reviewerPool, setReviewerPool] = useState<User[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [formOpen, setFormOpen] = useState<"new" | Topic | null>(null);
  const [expandedTopicId, setExpandedTopicId] = useState<number | null>(null);

  const load = () => {
    getMyConference()
      .then((c) => {
        setConference(c);
        if (c && c.status === "validated") {
          listChairTopics(c.id).then(setTopics);
          listConferenceReviewers(c.id).then(setReviewerPool);
        }
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les topics.")));
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

  const handleDelete = async (topic: Topic) => {
    if (!confirm(`Supprimer le topic « ${topic.name} » ?`)) return;
    try {
      await deleteTopic(conference.id, topic.id);
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de supprimer ce topic."));
    }
  };

  return (
    <div>
      <PageHeader
        title="Topics & assignations"
        subtitle="Pistes thématiques et compétences des relecteurs"
        action={
          <Btn size="sm" onClick={() => setFormOpen("new")}>
            <Plus size={13} /> Nouveau topic
          </Btn>
        }
      />

      {!topics ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : topics.length === 0 ? (
        <EmptyState icon={Plus} title="Aucun topic pour le moment" desc="Créez des pistes thématiques pour organiser les soumissions et les compétences des relecteurs." />
      ) : (
        <div className="space-y-3">
          {topics.map((topic) => (
            <TopicRow
              key={topic.id}
              topic={topic}
              conferenceId={conference.id}
              reviewerPool={reviewerPool}
              expanded={expandedTopicId === topic.id}
              onToggle={() => setExpandedTopicId((id) => (id === topic.id ? null : topic.id))}
              onEdit={() => setFormOpen(topic)}
              onDelete={() => handleDelete(topic)}
            />
          ))}
        </div>
      )}

      {formOpen && (
        <TopicFormModal
          conferenceId={conference.id}
          topic={formOpen === "new" ? null : formOpen}
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

function TopicRow({
  topic,
  conferenceId,
  reviewerPool,
  expanded,
  onToggle,
  onEdit,
  onDelete,
}: {
  topic: Topic;
  conferenceId: number;
  reviewerPool: User[];
  expanded: boolean;
  onToggle: () => void;
  onEdit: () => void;
  onDelete: () => void;
}) {
  const [assigned, setAssigned] = useState<User[] | null>(null);
  const [selectedReviewerId, setSelectedReviewerId] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (expanded && assigned === null) {
      listTopicReviewers(conferenceId, topic.id)
        .then(setAssigned)
        .catch(() => setAssigned([]));
    }
  }, [expanded, assigned, conferenceId, topic.id]);

  const availableToAdd = reviewerPool.filter((r) => !assigned?.some((a) => a.id === r.id));

  const handleAssign = async () => {
    if (!selectedReviewerId) return;
    setBusy(true);
    try {
      await assignReviewerToTopic(conferenceId, topic.id, Number(selectedReviewerId));
      setSelectedReviewerId("");
      setAssigned(await listTopicReviewers(conferenceId, topic.id));
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible d'assigner ce relecteur."));
    } finally {
      setBusy(false);
    }
  };

  const handleUnassign = async (reviewer: User) => {
    setBusy(true);
    try {
      await unassignReviewerFromTopic(conferenceId, topic.id, reviewer.id);
      setAssigned(await listTopicReviewers(conferenceId, topic.id));
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de retirer ce relecteur."));
    } finally {
      setBusy(false);
    }
  };

  return (
    <Card>
      <button onClick={onToggle} className="w-full px-4 py-3.5 flex items-center justify-between gap-4 text-left">
        <div className="flex items-center gap-2 min-w-0">
          {expanded ? <ChevronDown size={14} className="text-[#9CA3AF] shrink-0" /> : <ChevronRight size={14} className="text-[#9CA3AF] shrink-0" />}
          <div className="min-w-0">
            <p className="text-sm font-semibold text-[#1B2A4A] truncate">{topic.name}</p>
            {topic.description && <p className="text-xs text-[#9CA3AF] truncate">{topic.description}</p>}
          </div>
        </div>
        <div className="flex items-center gap-1 shrink-0">
          <span
            role="button"
            onClick={(e) => {
              e.stopPropagation();
              onEdit();
            }}
            className="p-1.5 rounded-sm hover:bg-gray-100 text-[#9CA3AF] hover:text-[#1B2A4A] transition-colors"
          >
            <Pencil size={13} />
          </span>
          <span
            role="button"
            onClick={(e) => {
              e.stopPropagation();
              onDelete();
            }}
            className="p-1.5 rounded-sm hover:bg-[#FBE3DE] text-[#9CA3AF] hover:text-[#B4472F] transition-colors"
          >
            <Trash2 size={13} />
          </span>
        </div>
      </button>

      {expanded && (
        <div className="px-4 pb-4 border-t border-[rgba(28,28,46,0.07)] pt-3">
          <p className="text-[11px] font-bold uppercase tracking-wider text-[#9CA3AF] mb-2">Relecteurs compétents</p>
          {assigned === null ? (
            <Spinner />
          ) : assigned.length === 0 ? (
            <p className="text-xs text-[#9CA3AF] mb-3">Aucun relecteur assigné à ce topic.</p>
          ) : (
            <div className="flex flex-wrap gap-2 mb-3">
              {assigned.map((r) => (
                <span
                  key={r.id}
                  className="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-[#E7ECF3] text-[#3C4A63] rounded text-xs font-medium"
                >
                  {r.name}
                  <button onClick={() => handleUnassign(r)} disabled={busy} className="hover:text-[#B4472F]">
                    <X size={11} />
                  </button>
                </span>
              ))}
            </div>
          )}
          <div className="flex items-center gap-2">
            <FSelect
              className="flex-1"
              value={selectedReviewerId}
              onChange={(e) => setSelectedReviewerId(e.target.value)}
              options={[
                { label: availableToAdd.length ? "Choisir un relecteur…" : "Aucun relecteur disponible", value: "" },
                ...availableToAdd.map((r) => ({ label: r.name, value: String(r.id) })),
              ]}
            />
            <Btn size="sm" onClick={handleAssign} disabled={!selectedReviewerId || busy}>
              <UserPlus size={12} /> Assigner
            </Btn>
          </div>
        </div>
      )}
    </Card>
  );
}

function TopicFormModal({
  conferenceId,
  topic,
  onClose,
  onSaved,
}: {
  conferenceId: number;
  topic: Topic | null;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [name, setName] = useState(topic?.name ?? "");
  const [description, setDescription] = useState(topic?.description ?? "");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      if (topic) {
        await updateTopic(conferenceId, topic.id, { name, description });
      } else {
        await createTopic(conferenceId, { name, description });
      }
      onSaved();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'enregistrer ce topic."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal title={topic ? "Modifier le topic" : "Nouveau topic"} onClose={onClose}>
      <form className="space-y-4" onSubmit={handleSubmit}>
        <FInput label="Nom" value={name} onChange={(e) => setName(e.target.value)} required />
        <FTextarea label="Description" rows={3} value={description ?? ""} onChange={(e) => setDescription(e.target.value)} />
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
