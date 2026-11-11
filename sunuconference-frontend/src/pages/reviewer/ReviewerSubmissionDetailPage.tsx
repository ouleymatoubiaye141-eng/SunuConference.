import { useEffect, useState, type FormEvent } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { AlertCircle, CheckCircle } from "lucide-react";
import { Btn, Card, FTextarea, Modal, PageHeader, Spinner } from "../../app/components/common/primitives";
import { SubmissionFilesCard } from "../../app/components/common/SubmissionFilesCard";
import {
  declareReviewConflict,
  listAssignedSubmissions,
  saveReviewDraft,
  submitReview,
} from "../../lib/reviewer-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import type { Recommendation, ReviewerAssignment } from "../../lib/types";

const RECOMMENDATIONS: { value: Recommendation; label: string; color: string; bg: string }[] = [
  { value: "accept", label: "Accepté", color: "#2F7A4F", bg: "#E1F3E7" },
  { value: "minor_revision", label: "Révision mineure", color: "#D97706", bg: "#F5EBD8" },
  { value: "major_revision", label: "Révision majeure", color: "#EA580C", bg: "#F5EBD8" },
  { value: "reject", label: "Refusé", color: "#B4472F", bg: "#FBE3DE" },
];

const CRITERIA: { key: "originality_score" | "relevance_score" | "presentation_score" | "overall_score"; label: string }[] = [
  { key: "originality_score", label: "Originalité et contribution scientifique" },
  { key: "relevance_score", label: "Pertinence pour la conférence" },
  { key: "presentation_score", label: "Qualité de la présentation" },
  { key: "overall_score", label: "Score global" },
];

export function ReviewerSubmissionDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const [assignment, setAssignment] = useState<ReviewerAssignment | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [conflictOpen, setConflictOpen] = useState(false);

  const load = () => {
    if (!id) return;
    listAssignedSubmissions()
      .then((all) => {
        const found = all.find((a) => String(a.id) === id);
        if (!found) {
          setLoadError("Assignation introuvable.");
          return;
        }
        setAssignment(found);
      })
      .catch((err) => setLoadError(extractApiErrorMessage(err, "Impossible de charger cette assignation.")));
  };

  useEffect(load, [id]);

  if (loadError) {
    return <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{loadError}</p>;
  }

  if (!assignment) {
    return (
      <div className="flex justify-center py-16">
        <Spinner />
      </div>
    );
  }

  const submission = assignment.submission;
  const review = assignment.review;
  const isLocked = review?.status === "submitted" || review?.status === "conflict_declared";

  return (
    <div className="max-w-3xl space-y-4">
      <PageHeader
        eyebrow="Dossier — Relecteur"
        title={submission?.title ?? ""}
        subtitle="Retour à mes assignations"
        back="Mes assignations"
        onBack={() => navigate("/relecteur")}
        action={
          !isLocked && (
            <Btn variant="danger" size="sm" onClick={() => setConflictOpen(true)}>
              <AlertCircle size={12} /> Déclarer un conflit d'intérêt
            </Btn>
          )
        }
      />

      <div className="flex items-center gap-2 flex-wrap">
        <span className="text-xs bg-[#E7ECF3] text-[#5B6B84] px-2 py-0.5 rounded">{submission?.topic?.name}</span>
        <span className="text-xs text-[#A9B4C4]">Auteurs masqués côté relecture</span>
      </div>

      <Card className="p-5">
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Résumé</h3>
        <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{submission?.abstract}</p>
        {submission?.keywords && submission.keywords.length > 0 && (
          <div className="flex flex-wrap gap-1.5 mt-3">
            {submission.keywords.map((k) => (
              <span key={k} className="px-2 py-0.5 bg-[#E7ECF3] text-[#5B6B84] text-[11px] rounded">
                {k}
              </span>
            ))}
          </div>
        )}
      </Card>

      {submission && <SubmissionFilesCard submission={submission} />}

      {review?.status === "conflict_declared" && (
        <Card className="p-5 border-[#F0C4B8] bg-[#FBE3DE]">
          <h3 className="text-sm font-semibold text-[#8C3320] mb-1 flex items-center gap-1.5">
            <AlertCircle size={14} /> Conflit d'intérêt déclaré
          </h3>
          <p className="text-sm text-[#6E2718] leading-relaxed">{review.conflict_reason}</p>
          <p className="text-xs text-[#9CA3AF] mt-2">Le PC Chair a été notifié et procédera à une réassignation.</p>
        </Card>
      )}

      {review?.status === "submitted" && <SubmittedReviewSummary review={review} />}

      {!isLocked && assignment && (
        <ReviewForm assignmentId={assignment.id} existingReview={review ?? null} onSaved={load} />
      )}

      {conflictOpen && (
        <ConflictModal
          assignmentId={assignment.id}
          onClose={() => setConflictOpen(false)}
          onDeclared={() => {
            setConflictOpen(false);
            load();
          }}
        />
      )}
    </div>
  );
}

