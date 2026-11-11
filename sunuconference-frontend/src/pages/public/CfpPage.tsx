import { Link } from "react-router-dom";
import { useEffect, useState } from "react";
import { ChevronLeft, Hash, MapPin } from "lucide-react";
import { Card } from "../../app/components/common/primitives";
import { usePublicConferences } from "../../lib/use-public-conferences";
import { listPublicTopics } from "../../lib/conference-endpoints";
import type { Topic } from "../../lib/types";
import { daysUntil, formatDate } from "../../lib/date-format";

export function CfpPage() {
  const { featured, isLoading, error } = usePublicConferences();
  const [topics, setTopics] = useState<Topic[]>([]);

  useEffect(() => {
    if (!featured) return;
    listPublicTopics(featured.id).then(setTopics).catch(() => setTopics([]));
  }, [featured]);

  if (isLoading) {
    return <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center text-sm text-[#9CA3AF]">Chargement…</div>;
  }

  if (error || !featured) {
    return (
      <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center p-6 text-center text-sm text-[#9CA3AF]">
        {error ?? "Aucune conférence n'est publiée pour le moment."}
      </div>
    );
  }

  const remainingDays = daysUntil(featured.submission_deadline);

  return (
    <div className="min-h-screen bg-[#EDF0F2]">
      <div className="bg-[#1B2A4A] py-14 px-6">
        <div className="max-w-3xl mx-auto">
          <Link to="/" className="flex items-center gap-1.5 text-sm text-[#5B6B84] hover:text-[#9CA3AF] mb-5 transition-colors">
            <ChevronLeft size={13} /> Retour à l'accueil
          </Link>
          <div
  className="text-[11px] font-medium uppercase tracking-wider mb-3"
  style={{ color: "#8FA8C4", fontFamily: "'IBM Plex Mono', monospace", letterSpacing: "0.07em" }}
>
  Dossier — Appel à communications
</div>
          <h1 className="text-4xl font-bold text-white mb-2" style={{ fontFamily: "'Zilla Slab', serif" }}>
            {featured.acronym ?? featured.name}
          </h1>
          {featured.acronym && (
            <p className="text-[#9CA3AF] text-lg mb-6" style={{ fontFamily: "'Zilla Slab', serif" }}>
              {featured.name}
            </p>
          )}
          <div className="flex flex-wrap gap-3">
            <Link to="/inscription" className="px-5 py-2.5 bg-[#2F7A4F] text-white font-semibold rounded-sm text-sm hover:bg-[#43A047] transition-colors">
              Soumettre un article →
            </Link>
            <Link to="/programme" className="px-5 py-2.5 border border-white/20 text-white font-medium rounded-sm text-sm hover:bg-white/8 transition-colors">
              Voir le programme
            </Link>
          </div>
        </div>
      </div>

      <div className="max-w-3xl mx-auto px-6 py-12">
        <div className="grid md:grid-cols-3 gap-8">
          <div className="md:col-span-2 space-y-8">
            <div>
              <h2 className="text-lg font-semibold text-[#1B2A4A] mb-3" style={{ fontFamily: "'Zilla Slab', serif" }}>
                À propos de la conférence
              </h2>
              <p className="text-sm text-[#5B6B84] leading-relaxed whitespace-pre-line">
                {featured.description ?? "Aucune description n'a encore été publiée pour cette conférence."}
              </p>
            </div>

            {featured.call_for_papers && (
              <div>
                <h2 className="text-lg font-semibold text-[#1B2A4A] mb-3" style={{ fontFamily: "'Zilla Slab', serif" }}>
                  Instructions aux auteurs
                </h2>
                <p className="text-sm text-[#3C4A63] leading-relaxed whitespace-pre-line">{featured.call_for_papers}</p>
              </div>
            )}

            {topics.length > 0 && (
              <div>
                <h2 className="text-lg font-semibold text-[#1B2A4A] mb-3" style={{ fontFamily: "'Zilla Slab', serif" }}>
                  Pistes thématiques
                </h2>
                <div className="grid grid-cols-2 gap-2">
                  {topics.map((t) => (
                    <div key={t.id} className="flex items-center gap-2 p-2.5 bg-white border border-[rgba(28,28,46,0.07)] rounded-sm text-sm text-[#3C4A63]">
                      <Hash size={12} className="text-[#2F7A4F] shrink-0" />
                      {t.name}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          <div className="space-y-4">
            <Card className="p-5">
 <h3>Dates importantes</h3> 
<h3
  className="text-[11px] font-medium uppercase tracking-wider text-[#1F4E6E] mb-4"
  style={{ fontFamily: "'IBM Plex Mono', monospace" }}
>
  Dates importantes
</h3>
<div className="space-y-3.5">
  {[
    { l: "Soumission d'articles", d: featured.submission_deadline, urgent: true },
    { l: "Fin des relectures", d: featured.review_deadline },
    { l: "Version révisée", d: featured.revision_deadline },
    { l: "Version finale", d: featured.camera_ready_deadline },
  ]
    .filter((r) => r.d)
    .map((r) => (
      <div key={r.l}>
        <p
          className={`text-xs font-semibold ${r.urgent ? "text-[#B4472F]" : "text-[#1B2A4A]"}`}
          style={{ fontFamily: "'IBM Plex Mono', monospace" }}
        >
          {formatDate(r.d)}
        </p>
        <p className="text-[11px] text-[#9CA3AF]">{r.l}</p>
      </div>
    ))}
</div>              <div className="space-y-3.5">
                {[
                  { l: "Soumission d'articles", d: featured.submission_deadline, urgent: true },
                  { l: "Fin des relectures", d: featured.review_deadline },
                  { l: "Version révisée", d: featured.revision_deadline },
                  { l: "Version finale", d: featured.camera_ready_deadline },
                ]
                  .filter((r) => r.d)
                  .map((r) => (
                    <div key={r.l}>
                      <p className={`text-xs font-semibold ${r.urgent ? "text-[#B4472F]" : "text-[#1B2A4A]"}`}>{formatDate(r.d)}</p>
                      <p className="text-[11px] text-[#9CA3AF]">{r.l}</p>
                    </div>
                  ))}
              </div>
            </Card>
            {(featured.start_date || featured.end_date) && (
              <Card className="p-5">
                <h3 className="text-xs font-bold uppercase tracking-wider text-[#1B2A4A] mb-3">Dates de la conférence</h3>
                <div className="flex items-start gap-2">
                  <MapPin size={13} className="text-[#9CA3AF] mt-0.5 shrink-0" />
                  <p className="text-sm font-medium text-[#1B2A4A]">
                    {formatDate(featured.start_date)}
                    {featured.end_date ? ` – ${formatDate(featured.end_date)}` : ""}
                  </p>
                </div>
              </Card>
            )}
            <Card className="p-5 border-[#2F7A4F]/20 bg-[#E1F3E7]">
              <h3 className="text-xs font-bold uppercase tracking-wider text-[#2F7A4F] mb-2">Soumettre maintenant</h3>
              <p className="text-xs text-[#5B6B84] mb-3 leading-relaxed">
                {remainingDays !== null
                  ? remainingDays >= 0
                    ? (
                      <>
                        Il reste <strong className="text-[#B4472F]">{remainingDays} jour{remainingDays > 1 ? "s" : ""}</strong> avant la deadline de soumission.
                      </>
                    )
                    : "La deadline de soumission est passée."
                  : "Deadline de soumission à confirmer."}
              </p>
              <Link to="/inscription" className="w-full inline-block text-center py-2 bg-[#2F7A4F] text-white text-sm rounded-sm font-semibold hover:bg-[#43A047]">
                Créer un compte →
              </Link>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
