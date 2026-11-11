import { useEffect, useState } from "react";
import { Spinner } from "../../app/components/common/primitives";
import { getMyConference } from "../../lib/chair-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { ChairConferenceFormPage } from "./ChairConferenceFormPage";
import type { Conference } from "../../lib/types";

export function ChairEditConferencePage() {
  const [conference, setConference] = useState<Conference | null | undefined>(undefined);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getMyConference()
      .then(setConference)
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger votre conférence.")));
  }, []);

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

  if (!conference) {
    return <p className="text-sm text-[#9CA3AF]">Créez d'abord votre conférence.</p>;
  }

  return <ChairConferenceFormPage conference={conference} onSaved={() => {}} />;
}
