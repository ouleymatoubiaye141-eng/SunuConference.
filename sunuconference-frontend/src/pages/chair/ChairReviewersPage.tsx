import { useEffect, useState, type FormEvent } from "react";
import { Mail, Plus, RotateCw, Trash2, Users } from "lucide-react";
import { toast } from "sonner";
import { Btn, Card, EmptyState, FInput, Modal, PageHeader, Spinner } from "../../app/components/common/primitives";
import {
  createReviewer,
  getMyConference,
  listConferenceReviewers,
  removeReviewerFromConference,
  resendReviewerCredentials,
} from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import type { Conference, User } from "../../lib/types";

export function ChairReviewersPage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [reviewers, setReviewers] = useState<User[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [inviteOpen, setInviteOpen] = useState(false);

  const load = () => {
    getMyConference()
      .then((c) => {
        setConference(c);
        if (c && c.status === "validated") {
          return listConferenceReviewers(c.id).then(setReviewers);
        }
        return undefined;
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les relecteurs.")));
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

  const handleRemove = async (reviewer: User) => {
    if (!confirm(`Retirer ${reviewer.name} de la conférence ?`)) return;
    try {
      await removeReviewerFromConference(conference.id, reviewer.id);
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de retirer ce relecteur."));
    }
  };

  const handleResend = async (reviewer: User) => {
    try {
      await resendReviewerCredentials(conference.id, reviewer.id);
      toast.success(`Nouveaux identifiants envoyés à ${reviewer.email}.`);
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de renvoyer les identifiants."));
    }
  };

  return (
    <div>
      <PageHeader
        title="Relecteurs"
        subtitle={reviewers ? `${reviewers.length} relecteur${reviewers.length > 1 ? "s" : ""}` : undefined}
        action={
          <Btn size="sm" onClick={() => setInviteOpen(true)}>
            <Plus size={13} /> Inviter un relecteur
          </Btn>
        }
      />

      {!reviewers ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : reviewers.length === 0 ? (
        <EmptyState icon={Users} title="Aucun relecteur pour le moment" desc="Invitez des relecteurs pour pouvoir leur assigner des topics et des soumissions." />
      ) : (
        <Card>
          <div className="divide-y divide-[rgba(28,28,46,0.05)]">
            {reviewers.map((r) => (
              <div key={r.id} className="px-4 py-3.5 flex items-center justify-between gap-4">
                <div className="min-w-0">
                  <p className="text-sm font-medium text-[#1B2A4A] truncate">{r.name}</p>
                  <p className="text-xs text-[#9CA3AF] truncate">{r.email}</p>
                </div>
                <div className="flex items-center gap-1 shrink-0">
                  <button
                    onClick={() => handleResend(r)}
                    title="Renvoyer les identifiants"
                    className="p-1.5 rounded-sm hover:bg-gray-100 text-[#9CA3AF] hover:text-[#1B2A4A] transition-colors"
                  >
                    <RotateCw size={13} />
                  </button>
                  <button
                    onClick={() => handleRemove(r)}
                    title="Retirer de la conférence"
                    className="p-1.5 rounded-sm hover:bg-[#FBE3DE] text-[#9CA3AF] hover:text-[#B4472F] transition-colors"
                  >
                    <Trash2 size={13} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </Card>
      )}

      {inviteOpen && (
        <InviteReviewerModal
          conferenceId={conference.id}
          onClose={() => setInviteOpen(false)}
          onInvited={() => {
            setInviteOpen(false);
            load();
          }}
        />
      )}
    </div>
  );
}

function InviteReviewerModal({
  conferenceId,
  onClose,
  onInvited,
}: {
  conferenceId: number;
  onClose: () => void;
  onInvited: () => void;
}) {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      await createReviewer(conferenceId, { name, email });
      onInvited();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'inviter ce relecteur."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal title="Inviter un relecteur" onClose={onClose}>
      <form className="space-y-4" onSubmit={handleSubmit}>
        <div className="p-3 bg-[#EFF6FF] border border-[#BFDBFE] rounded-sm flex gap-2">
          <Mail size={14} className="text-[#1D4ED8] shrink-0 mt-0.5" />
          <p className="text-xs text-[#1E3A8A] leading-relaxed">
            Un compte relecteur sera créé et des identifiants temporaires lui seront envoyés par e-mail.
          </p>
        </div>
        <FInput label="Nom complet" value={name} onChange={(e) => setName(e.target.value)} required />
        <FInput label="E-mail" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <div className="flex justify-end gap-3">
          <Btn variant="secondary" onClick={onClose}>
            Annuler
          </Btn>
          <Btn type="submit" disabled={isSubmitting}>
            {isSubmitting ? "Envoi…" : "Envoyer l'invitation"}
          </Btn>
        </div>
      </form>
    </Modal>
  );
}
