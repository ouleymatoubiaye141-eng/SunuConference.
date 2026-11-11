import { useEffect, useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { Plus, Trash2 } from "lucide-react";
import { Btn, Card, FInput, FSelect, FTextarea, FFileInput, PageHeader } from "../../app/components/common/primitives";
import { usePublicConferences } from "../../lib/use-public-conferences";
import { listPublicTopics } from "../../lib/conference-endpoints";
import { createSubmission, type CoAuthorInput } from "../../lib/submission-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import type { Topic } from "../../lib/types";

interface CoAuthorRow extends CoAuthorInput {
  key: string;
}

export function AuthorNewSubmissionPage() {
  const navigate = useNavigate();
  const { conferences, isLoading: conferencesLoading } = usePublicConferences();

  const [conferenceId, setConferenceId] = useState<string>("");
  const [topics, setTopics] = useState<Topic[]>([]);
  const [topicId, setTopicId] = useState<string>("");
  const [title, setTitle] = useState("");
  const [abstract, setAbstract] = useState("");
  const [keywordsInput, setKeywordsInput] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [coAuthors, setCoAuthors] = useState<CoAuthorRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Pré-sélectionne la première conférence dès que la liste est chargée.
  useEffect(() => {
    if (!conferenceId && conferences.length > 0) {
      setConferenceId(String(conferences[0].id));
    }
  }, [conferences, conferenceId]);

  useEffect(() => {
    if (!conferenceId) {
      setTopics([]);
      return;
    }
    setTopicId("");
    listPublicTopics(conferenceId)
      .then(setTopics)
      .catch(() => setTopics([]));
  }, [conferenceId]);

  const addCoAuthor = () => {
    setCoAuthors((rows) => [...rows, { key: crypto.randomUUID(), name: "", email: "", affiliation: "" }]);
  };

  const updateCoAuthor = (key: string, field: keyof CoAuthorInput, value: string) => {
    setCoAuthors((rows) => rows.map((r) => (r.key === key ? { ...r, [field]: value } : r)));
  };

  const removeCoAuthor = (key: string) => {
    setCoAuthors((rows) => rows.filter((r) => r.key !== key));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!conferenceId || !topicId) {
      setError("Sélectionnez une conférence et une piste thématique.");
      return;
    }
    if (!file) {
      setError("Le fichier PDF de l'article est obligatoire.");
      return;
    }

    const keywords = keywordsInput
      .split(",")
      .map((k) => k.trim())
      .filter(Boolean);

    setIsSubmitting(true);
    try {
      const submission = await createSubmission(conferenceId, topicId, {
        title,
        abstract,
        keywords,
        file,
        co_authors: coAuthors
          .filter((r) => r.name.trim())
          .map(({ name, email, affiliation }) => ({ name, email, affiliation })),
      });
      navigate(`/auteur/soumissions/${submission.id}`, { replace: true });
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de soumettre l'article."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-2xl">
      <PageHeader eyebrow="Dossier — Auteur" title="Nouvelle soumission" subtitle="Déposer un article" />
      <Card className="p-6">
        <form className="space-y-5" onSubmit={handleSubmit}>
          <FSelect
            label="Conférence"
            required
            value={conferenceId}
            onChange={(e) => setConferenceId(e.target.value)}
            options={
              conferencesLoading
                ? [{ label: "Chargement…", value: "" }]
                : conferences.length === 0
                  ? [{ label: "Aucune conférence ouverte", value: "" }]
                  : conferences.map((c) => ({ label: c.acronym ?? c.name, value: String(c.id) }))
            }
          />
          <FSelect
            label="Piste thématique"
            required
            value={topicId}
            onChange={(e) => setTopicId(e.target.value)}
            options={
              topics.length === 0
                ? [{ label: "Sélectionnez d'abord une conférence", value: "" }]
                : [{ label: "Choisir une piste…", value: "" }, ...topics.map((t) => ({ label: t.name, value: String(t.id) }))]
            }
          />
          <FInput label="Titre de l'article" value={title} onChange={(e) => setTitle(e.target.value)} required />
          <FTextarea label="Résumé" rows={6} value={abstract} onChange={(e) => setAbstract(e.target.value)} required />
          <FInput
            label="Mots-clés"
            placeholder="apprentissage automatique, vision par ordinateur, ..."
            hint="Séparez les mots-clés par des virgules."
            value={keywordsInput}
            onChange={(e) => setKeywordsInput(e.target.value)}
          />
          <FFileInput
            label="Fichier de l'article (PDF, 10 Mo max)"
            required
            fileName={file?.name}
            onChange={setFile}
          />

          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="block text-sm font-medium text-[#1B2A4A]">Co-auteurs</label>
              <button
                type="button"
                onClick={addCoAuthor}
                className="text-xs font-semibold text-[#1B2A4A] hover:underline flex items-center gap-1"
              >
                <Plus size={12} /> Ajouter
              </button>
            </div>
            {coAuthors.length === 0 && <p className="text-xs text-[#9CA3AF]">Aucun co-auteur ajouté.</p>}
            <div className="space-y-3">
              {coAuthors.map((row) => (
                <div key={row.key} className="p-3 border border-[rgba(28,28,46,0.08)] rounded-sm space-y-2">
                  <div className="flex justify-end">
                    <button type="button" onClick={() => removeCoAuthor(row.key)} className="text-[#B4472F] hover:text-[#8C3320]">
                      <Trash2 size={13} />
                    </button>
                  </div>
                  <FInput label="Nom" value={row.name} onChange={(e) => updateCoAuthor(row.key, "name", e.target.value)} required />
                  <div className="grid grid-cols-2 gap-3">
                    <FInput label="E-mail" type="email" value={row.email ?? ""} onChange={(e) => updateCoAuthor(row.key, "email", e.target.value)} />
                    <FInput label="Affiliation" value={row.affiliation ?? ""} onChange={(e) => updateCoAuthor(row.key, "affiliation", e.target.value)} />
                  </div>
                </div>
              ))}
            </div>
          </div>

          {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}

          <Btn type="submit" disabled={isSubmitting} className="w-full justify-center py-2.5">
            {isSubmitting ? "Envoi en cours…" : "Soumettre l'article"}
          </Btn>
        </form>
      </Card>
    </div>
  );
}
