import { Link } from "react-router-dom";
import { useEffect, useState } from "react";
import { ChevronLeft, Clock, MapPin } from "lucide-react";
import { usePublicConferences } from "../../lib/use-public-conferences";
import { listPublicTopics, listPublicProgramme } from "../../lib/conference-endpoints";
import type { ConferenceSession, Topic } from "../../lib/types";
import { formatDate, formatDateRange } from "../../lib/date-format";

function formatTimeRange(start: string, end: string): string {
  return `${start.slice(0, 5)} – ${end.slice(0, 5)}`;
}

export function ProgrammePage() {
  const { featured, isLoading, error } = usePublicConferences();
  const [topics, setTopics] = useState<Topic[]>([]);
  const [sessions, setSessions] = useState<ConferenceSession[] | null>(null);

  useEffect(() => {
    if (!featured) return;
    listPublicTopics(featured.id).then(setTopics).catch(() => setTopics([]));
    if (featured.program_published) {
      listPublicProgramme(featured.id).then(setSessions).catch(() => setSessions([]));
    }
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

  const sessionsByDay = (sessions ?? []).reduce<Record<string, ConferenceSession[]>>((acc, s) => {
    const day = s.session_date.slice(0, 10);
    (acc[day] ??= []).push(s);
    return acc;
  }, {});
  const days = Object.keys(sessionsByDay).sort();

  return (
    <div className="min-h-screen bg-[#EDF0F2]">
      <div className="bg-[#1B2A4A] py-12 px-6">
        <div className="max-w-5xl mx-auto">
          <Link to="/" className="flex items-center gap-1.5 text-sm text-[#5B6B84] hover:text-[#9CA3AF] mb-5 transition-colors">
            <ChevronLeft size={13} /> Retour
          </Link>
          <div
  className="text-[11px] font-medium uppercase tracking-wider mb-3"
  style={{ color: "#8FA8C4", fontFamily: "'IBM Plex Mono', monospace", letterSpacing: "0.07em" }}
>
  Dossier — Programme
</div>
<h1 className="text-3xl font-bold text-white mb-1" style={{ fontFamily: "'Zilla Slab', serif" }}></h1>
          <h1 className="text-3xl font-bold text-white mb-1" style={{ fontFamily: "'Zilla Slab', serif" }}>
            Programme {featured.acronym ?? featured.name}
          </h1>
          <p className="text-[#9CA3AF] text-sm">{formatDateRange(featured.start_date, featured.end_date)}</p>
        </div>
      </div>

      <div className="max-w-5xl mx-auto px-6 py-10">
        <div className="grid md:grid-cols-2 gap-6 mb-10">
          <div>
            <h2 className="text-lg font-semibold text-[#1B2A4A] mb-4" style={{ fontFamily: "'Zilla Slab', serif" }}>
              Dates clés
            </h2>
           <div className="space-y-2">
  {[
    { l: "Soumission d'articles" , d: featured.submission_deadline },
    { l: "Fin des relectures", d: featured.review_deadline },
    { l: "Version révisée", d: featured.revision_deadline },
    { l: "Version finale", d: featured.camera_ready_deadline },
    { l: "Conférence", d: featured.start_date },
  ]
    .filter((r) => r.d)
    .map((r, i) => (
      <div key={r.l} className="flex justify-between items-center px-3.5 py-2.5 bg-white border-l-2" style={{ borderColor: "#2E6FA6", borderTop: "1px solid #E1E5E9", borderRight: "1px solid #E1E5E9", borderBottom: "1px solid #E1E5E9" }}>
        <span className="text-sm text-[#5B6B84]">
          <span className="text-[10.5px] mr-2" style={{ color: "#1F4E6E", fontFamily: "'IBM Plex Mono', monospace" }}>
            {String(i + 1).padStart(2, "0")}
          </span>
          {r.l}
        </span>
        <span className="text-sm font-semibold text-[#1B2A4A]" style={{ fontFamily: "'IBM Plex Mono', monospace" }}>
          {formatDate(r.d)}
        </span>
      </div>
    ))}
</div></div>

          {topics.length > 0 && (
            <div>
              <h2 className="text-lg font-semibold text-[#1B2A4A] mb-4" style={{ fontFamily: "'Zilla Slab', serif" }}>
                Pistes thématiques
              </h2>
              <div className="grid grid-cols-1 gap-2">
                {topics.map((t) => (
                  <div key={t.id} className="p-3 bg-white border border-[rgba(28,28,46,0.08)] rounded-sm text-sm text-[#3C4A63]">
                    {t.name}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {!featured.program_published ? (
          <div className="p-5 bg-white border border-dashed border-[rgba(28,28,46,0.15)] rounded-sm text-center">
            <p className="text-sm text-[#5B6B84]">
              Le détail des sessions (salles, horaires, communications) n'a pas encore été publié par les organisateurs.
            </p>
          </div>
        ) : (
          <div>
            <h2 className="text-lg font-semibold text-[#1B2A4A] mb-4" style={{ fontFamily: "'Zilla Slab', serif" }}>
              Sessions
            </h2>
            {!sessions ? (
              <p className="text-sm text-[#9CA3AF]">Chargement du programme…</p>
            ) : days.length === 0 ? (
              <p className="text-sm text-[#9CA3AF]">Aucune session programmée pour le moment.</p>
            ) : (
              <div className="space-y-8">
                {days.map((day) => (
                  <div key={day}>
                    <h3 className="text-sm font-bold text-[#2E6FA6] uppercase tracking-wider mb-3">{formatDate(day)}</h3>
                    <div className="space-y-3">
                      {sessionsByDay[day].map((session) => (
                        <div key={session.id} className="bg-white border border-[rgba(28,28,46,0.08)] rounded-sm p-4">
                          <div className="flex items-center justify-between flex-wrap gap-2 mb-3">
                            <p className="text-sm font-semibold text-[#1B2A4A]">{session.title}</p>
                            <div className="flex items-center gap-3 text-xs text-[#9CA3AF]">
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
                          {session.submissions && session.submissions.length > 0 ? (
                            <div className="space-y-1.5">
                              {session.submissions.map((paper) => (
                                <div key={paper.id} className="p-2.5 bg-[#EDF0F2] rounded-sm">
                                  <p className="text-xs font-medium text-[#1B2A4A]">{paper.title}</p>
                                  {paper.author && <p className="text-[11px] text-[#9CA3AF] mt-0.5">{paper.author.name}</p>}
                                </div>
                              ))}
                            </div>
                          ) : (
                            <p className="text-xs text-[#A9B4C4]">Aucun article programmé pour cette session.</p>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
