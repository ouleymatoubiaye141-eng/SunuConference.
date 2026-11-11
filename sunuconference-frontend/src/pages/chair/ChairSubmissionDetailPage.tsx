import { useEffect, useState, type FormEvent } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { AlertTriangle, Send, UserPlus, X } from "lucide-react";
import { toast } from "sonner";
import { Btn, Card, FSelect, FTextarea, PageHeader, Spinner } from "../../app/components/common/primitives";
import { StatusBadge } from "../../app/components/common/StatusBadge";
import { SubmissionFilesCard } from "../../app/components/common/SubmissionFilesCard";
import {
  assignReviewerToSubmission,
  getChairSubmission,
  getMyConference,
  getReviewsSummary,
  listTopicReviewers,
  recordDecision,
  removeReviewerFromSubmission,
} from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDate } from "../../lib/date-format";
import type { Conference, DecisionValue, ReviewSummary, Submission, User } from "../../lib/types";

const RECOMMENDATION_LABEL: Record<string, string> = {
  accept: "Accepté",
  minor_revision: "Révision mineure",
  major_revision: "Révision majeure",
  reject: "Refusé",
};

const DECISIONS: { value: DecisionValue; label: string; color: string; bg: string }[] = [
  { value: "accept", label: "Accepter", color: "#2F7A4F", bg: "#E1F3E7" },
  { value: "revision_required", label: "Demander une révision", color: "#EA580C", bg: "#F5EBD8" },
  { value: "reject", label: "Refuser", color: "#B4472F", bg: "#FBE3DE" },
];

const CAN_DECIDE_STATUSES = ["under_review", "revised_submitted"];

