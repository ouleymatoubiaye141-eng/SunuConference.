import { useEffect, useState } from "react";
import { CheckCircle, Clock, Inbox, XCircle } from "lucide-react";
import { Btn, Card, EmptyState, FTextarea, Modal, PageHeader, Spinner } from "../../app/components/common/primitives";
import { listAllConferences, rejectConference, validateConference } from "../../lib/admin-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDateRange } from "../../lib/date-format";
import type { Conference } from "../../lib/types";
import { toast } from "sonner";

export function AdminPendingConferencesPage() {
  const [conferences, setConferences] = useState<Conference[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [rejectTarget, setRejectTarget] = useState<Conference | null>(null);
  const [rejectionReason, setRejectionReason] = useState("");

  const load = () => {
    listAllConferences()
      .then((all) => setConferences(all.filter((c) => c.status === "pending")))
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les conférences.")));
  };

  useEffect(load, []);

  const handleValidate = async (conference: Conference) => {
    setBusyId(conference.id);
    try {
      await validateConference(conference.id);
      toast.success(`« ${conference.name} » a été validée.`);
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de valider cette conférence."));
    } finally {
      setBusyId(null);
    }
  };

  const handleReject = async () => {
    if (!rejectTarget || !rejectionReason.trim()) return;
    setBusyId(rejectTarget.id);
    try {
      await rejectConference(rejectTarget.id, rejectionReason.trim());
      toast.success(`« ${rejectTarget.name} » a été rejetée.`);
      setRejectTarget(null);
      setRejectionReason("");
      load();
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de rejeter cette conférence."));
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <PageHeader title="Demandes en attente" subtitle={conferences ? `${conferences.length} conférence(s) à examiner` : undefined} />

      {error && <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2 mb-4">{error}</p>}

      {conferences === null ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : conferences.length === 0 ? (
        <EmptyState icon={Inbox} title="Aucune demande en attente" desc="Toutes les demandes de création de conférence ont été traitées." />
      ) : (
        <div className="space-y-4">
          {conferences.map((conference) => (
            <Card key={conference.id} className="p-5">
              <div className="inline-flex items-center gap-1.5 bg-[#F5EBD8] text-[#8C6420] text-[11px] font-semibold px-2 py-1 rounded-sm mb-2">
                <Clock size={11} /> En attente
              </div>
              <h2 className="text-sm font-bold text-[#1B2A4A]">{conference.name}</h2>
              <p className="text-xs text-[#9CA3AF] mt-1">
                Soumise par {conference.chair?.name ?? "—"}
                {conference.chair?.email ? ` · ${conference.chair.email}` : ""}
              </p>
              <p className="text-xs text-[#5B6B84] mt-2">{formatDateRange(conference.start_date, conference.end_date)}</p>
              {conference.description && <p className="text-xs text-[#5B6B84] mt-2 leading-relaxed">{conference.description}</p>}
              <div className="flex gap-2 mt-4">
                <Btn variant="success" size="sm" disabled={busyId === conference.id} onClick={() => handleValidate(conference)} className="flex-1 justify-center">
                  <CheckCircle size={13} /> Valider
                </Btn>
                <Btn
                  variant="danger"
                  size="sm"
                  disabled={busyId === conference.id}
                  onClick={() => setRejectTarget(conference)}
                  className="flex-1 justify-center"
                >
                  <XCircle size={13} /> Rejeter
                </Btn>
              </div>
            </Card>
          ))}
        </div>
      )}

      {rejectTarget && (
        <Modal title={`Rejeter « ${rejectTarget.name} »`} onClose={() => { setRejectTarget(null); setRejectionReason(""); }}>
          <FTextarea
            label="Motif du rejet"
            placeholder="Expliquez pourquoi cette demande est rejetée…"
            value={rejectionReason}
            onChange={(e) => setRejectionReason(e.target.value)}
            required
          />
          <div className="flex gap-2 mt-4">
            <Btn variant="secondary" className="flex-1 justify-center" onClick={() => { setRejectTarget(null); setRejectionReason(""); }}>
              Annuler
            </Btn>
            <Btn
              variant="danger"
              className="flex-1 justify-center"
              disabled={!rejectionReason.trim() || busyId === rejectTarget.id}
              onClick={handleReject}
            >
              Confirmer le rejet
            </Btn>
          </div>
        </Modal>
      )}
    </div>
  );
}