function SubmittedReviewSummary({ review }: { review: NonNullable<ReviewerAssignment["review"]> }) {
  const rec = RECOMMENDATIONS.find((r) => r.value === review.recommendation);
  return (
    <Card className="p-5">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] flex items-center gap-1.5">
          <CheckCircle size={13} className="text-[#2F7A4F]" /> Rapport envoyé
        </h3>
        {rec && (
          <span className="text-xs font-semibold px-2 py-0.5 rounded-full" style={{ color: rec.color, backgroundColor: rec.bg }}>
            {rec.label}
          </span>
        )}
      </div>
      <div className="flex gap-4 mb-4">
        {CRITERIA.map((c) => (
          <div key={c.key} className="text-center">
            <div className="text-xl font-bold text-[#1B2A4A] leading-none">
              {review[c.key]}
              <span className="text-sm text-[#A9B4C4] font-normal">/5</span>
            </div>
            <div className="text-[10px] text-[#9CA3AF] mt-0.5 max-w-[80px]">{c.label}</div>
          </div>
        ))}
      </div>
      <div className="p-3.5 bg-[#EDF0F2] rounded-sm border border-[rgba(28,28,46,0.06)]">
        <p className="text-[10px] text-[#9CA3AF] uppercase tracking-wider font-bold mb-2">Commentaire à l'auteur</p>
        <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{review.public_comment}</p>
      </div>
    </Card>
  );
}

