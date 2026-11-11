import { useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { Btn, Card, FInput, FTextarea, PageHeader } from "../../app/components/common/primitives";
import { createConference, updateConference } from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import type { Conference, ConferenceInput } from "../../lib/types";

function toDateInput(value?: string | null): string {
  if (!value) return "";
  return value.slice(0, 10);
}

function toDateTimeInput(value?: string | null): string {
  if (!value) return "";
  return value.slice(0, 16);
}

const emptyForm: ConferenceInput = {
  name: "",
  acronym: "",
  description: "",
  call_for_papers: "",
  start_date: "",
  end_date: "",
  submission_deadline: "",
  review_deadline: "",
  revision_deadline: "",
  camera_ready_deadline: "",
};

export function ChairConferenceFormPage({ conference, onSaved }: { conference?: Conference | null; onSaved: () => void }) {
  const navigate = useNavigate();
  const isEditing = Boolean(conference);

  const [form, setForm] = useState<ConferenceInput>(
    conference
      ? {
          name: conference.name,
          acronym: conference.acronym ?? "",
          description: conference.description ?? "",
          call_for_papers: conference.call_for_papers ?? "",
          start_date: toDateInput(conference.start_date),
          end_date: toDateInput(conference.end_date),
          submission_deadline: toDateTimeInput(conference.submission_deadline),
          review_deadline: toDateTimeInput(conference.review_deadline),
          revision_deadline: toDateTimeInput(conference.revision_deadline),
          camera_ready_deadline: toDateTimeInput(conference.camera_ready_deadline),
        }
      : emptyForm,
  );
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const update = (field: keyof ConferenceInput, value: string) => setForm((f) => ({ ...f, [field]: value }));

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      if (isEditing && conference) {
        await updateConference(conference.id, form);
      } else {
        await createConference(form);
      }
      onSaved();
      navigate("/chair", { replace: true });
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible d'enregistrer la conférence."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-2xl">
      <PageHeader
        title={isEditing ? "Modifier la conférence" : "Créer une conférence"}
        subtitle={isEditing ? undefined : "Elle sera soumise à validation par un administrateur."}
      />
      <Card className="p-6">
        <form className="space-y-5" onSubmit={handleSubmit}>
          <div className="grid grid-cols-2 gap-4">
            <FInput label="Nom de la conférence" value={form.name} onChange={(e) => update("name", e.target.value)} required />
            <FInput label="Acronyme" value={form.acronym} onChange={(e) => update("acronym", e.target.value)} required />
          </div>
          <FTextarea
            label="Description"
            rows={3}
            value={form.description ?? ""}
            onChange={(e) => update("description", e.target.value)}
          />
          <FTextarea
            label="Appel à communications (Call for Papers)"
            rows={5}
            value={form.call_for_papers ?? ""}
            onChange={(e) => update("call_for_papers", e.target.value)}
          />
          <div className="grid grid-cols-2 gap-4">
            <FInput
              label="Date de début"
              type="date"
              value={form.start_date}
              onChange={(e) => update("start_date", e.target.value)}
              required
            />
            <FInput
              label="Date de fin"
              type="date"
              value={form.end_date}
              onChange={(e) => update("end_date", e.target.value)}
              required
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <FInput
              label="Deadline de soumission"
              type="datetime-local"
              value={form.submission_deadline}
              onChange={(e) => update("submission_deadline", e.target.value)}
              required
            />
            <FInput
              label="Deadline de relecture"
              type="datetime-local"
              value={form.review_deadline}
              onChange={(e) => update("review_deadline", e.target.value)}
              required
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <FInput
              label="Deadline de révision"
              type="datetime-local"
              value={form.revision_deadline}
              onChange={(e) => update("revision_deadline", e.target.value)}
              required
            />
            <FInput
              label="Deadline camera-ready"
              type="datetime-local"
              value={form.camera_ready_deadline}
              onChange={(e) => update("camera_ready_deadline", e.target.value)}
              required
            />
          </div>
          <p className="text-[11px] text-[#9CA3AF]">
            Ordre attendu : soumission ≤ début de conférence, puis relecture, révision et camera-ready, chacune après la précédente.
          </p>

          {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}

          <Btn type="submit" disabled={isSubmitting} className="w-full justify-center py-2.5">
            {isSubmitting ? "Enregistrement…" : isEditing ? "Enregistrer les modifications" : "Créer la conférence"}
          </Btn>
        </form>
      </Card>
    </div>
  );
}