export function ChairSubmissionDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const [conference, setConference] = useState<Conference | null>(null);
  const [submission, setSubmission] = useState<Submission | null>(null);
  const [summary, setSummary] = useState<ReviewSummary | null>(null);
  const [topicReviewers, setTopicReviewers] = useState<User[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = () => {
    if (!id) return;
    getMyConference()
      .then((c) => {
        if (!c) throw new Error("no-conference");
        setConference(c);
        return Promise.all([
          getChairSubmission(c.id, id),
          getReviewsSummary(c.id, id),
        ]).then(([sub, sum]) => {
          setSubmission(sub);
          setSummary(sum);
          return listTopicReviewers(c.id, sub.topic_id).then(setTopicReviewers);
        });
      })
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger cette soumission.")));
  };

  useEffect(load, [id]);

  if (error) {
    return <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>;
  }

  if (!submission || !conference || !summary) {
    return (
      <div className="flex justify-center py-16">
        <Spinner />
      </div>
    );
  }

  const assignedReviewerIds = new Set(
    (submission.reviewer_submissions ?? []).map((rs) => rs.reviewer_id),
  );
  const availableReviewers = topicReviewers.filter((r) => !assignedReviewerIds.has(r.id));

  const handleAssign = async (reviewerId: number) => {
    try {
      await assignReviewerToSubmission(conference.id, submission.id, reviewerId);
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible d'assigner ce relecteur."));
    }
  };

  const handleRemove = async (reviewerId: number) => {
    try {
      await removeReviewerFromSubmission(conference.id, submission.id, reviewerId);
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de retirer ce relecteur."));
    }
  };

  return (
    <div className="max-w-4xl space-y-4">
      <PageHeader
        eyebrow="Dossier — PC Chair"
        title={submission.title}
        back="Soumissions"
        onBack={() => navigate("/chair/soumissions")}
        action={<StatusBadge status={submission.status} />}
      />

      <div className="flex items-center gap-2 flex-wrap">
        <span className="text-xs bg-[#E7ECF3] text-[#5B6B84] px-2 py-0.5 rounded">{submission.topic?.name}</span>
        <span className="text-xs text-[#A9B4C4]">{submission.author?.name}</span>
      </div>

      <div className="grid grid-cols-3 gap-4">
        <div className="col-span-2 space-y-4">
          <Card className="p-5">
            <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Résumé</h3>
            <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{submission.abstract}</p>
            {submission.keywords && submission.keywords.length > 0 && (
              <div className="flex flex-wrap gap-1.5 mt-3">
                {submission.keywords.map((k) => (
                  <span key={k} className="px-2 py-0.5 bg-[#E7ECF3] text-[#5B6B84] text-[11px] rounded">
                    {k}
                  </span>
                ))}
              </div>
            )}
            {submission.co_authors && submission.co_authors.length > 0 && (
              <div className="mt-4 pt-3 border-t border-[rgba(28,28,46,0.06)]">
                <p className="text-[11px] font-bold uppercase tracking-wider text-[#9CA3AF] mb-1.5">Co-auteurs</p>
                <p className="text-xs text-[#5B6B84]">{submission.co_authors.map((a) => a.name).join(", ")}</p>
              </div>
            )}
          </Card>

          <SubmissionFilesCard submission={submission} />

          <ReviewSummaryCard summary={summary} />

          {CAN_DECIDE_STATUSES.includes(submission.status) && (
            <DecisionForm
              conferenceId={conference.id}
              submissionId={submission.id}
              hasSubmittedReview={summary.submitted_reviews.length > 0}
              onDecided={load}
            />
          )}

          {submission.decisions && submission.decisions.length > 0 && (
            <Card className="p-5">
              <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Historique des décisions</h3>
              <div className="space-y-2">
                {submission.decisions.map((d) => (
                  <div key={d.id} className="p-3 bg-[#EDF0F2] rounded-sm">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-semibold text-[#1B2A4A]">{RECOMMENDATION_LABEL[d.decision] ?? d.decision}</span>
                      <span className="text-[11px] text-[#9CA3AF]">{formatDate(d.decided_at)}</span>
                    </div>
                    {d.comments && <p className="text-xs text-[#5B6B84] mt-1">{d.comments}</p>}
                  </div>
                ))}
              </div>
            </Card>
          )}
        </div>

        <div className="space-y-4">
          <Card className="p-4">
            <h3 className="text-[11px] font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Relecteurs assignés</h3>
            {(submission.reviewer_submissions ?? []).length === 0 ? (
              <p className="text-xs text-[#9CA3AF] mb-3">Aucun relecteur assigné.</p>
            ) : (
              <div className="space-y-2 mb-3">
                {(submission.reviewer_submissions ?? []).map((rs) => (
                  <div key={rs.id} className="flex items-center justify-between gap-2 p-2 bg-[#EDF0F2] rounded-sm">
                    <div className="min-w-0">
                      <p className="text-xs font-medium text-[#1B2A4A] truncate">{rs.reviewer?.name}</p>
                      <p className="text-[10px] text-[#9CA3AF]">
                        {rs.review?.status === "submitted"
                          ? "Rapport soumis"
                          : rs.review?.status === "conflict_declared"
                            ? "Conflit déclaré"
                            : "En attente"}
                      </p>
                    </div>
                    {rs.review?.status !== "submitted" && (
                      <button onClick={() => handleRemove(rs.reviewer_id)} className="text-[#9CA3AF] hover:text-[#B4472F] shrink-0">
                        <X size={12} />
                      </button>
                    )}
                  </div>
                ))}
              </div>
            )}
            <AssignReviewerControl reviewers={availableReviewers} onAssign={handleAssign} />
          </Card>
        </div>
      </div>
    </div>
  );
}

function AssignReviewerControl({ reviewers, onAssign }: { reviewers: User[]; onAssign: (id: number) => Promise<void> }) {
  const [selected, setSelected] = useState("");
  const [busy, setBusy] = useState(false);

  const handleClick = async () => {
    if (!selected) return;
    setBusy(true);
    try {
      await onAssign(Number(selected));
      setSelected("");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="space-y-2">
      <FSelect
        value={selected}
        onChange={(e) => setSelected(e.target.value)}
        options={[
          { label: reviewers.length ? "Choisir un relecteur…" : "Aucun relecteur compétent disponible", value: "" },
          ...reviewers.map((r) => ({ label: r.name, value: String(r.id) })),
        ]}
      />
      <Btn size="sm" className="w-full justify-center" onClick={handleClick} disabled={!selected || busy}>
        <UserPlus size={12} /> Assigner
      </Btn>
      <p className="text-[10px] text-[#A9B4C4]">Seuls les relecteurs déjà compétents sur ce topic sont proposés.</p>
    </div>
  );
}

function ReviewSummaryCard({ summary }: { summary: ReviewSummary }) {
  const { average_scores, submitted_reviews, conflicts, pending_assignments, recommendations_distribution } = summary;

  return (
    <Card className="p-5">
      <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-4">Synthèse des évaluations</h3>

      <div className="grid grid-cols-4 gap-3 mb-4">
        {[
          { key: "originality", label: "Originalité" },
          { key: "relevance", label: "Pertinence" },
          { key: "presentation", label: "Présentation" },
          { key: "overall", label: "Global" },
        ].map(({ key, label }) => (
          <div key={key} className="text-center p-2.5 bg-[#EDF0F2] rounded-sm">
            <p className="text-lg font-bold text-[#1B2A4A]">
              {average_scores[key as keyof typeof average_scores] ?? "—"}
              <span className="text-xs text-[#A9B4C4] font-normal">/5</span>
            </p>
            <p className="text-[10px] text-[#9CA3AF] mt-0.5">{label}</p>
          </div>
        ))}
      </div>

      {Object.keys(recommendations_distribution).length > 0 && (
        <div className="flex flex-wrap gap-2 mb-4">
          {Object.entries(recommendations_distribution).map(([rec, count]) => (
            <span key={rec} className="text-[11px] px-2 py-0.5 bg-[#E7ECF3] text-[#5B6B84] rounded font-medium">
              {RECOMMENDATION_LABEL[rec] ?? rec} : {count}
            </span>
          ))}
        </div>
      )}

      {pending_assignments.length > 0 && (
        <div className="flex items-center gap-1.5 text-[11px] text-[#8C6420] bg-[#F5EBD8] border border-[#E8C077] rounded-sm px-2.5 py-1.5 mb-4">
          <AlertTriangle size={11} /> {pending_assignments.length} relecteur(s) n'ont pas encore soumis leur rapport.
        </div>
      )}

      {submitted_reviews.length === 0 ? (
        <p className="text-xs text-[#9CA3AF]">Aucun rapport soumis pour le moment.</p>
      ) : (
        <div className="space-y-3">
          {submitted_reviews.map((review) => (
            <div key={review.id} className="p-3.5 bg-[#EDF0F2] rounded-sm border border-[rgba(28,28,46,0.06)]">
              <div className="flex items-center justify-between mb-1.5">
                <span className="text-xs font-semibold text-[#1B2A4A]">{review.reviewer_submission?.reviewer?.name}</span>
                <span className="text-[11px] font-medium text-[#5B6B84]">{RECOMMENDATION_LABEL[review.recommendation ?? ""] ?? "—"}</span>
              </div>
              <p className="text-xs text-[#3C4A63] leading-relaxed mb-2">{review.public_comment}</p>
              {review.private_comment && (
                <div className="mt-2 pt-2 border-t border-[rgba(28,28,46,0.06)]">
                  <p className="text-[10px] font-bold uppercase tracking-wider text-[#9CA3AF] mb-1">Confidentiel</p>
                  <p className="text-xs text-[#5B6B84] leading-relaxed">{review.private_comment}</p>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {conflicts.length > 0 && (
        <div className="mt-3 pt-3 border-t border-[rgba(28,28,46,0.06)]">
          <p className="text-[11px] font-bold uppercase tracking-wider text-[#9CA3AF] mb-1.5">Conflits déclarés</p>
          {conflicts.map((c) => (
            <p key={c.id} className="text-xs text-[#8C3320]">
              {c.reviewer?.name ?? `Relecteur #${c.reviewer_id}`} — {c.review?.conflict_reason}
            </p>
          ))}
        </div>
      )}
    </Card>
  );
}

function DecisionForm({
  conferenceId,
  submissionId,
  hasSubmittedReview,
  onDecided,
}: {
  conferenceId: number;
  submissionId: number;
  hasSubmittedReview: boolean;
  onDecided: () => void;
}) {
  const [decision, setDecision] = useState<DecisionValue | "">("");
  const [comments, setComments] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    if (!decision) {
      setError("Sélectionnez une décision.");
      return;
    }
    if (decision !== "accept" && !comments.trim()) {
      setError("Un commentaire est obligatoire pour un refus ou une demande de révision.");
      return;
    }
    setIsSubmitting(true);
    try {
      const result = await recordDecision(conferenceId, submissionId, decision, comments || null);
      if (result.warnings?.length) {
        result.warnings.forEach((w) => toast.warning(w));
      }
      toast.success("Décision enregistrée.");
      onDecided();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'enregistrer la décision."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="p-5">
      <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Décision finale</h3>
      {!hasSubmittedReview && (
        <p className="text-xs text-[#8C6420] bg-[#F5EBD8] border border-[#E8C077] rounded-sm px-2.5 py-1.5 mb-3">
          Au moins un rapport de relecture doit être soumis avant de pouvoir statuer.
        </p>
      )}
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-3 gap-2">
          {DECISIONS.map((d) => (
            <button
              key={d.value}
              type="button"
              onClick={() => setDecision(d.value)}
              style={decision === d.value ? { borderColor: d.color, backgroundColor: d.bg, color: d.color } : {}}
              className={`py-2.5 text-xs font-bold rounded-sm border-2 transition-all ${
                decision === d.value ? "" : "border-[#E1E5E9] text-[#9CA3AF] hover:border-gray-300"
              }`}
            >
              {d.label}
            </button>
          ))}
        </div>
        <FTextarea
          label="Commentaire (visible par l'auteur)"
          rows={4}
          value={comments}
          onChange={(e) => setComments(e.target.value)}
          placeholder="Motivez la décision, notamment en cas de refus ou de révision demandée."
        />
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <Btn type="submit" disabled={isSubmitting || !hasSubmittedReview} className="w-full justify-center">
          <Send size={13} /> {isSubmitting ? "Envoi…" : "Enregistrer la décision"}
        </Btn>
      </form>
    </Card>
  );
}
