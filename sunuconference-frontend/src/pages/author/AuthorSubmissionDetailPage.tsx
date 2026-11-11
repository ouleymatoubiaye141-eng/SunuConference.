import { useEffect, useState, type FormEvent } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { MessageSquare, Upload } from "lucide-react";
import { Btn, Card, FFileInput, FInput, FTextarea, PageHeader, Spinner } from "../../app/components/common/primitives";
import { StatusBadge } from "../../app/components/common/StatusBadge";
import { SubmissionFilesCard } from "../../app/components/common/SubmissionFilesCard";
import {
  getMySubmission,
  submitCameraReady,
  submitRebuttal,
  submitRevision,
} from "../../lib/submission-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDateTime } from "../../lib/date-format";
import type { Submission } from "../../lib/types";

const DECISION_LABELS: Record<string, string> = {
  accept: "Acceptée",
  reject: "Rejetée",
  revision_required: "Révision requise",
};

export function AuthorSubmissionDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const [submission, setSubmission] = useState<Submission | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);

  const load = () => {
    if (!id) return;
    getMySubmission(id)
      .then(setSubmission)
      .catch((err) => setLoadError(extractApiErrorMessage(err, "Impossible de charger cette soumission.")));
  };

  useEffect(load, [id]);

  if (loadError) {
    return <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{loadError}</p>;
  }

  if (!submission) {
    return (
      <div className="flex justify-center py-16">
        <Spinner />
      </div>
    );
  }

  const rebuttalAlreadySent = submission.author_responses?.some((r) => r.type === "pre_decision") ?? false;
  const revisionResponses = submission.author_responses?.filter((r) => r.type === "revision_response") ?? [];

  return (
    <div className="max-w-3xl space-y-6">
      <PageHeader
        eyebrow="Dossier — Auteur"
        title={submission.title}
        subtitle={`${submission.conference?.acronym ?? submission.conference?.name ?? ""} · ${submission.topic?.name ?? ""}`}
        back="Retour à mes soumissions"
        onBack={() => navigate("/auteur")}
        action={<StatusBadge status={submission.status} />}
      />

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
          <div className="mt-4 pt-4 border-t border-[rgba(28,28,46,0.07)]">
            <h4 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-2">Co-auteurs</h4>
            <ul className="text-sm text-[#3C4A63] space-y-1">
              {submission.co_authors.map((c) => (
                <li key={c.id}>
                  {c.name}
                  {c.affiliation ? ` — ${c.affiliation}` : ""}
                </li>
              ))}
            </ul>
          </div>
        )}
      </Card>

      <SubmissionFilesCard submission={submission} />

      {submission.latest_decision && (
        <Card className="p-5 border-[#1B2A4A]/10">
          <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-2">Décision du comité</h3>
          <p className="text-sm font-semibold text-[#1B2A4A]">
            {DECISION_LABELS[submission.latest_decision.decision] ?? submission.latest_decision.decision}
            <span className="text-xs text-[#9CA3AF] font-normal ml-2">{formatDateTime(submission.latest_decision.decided_at)}</span>
          </p>
          {submission.latest_decision.comments && (
            <p className="text-sm text-[#5B6B84] leading-relaxed mt-2 whitespace-pre-line">{submission.latest_decision.comments}</p>
          )}
        </Card>
      )}

      <Card className="p-5">
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">
          Avis des relecteurs {submission.reviews && submission.reviews.length > 0 ? `(${submission.reviews.length})` : ""}
        </h3>
        {!submission.reviews || submission.reviews.length === 0 ? (
          <p className="text-sm text-[#9CA3AF]">Aucun avis publié pour le moment.</p>
        ) : (
          <div className="space-y-3">
            {submission.reviews.map((r, i) => (
              <div key={i} className="p-3 bg-[#F6FAFD] rounded-sm">
                <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{r.public_comment}</p>
                <p className="text-[11px] text-[#9CA3AF] mt-1.5">{formatDateTime(r.submitted_at)}</p>
              </div>
            ))}
          </div>
        )}
      </Card>

      {revisionResponses.length > 0 && (
        <Card className="p-5">
          <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Réponses aux relecteurs</h3>
          <div className="space-y-3">
            {revisionResponses.map((r) => (
              <div key={r.id} className="p-3 bg-[#F6FAFD] rounded-sm">
                <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{r.content}</p>
                <p className="text-[11px] text-[#9CA3AF] mt-1.5">{formatDateTime(r.created_at)}</p>
              </div>
            ))}
          </div>
        </Card>
      )}

      {submission.status === "under_review" && (
        <RebuttalCard
          alreadySent={rebuttalAlreadySent}
          existingContent={submission.author_responses?.find((r) => r.type === "pre_decision")?.content}
          onSubmitted={load}
          submissionId={submission.id}
        />
      )}

      {submission.status === "revision_required" && <RevisionCard submissionId={submission.id} onSubmitted={load} />}

      {submission.status === "accepted" && <CameraReadyCard submissionId={submission.id} onSubmitted={load} />}
    </div>
  );
}

