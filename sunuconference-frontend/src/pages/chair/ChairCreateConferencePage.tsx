import { useEffect, useState } from "react";
import { Navigate } from "react-router-dom";
import { Spinner } from "../../app/components/common/primitives";
import { getMyConference } from "../../lib/chair-endpoints";
import { ChairConferenceFormPage } from "./ChairConferenceFormPage";
import type { Conference } from "../../lib/types";

export function ChairCreateConferencePage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);

  useEffect(() => {
    getMyConference()
      .then(setConference)
      .catch(() => setConference(null));
  }, []);

  if (conference === undefined) {
    return (
      <div className="flex justify-center py-16">
        <Spinner />
      </div>
    );
  }

  // Une conférence pending ou validée existe déjà : pas de création possible,
  // on renvoie vers le tableau de bord (qui, pour un statut "rejected", propose
  // déjà l'édition — donc on laisse passer ce cas-là ici).
  if (conference && conference.status !== "rejected") {
    return <Navigate to="/chair" replace />;
  }

  return <ChairConferenceFormPage conference={null} onSaved={() => {}} />;
}
