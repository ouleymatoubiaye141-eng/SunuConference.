import { Link } from "react-router-dom";
import { ArrowRight, Hash } from "lucide-react";
import { usePublicConferences } from "../../lib/use-public-conferences";
import { useEffect, useState } from "react";
import { listPublicTopics } from "../../lib/conference-endpoints";
import type { Topic } from "../../lib/types";
import { daysUntil, formatDate, formatDateRange } from "../../lib/date-format";

const INK = "#161B33";
const PAPER = "#F4EFE3";
const OCHRE = "#B8842E";
const OCHRE_DARK = "#8C6420";
const FOREST = "#2F5233";
const CLAY = "#A23B2C";

export function LandingPage() {
  const { featured, conferences, isLoading, error } = usePublicConferences();
  const [topics, setTopics] = useState<Topic[]>([]);

  useEffect(() => {
    if (!featured) return;
    listPublicTopics(featured.id)
      .then(setTopics)
      .catch(() => setTopics([]));
  }, [featured]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center text-sm text-[#9CA3AF]" style={{ background: PAPER }}>
        Chargement…
      </div>
    );
  }

  if (error || !featured) {
    return (
      <div className="min-h-screen flex items-center justify-center p-6 text-center" style={{ background: PAPER }}>
        <div className="max-w-md">
          <h1 className="text-xl font-bold mb-2" style={{ color: INK, fontFamily: "'Zilla Slab', serif" }}>
            SunuConférence
          </h1>
          <p className="text-sm text-[#9CA3AF]">
            {error ?? "Aucune conférence n'est publiée pour le moment. Revenez bientôt !"}
          </p>
        </div>
      </div>
    );
  }

  const remainingDays = daysUntil(featured.submission_deadline);
  const submissionsOpen = remainingDays !== null && remainingDays >= 0;

  return (
    <div style={{ background: PAPER }}>
      {/* Hero — amphithéâtre photo + voile encre */}
      <section className="relative overflow-hidden" style={{ background: INK, color: "#fff" }}>
        <div
          className="absolute inset-0 pointer-events-none"
          style={{
            backgroundImage: `linear-gradient(180deg, rgba(22,27,51,0.90) 0%, rgba(22,27,51,0.80) 38%, rgba(22,27,51,0.94) 100%), url('/images/hero-conference.jpg')`,
            backgroundSize: "cover",
            backgroundPosition: "center 35%",
          }}
        />
        <div
          className="absolute pointer-events-none"
          style={{
            top: -220,
            left: "50%",
            transform: "translateX(-50%)",
            width: 900,
            height: 520,
            background: `radial-gradient(ellipse at center, rgba(184,132,46,0.16), transparent 68%)`,
          }}
        />

        <div className="relative max-w-5xl mx-auto px-6 py-20">
          <div className="flex items-center gap-2.5 mb-6">
            <span
              className="px-3 py-1 text-[11px] font-medium uppercase tracking-wider rounded-sm border"
              style={{ borderColor: "rgba(244,239,227,0.25)", color: "#B7BAD1" }}
            >
              {formatDateRange(featured.start_date, featured.end_date)}
            </span>
            {submissionsOpen && (
              <span
                className="inline-flex items-center gap-2 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider rounded-sm"
                style={{ border: `1px dashed ${OCHRE}`, color: OCHRE, transform: "rotate(-1.2deg)" }}
              >
                <span className="w-1.5 h-1.5 rounded-full" style={{ background: OCHRE }} />
                Soumissions ouvertes
              </span>
            )}
          </div>

          <h1
            className="text-6xl md:text-7xl font-bold mb-2 leading-[0.95] tracking-tight"
            style={{ fontFamily: "'Zilla Slab', serif" }}
          >
            {featured.acronym ?? featured.name}
          </h1>
          {featured.acronym && (
            <p className="text-xl italic mb-6" style={{ fontFamily: "'Zilla Slab', serif", color: "#9DA1C4" }}>
              {featured.name}
            </p>
          )}
          {featured.description && (
            <p className="text-sm mb-9 max-w-xl leading-relaxed" style={{ color: "#8B8FB0" }}>
              {featured.description}
            </p>
          )}

          <div className="flex flex-wrap gap-3 mb-13">
            <Link
              to="/inscription"
              className="px-5 py-2.5 font-semibold rounded-sm text-sm flex items-center gap-2 transition-colors"
              style={{ background: OCHRE, color: "#1B1206" }}
              onMouseEnter={(e) => (e.currentTarget.style.background = OCHRE_DARK)}
              onMouseLeave={(e) => (e.currentTarget.style.background = OCHRE)}
            >
              Soumettre un article <ArrowRight size={14} />
            </Link>
            <Link
              to="/connexion"
              className="px-5 py-2.5 border font-medium rounded-sm text-sm transition-colors hover:bg-white/8"
              style={{ borderColor: "rgba(244,239,227,0.28)", color: "#fff" }}
            >
              Se connecter
            </Link>
            <Link
              to="/appel-a-communications"
              className="px-5 py-2.5 border font-medium rounded-sm text-sm transition-colors hover:bg-white/8"
              style={{ borderColor: "rgba(244,239,227,0.28)", color: "#fff" }}
            >
              Voir l'appel à communications
            </Link>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 border-t" style={{ borderColor: "rgba(244,239,227,0.14)" }}>
            <div className="pt-6 pr-6 border-r" style={{ borderColor: "rgba(244,239,227,0.14)" }}>
              <div className="text-2xl font-medium" style={{ fontFamily: "'IBM Plex Mono', monospace" }}>
                {topics.length || "—"}
              </div>
              <div className="text-[11px] uppercase tracking-wider mt-1.5" style={{ color: "#6E7296" }}>
                Pistes thématiques
              </div>
            </div>
            <div className="pt-6 pr-6 md:border-r" style={{ borderColor: "rgba(244,239,227,0.14)" }}>
              <div className="text-2xl font-medium" style={{ fontFamily: "'IBM Plex Mono', monospace" }}>
                {formatDate(featured.submission_deadline)}
              </div>
              <div className="text-[11px] uppercase tracking-wider mt-1.5" style={{ color: "#6E7296" }}>
                Deadline soumission
              </div>
            </div>
            {conferences.length > 1 && (
              <div className="pt-6 pr-6 border-r" style={{ borderColor: "rgba(244,239,227,0.14)" }}>
                <div className="text-2xl font-medium" style={{ fontFamily: "'IBM Plex Mono', monospace" }}>
                  {conferences.length}
                </div>
                <div className="text-[11px] uppercase tracking-wider mt-1.5" style={{ color: "#6E7296" }}>
                  Conférences actives
                </div>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* CFP — dossier 01 */}
      <section className="max-w-5xl mx-auto px-6 py-16">
        <div className="grid md:grid-cols-3 gap-12">
          <div className="md:col-span-2">
            <div
              className="text-xs font-medium uppercase tracking-wider mb-2.5"
              style={{ color: OCHRE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
            >
              Dossier 01 — Appel
            </div>
            <h2 className="text-3xl font-semibold mb-5" style={{ color: INK, fontFamily: "'Zilla Slab', serif" }}>
              Appel à communications
            </h2>
            {featured.call_for_papers ? (
              <p className="text-sm leading-relaxed mb-7 whitespace-pre-line" style={{ color: "#5B5A6E" }}>
                {featured.call_for_papers}
              </p>
            ) : (
              <p className="text-sm leading-relaxed mb-7" style={{ color: "#5B5A6E" }}>
                Les organisateurs n'ont pas encore publié le texte complet de l'appel à communications.
              </p>
            )}
            {topics.length > 0 && (
              <div className="grid grid-cols-2 gap-x-6 gap-y-0 mb-6">
                {topics.map((t, i) => (
                  <div
                    key={t.id}
                    className="flex items-baseline gap-2.5 text-sm py-1.5 border-b"
                    style={{ color: INK, borderColor: "rgba(22,27,51,0.14)" }}
                  >
                    <span
                      className="text-[11px] w-5 shrink-0"
                      style={{ color: OCHRE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
                    >
                      {String(i + 1).padStart(2, "0")}
                    </span>
                    {t.name}
                  </div>
                ))}
              </div>
            )}
            <Link
              to="/appel-a-communications"
              className="inline-flex text-sm font-semibold items-center gap-1.5 transition-colors"
              style={{ color: FOREST }}
            >
              Voir l'appel complet <ArrowRight size={13} />
            </Link>
          </div>

          <div className="space-y-4">
            <div className="bg-white rounded-sm border p-6" style={{ borderColor: "rgba(22,27,51,0.14)" }}>
              <h3
                className="text-[11px] font-medium uppercase tracking-wider mb-4 pb-3 border-b"
                style={{ color: "#5B5A6E", borderColor: "rgba(22,27,51,0.14)", fontFamily: "'IBM Plex Mono', monospace" }}
              >
                Dates clés
              </h3>
              <div>
                {[
                  { l: "Soumission articles", d: featured.submission_deadline, urgent: true },
                  { l: "Fin des relectures", d: featured.review_deadline },
                  { l: "Version révisée", d: featured.revision_deadline },
                  { l: "Version finale", d: featured.camera_ready_deadline },
                ]
                  .filter((r) => r.d)
                  .map((r, i) => (
                    <div
                      key={r.l}
                      className={`flex justify-between items-baseline py-2 ${i > 0 ? "border-t border-dotted" : ""}`}
                      style={{ borderColor: "rgba(22,27,51,0.14)" }}
                    >
                      <span className="text-[12.5px]" style={{ color: "#5B5A6E" }}>
                        {r.l}
                      </span>
                      <span
                        className="text-[12.5px] font-medium"
                        style={{ color: r.urgent ? CLAY : INK, fontFamily: "'IBM Plex Mono', monospace" }}
                      >
                        {formatDate(r.d)}
                      </span>
                    </div>
                  ))}
              </div>
            </div>

            <div className="rounded-sm p-6" style={{ background: INK, color: "#fff" }}>
              <h3 className="text-base font-semibold mb-1.5" style={{ fontFamily: "'Zilla Slab', serif" }}>
                Prêt à soumettre ?
              </h3>
              <p className="text-xs leading-relaxed mb-4" style={{ color: "#9DA1C4" }}>
                Créez votre compte en 2 minutes et soumettez votre article.
              </p>
              <Link
                to="/inscription"
                className="w-full inline-flex justify-center items-center text-center py-2.5 text-sm rounded-sm font-semibold transition-colors"
                style={{ background: OCHRE, color: "#1B1206" }}
              >
                Créer un compte →
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Pistes thématiques — dossier 02 */}
      {topics.length > 0 && (
        <section className="bg-white border-y" style={{ borderColor: "rgba(22,27,51,0.14)" }}>
          <div className="max-w-5xl mx-auto px-6 py-14">
            <div className="flex items-end justify-between mb-7">
              <div>
                <div
                  className="text-xs font-medium uppercase tracking-wider mb-2"
                  style={{ color: OCHRE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
                >
                  Dossier 02 — Pistes
                </div>
                <h2 className="text-2xl font-semibold" style={{ color: INK, fontFamily: "'Zilla Slab', serif" }}>
                  Pistes thématiques
                </h2>
              </div>
              <Link
                to="/programme"
                className="text-sm font-semibold flex items-center gap-1.5"
                style={{ color: FOREST }}
              >
                Voir le programme <ArrowRight size={13} />
              </Link>
            </div>
            <div className="grid md:grid-cols-3 gap-px" style={{ background: "rgba(22,27,51,0.14)", border: "1px solid rgba(22,27,51,0.14)" }}>
              {topics.map((t, i) => (
                <div key={t.id} className="bg-white p-5">
                  <span
                    className="block text-[10.5px] mb-2 tracking-wider"
                    style={{ color: OCHRE_DARK, fontFamily: "'IBM Plex Mono', monospace" }}
                  >
                    TH.{String(i + 1).padStart(2, "0")}
                  </span>
                  <div className="flex items-start gap-2">
                    <Hash size={13} className="shrink-0 mt-0.5" style={{ color: FOREST }} />
                    <div>
                      <div className="text-sm font-semibold" style={{ color: INK }}>
                        {t.name}
                      </div>
                      {t.description && (
                        <div className="text-xs mt-0.5 leading-relaxed" style={{ color: "#9CA3AF" }}>
                          {t.description}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}
    </div>
  );
}