function ReviewForm({
  assignmentId,
  existingReview,
  onSaved,
}: {
  assignmentId: number;
  existingReview: ReviewerAssignment["review"] | null;
  onSaved: () => void;
}) {
  const [scores, setScores] = useState<Record<string, number>>({
    originality_score: existingReview?.originality_score ?? 0,
    relevance_score: existingReview?.relevance_score ?? 0,
    presentation_score: existingReview?.presentation_score ?? 0,
    overall_score: existingReview?.overall_score ?? 0,
  });
  const [recommendation, setRecommendation] = useState<Recommendation | "">(existingReview?.recommendation ?? "");
  const [publicComment, setPublicComment] = useState(existingReview?.public_comment ?? "");
  const [privateComment, setPrivateComment] = useState(existingReview?.private_comment ?? "");
  const [error, setError] = useState<string | null>(null);
  const [savingDraft, setSavingDraft] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleSaveDraft = async () => {
    setError(null);
    setSavingDraft(true);
    try {
      await saveReviewDraft(assignmentId, {
        originality_score: scores.originality_score || null,
        relevance_score: scores.relevance_score || null,
        presentation_score: scores.presentation_score || null,
        overall_score: scores.overall_score || null,
        public_comment: publicComment || null,
        private_comment: privateComment || null,
        recommendation: recommendation || null,
      });
      onSaved();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'enregistrer le brouillon."));
    } finally {
      setSavingDraft(false);
    }
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!scores.originality_score || !scores.relevance_score || !scores.presentation_score || !scores.overall_score) {
      setError("Tous les scores sont obligatoires pour envoyer le rapport.");
      return;
    }
    if (!recommendation) {
      setError("Sélectionnez une recommandation finale.");
      return;
    }
    if (!publicComment.trim()) {
      setError("Le commentaire à l'auteur est obligatoire.");
      return;
    }

    setSubmitting(true);
    try {
      await submitReview(assignmentId, {
        originality_score: scores.originality_score,
        relevance_score: scores.relevance_score,
        presentation_score: scores.presentation_score,
        overall_score: scores.overall_score,
        public_comment: publicComment,
        private_comment: privateComment || null,
        recommendation,
      });
      onSaved();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'envoyer le rapport."));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Card className="p-6 space-y-7">
      <div>
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-4">Scores par critère</h3>
        <div className="space-y-5">
          {CRITERIA.map(({ key, label }) => (
            <div key={key}>
              <div className="flex justify-between mb-2">
                <span className="text-sm font-medium text-[#3C4A63]">{label}</span>
                <span className="text-sm font-bold text-[#1B2A4A]">
                  {scores[key] || "—"}
                  <span className="text-[#A9B4C4] font-normal">/5</span>
                </span>
              </div>
              <div className="flex items-center gap-2">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button
                    key={n}
                    type="button"
                    onClick={() => setScores((s) => ({ ...s, [key]: n }))}
                    style={n <= scores[key] ? { backgroundColor: "#B8842E", borderColor: "#B8842E" } : {}}
                    className={`w-9 h-9 rounded-sm border-2 text-sm font-bold transition-all ${
                      n <= scores[key] ? "text-white" : "border-[#E1E5E9] text-[#A9B4C4] hover:border-[#B8842E]/60"
                    }`}
                  >
                    {n}
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div>
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Recommandation finale</h3>
        <div className="grid grid-cols-2 gap-2">
          {RECOMMENDATIONS.map((r) => (
            <button
              key={r.value}
              type="button"
              onClick={() => setRecommendation(r.value)}
              style={recommendation === r.value ? { borderColor: r.color, backgroundColor: r.bg, color: r.color } : {}}
              className={`py-3 text-sm font-bold rounded-sm border-2 transition-all ${
                recommendation === r.value ? "" : "border-[#E1E5E9] text-[#9CA3AF] hover:border-gray-300 hover:text-[#5B6B84]"
              }`}
            >
              {r.label}
            </button>
          ))}
        </div>
      </div>

      <FTextarea
        label="Commentaire à l'auteur (public)"
        placeholder="Rédigez votre rapport destiné à l'auteur. Soyez constructif et précis."
        rows={5}
        value={publicComment}
        onChange={(e) => setPublicComment(e.target.value)}
      />

      <div className="rounded-sm overflow-hidden border border-[rgba(255,255,255,0.04)]">
        <div className="bg-[#1B2A4A] px-4 py-3">
          <span className="text-xs font-bold text-white uppercase tracking-wider">Commentaire confidentiel — PC Chair uniquement</span>
        </div>
        <div className="bg-[#163A54] p-4">
          <p className="text-[11px] text-[#9CA3AF] mb-3">Ce commentaire ne sera jamais visible par l'auteur.</p>
          <textarea
            rows={4}
            value={privateComment}
            onChange={(e) => setPrivateComment(e.target.value)}
            placeholder="Observations confidentielles pour le PC Chair…"
            className="w-full px-3 py-2.5 text-sm bg-[#1B2A4A] border border-[rgba(255,255,255,0.07)] rounded-sm text-[#B7C2D0] placeholder:text-[#5B6B84] focus:outline-none focus:border-[rgba(255,255,255,0.15)] resize-none"
          />
        </div>
      </div>

      {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}

      <div className="flex justify-between pt-2 border-t border-[rgba(28,28,46,0.07)]">
        <Btn variant="secondary" onClick={handleSaveDraft} disabled={savingDraft || submitting}>
          {savingDraft ? "Enregistrement…" : "Enregistrer le brouillon"}
        </Btn>
        <Btn onClick={handleSubmit} disabled={submitting || savingDraft}>
          {submitting ? "Envoi…" : "Envoyer le rapport"}
        </Btn>
      </div>
    </Card>
  );
}

function ConflictModal({
  assignmentId,
  onClose,
  onDeclared,
}: {
  assignmentId: number;
  onClose: () => void;
  onDeclared: () => void;
}) {
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleConfirm = async () => {
    if (!reason.trim()) {
      setError("Précisez le motif du conflit.");
      return;
    }
    setError(null);
    setIsSubmitting(true);
    try {
      await declareReviewConflict(assignmentId, reason);
      onDeclared();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de déclarer le conflit."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal title="Déclarer un conflit d'intérêt" onClose={onClose}>
      <div className="space-y-4">
        <div className="p-3.5 bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm">
          <p className="text-xs text-[#8C6420] leading-relaxed">
            Cette déclaration vous retirera de l'évaluation. Le PC Chair sera notifié et procédera à une
            réassignation vers un autre relecteur.
          </p>
        </div>
        <FTextarea
          label="Motif du conflit"
          placeholder="Ex : collaboration récente avec un auteur, compétition directe, relation personnelle…"
          rows={4}
          value={reason}
          onChange={(e) => setReason(e.target.value)}
        />
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <div className="flex justify-end gap-3">
          <Btn variant="secondary" onClick={onClose}>
            Annuler
          </Btn>
          <Btn variant="danger" onClick={handleConfirm} disabled={isSubmitting}>
            <AlertCircle size={13} /> {isSubmitting ? "Envoi…" : "Confirmer le conflit"}
          </Btn>
        </div>
      </div>
    </Modal>
  );
}
