import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { CheckCircle, Clock, Globe, Users, XCircle } from "lucide-react";
import { PageHeader, Spinner, StatCard } from "../../app/components/common/primitives";
import { listAllConferences, listUsers } from "../../lib/admin-endpoints";
import { useAuth } from "../../lib/auth-context";
import type { Conference, User } from "../../lib/types";

export function AdminDashboardPage() {
  const { user } = useAuth();
  const [conferences, setConferences] = useState<Conference[] | null>(null);
  const [users, setUsers] = useState<User[] | null>(null);

  useEffect(() => {
    listAllConferences().then(setConferences).catch(() => setConferences([]));
    listUsers().then(setUsers).catch(() => setUsers([]));
  }, []);

  const pending = conferences?.filter((c) => c.status === "pending").length ?? null;
  const validated = conferences?.filter((c) => c.status === "validated").length ?? null;
  const rejected = conferences?.filter((c) => c.status === "rejected").length ?? null;

  return (
    <div>
      <PageHeader title="Tableau de bord" subtitle={`Bienvenue ${user?.name ?? ""}`} />

      {conferences === null || users === null ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : (
        <>
          <div className="grid grid-cols-4 gap-4 mb-6">
            <Link to="/admin/en-attente">
              <StatCard label="En attente" value={pending ?? "—"} icon={Clock} color="#B8842E" />
            </Link>
            <StatCard label="Validées" value={validated ?? "—"} icon={CheckCircle} color="#2F7A4F" />
            <StatCard label="Rejetées" value={rejected ?? "—"} icon={XCircle} color="#B4472F" />
            <Link to="/admin/utilisateurs">
              <StatCard label="Utilisateurs" value={users.length} icon={Users} color="#E91E63" />
            </Link>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <Link to="/admin/en-attente" className="block p-5 bg-white border border-[rgba(28,28,46,0.08)] rounded-sm hover:border-[rgba(28,28,46,0.2)] transition-colors">
              <Clock size={18} className="text-[#B8842E] mb-2" />
              <p className="text-sm font-semibold text-[#1B2A4A]">Demandes en attente</p>
              <p className="text-xs text-[#9CA3AF] mt-1">Valider ou rejeter les conférences soumises par les PC Chairs.</p>
            </Link>
            <Link to="/admin/conferences" className="block p-5 bg-white border border-[rgba(28,28,46,0.08)] rounded-sm hover:border-[rgba(28,28,46,0.2)] transition-colors">
              <Globe size={18} className="text-[#2E6FA6] mb-2" />
              <p className="text-sm font-semibold text-[#1B2A4A]">Toutes les conférences</p>
              <p className="text-xs text-[#9CA3AF] mt-1">Vue globale, tous statuts confondus.</p>
            </Link>
          </div>
        </>
      )}
    </div>
  );
}