function RebuttalCard({
  submissionId,
  alreadySent,
  existingContent,
  onSubmitted,
}: {
  submissionId: number;
  alreadySent: boolean;
  existingContent?: string;
  onSubmitted: () => void;
}) {
  const [content, setContent] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (alreadySent) {
    return (
      <Card className="p-5">
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-2 flex items-center gap-1.5">
          <MessageSquare size={13} /> Rebuttal envoyé
        </h3>
        <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{existingContent}</p>
        <p className="text-xs text-[#9CA3AF] mt-2">En attente de la décision du comité.</p>
      </Card>
    );
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      await submitRebuttal(submissionId, content);
      onSubmitted();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'envoyer le rebuttal."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="p-5">
      <h3 className="text-sm font-semibold text-[#1B2A4A] mb-1 flex items-center gap-1.5">
        <MessageSquare size={14} /> Réponse pré-décision (rebuttal)
      </h3>
      <p className="text-xs text-[#9CA3AF] mb-3">
        Répondez aux avis des relecteurs avant que le comité ne prenne sa décision finale.
      </p>
      <form className="space-y-3" onSubmit={handleSubmit}>
        <FTextarea rows={5} value={content} onChange={(e) => setContent(e.target.value)} required />
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <Btn type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Envoi…" : "Envoyer le rebuttal"}
        </Btn>
      </form>
    </Card>
  );
}

function RevisionCard({ submissionId, onSubmitted }: { submissionId: number; onSubmitted: () => void }) {
  const [file, setFile] = useState<File | null>(null);
  const [response, setResponse] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    if (!file) {
      setError("Le fichier révisé (PDF) est obligatoire.");
      return;
    }
    setIsSubmitting(true);
    try {
      await submitRevision(submissionId, { file, response_to_reviewers: response });
      onSubmitted();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de soumettre la révision."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="p-5">
      <h3 className="text-sm font-semibold text-[#1B2A4A] mb-1 flex items-center gap-1.5">
        <Upload size={14} /> Soumettre la révision demandée
      </h3>
      <form className="space-y-3 mt-3" onSubmit={handleSubmit}>
        <FFileInput label="Nouveau fichier (PDF)" required fileName={file?.name} onChange={setFile} />
        <FTextarea
          label="Réponse aux relecteurs"
          rows={5}
          value={response}
          onChange={(e) => setResponse(e.target.value)}
          required
        />
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <Btn type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Envoi…" : "Soumettre la révision"}
        </Btn>
      </form>
    </Card>
  );
}

function CameraReadyCard({ submissionId, onSubmitted }: { submissionId: number; onSubmitted: () => void }) {
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    if (!file) {
      setError("Le fichier camera-ready (PDF) est obligatoire.");
      return;
    }
    setIsSubmitting(true);
    try {
      await submitCameraReady(submissionId, file);
      onSubmitted();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de soumettre la version camera-ready."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="p-5 border-[#2F7A4F]/20 bg-[#E1F3E7]">
      <h3 className="text-sm font-semibold text-[#2F7A4F] mb-1 flex items-center gap-1.5">
        <Upload size={14} /> Article accepté — déposez la version finale (camera-ready)
      </h3>
      <form className="space-y-3 mt-3" onSubmit={handleSubmit}>
        <FFileInput label="Fichier camera-ready (PDF)" required fileName={file?.name} onChange={setFile} />
        {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
        <Btn type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Envoi…" : "Soumettre la version finale"}
        </Btn>
      </form>
    </Card>
  );
}
