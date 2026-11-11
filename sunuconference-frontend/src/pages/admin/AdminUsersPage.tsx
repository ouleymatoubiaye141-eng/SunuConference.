import { useEffect, useMemo, useState } from "react";
import { Search, Users } from "lucide-react";
import { Card, EmptyState, FInput, PageHeader, Spinner } from "../../app/components/common/primitives";
import { listUsers } from "../../lib/admin-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";
import { formatDate } from "../../lib/date-format";
import type { Role, User } from "../../lib/types";

const ROLE_META: Record<Role, { label: string; bg: string; text: string }> = {
  author: { label: "Auteur", bg: "#E1F3E7", text: "#2F7A4F" },
  reviewer: { label: "Relecteur", bg: "#F5EBD8", text: "#8C6420" },
  chair: { label: "PC Chair", bg: "#EAF3FB", text: "#1F4E6E" },
  admin: { label: "Admin", bg: "#FDF2F8", text: "#9D174D" },
};

function initialsFor(name: string): string {
  const parts = name.trim().split(/\s+/);
  return ((parts[0]?.[0] ?? "") + (parts.length > 1 ? parts[parts.length - 1][0] : "")).toUpperCase();
}

export function AdminUsersPage() {
  const [users, setUsers] = useState<User[] | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState<Role | "all">("all");

  useEffect(() => {
    listUsers()
      .then(setUsers)
      .catch((err) => setError(extractApiErrorMessage(err, "Impossible de charger les utilisateurs.")));
  }, []);

  const filtered = useMemo(() => {
    if (!users) return [];
    return users.filter((u) => {
      const matchesRole = roleFilter === "all" || u.role === roleFilter;
      const q = search.trim().toLowerCase();
      const matchesSearch = !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
      return matchesRole && matchesSearch;
    });
  }, [users, roleFilter, search]);

  return (
    <div>
      <PageHeader title="Utilisateurs" subtitle={users ? `${users.length} utilisateur(s) au total` : undefined} />

      {error && <p className="text-sm text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2 mb-4">{error}</p>}

      <div className="flex items-center gap-3 mb-5">
        <div className="relative flex-1 max-w-xs">
          <Search size={13} className="absolute left-3 top-1/2 -translate-y-1/2 text-[#9CA3AF]" />
          <FInput placeholder="Rechercher un nom ou email…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-7" />
        </div>
        <div className="flex gap-1 bg-[#E7ECF3] p-1 rounded-sm">
          {(["all", "author", "reviewer", "chair", "admin"] as const).map((r) => (
            <button
              key={r}
              onClick={() => setRoleFilter(r)}
              className={`px-2.5 py-1.5 text-xs font-semibold rounded-sm transition-colors ${
                roleFilter === r ? "bg-white text-[#1B2A4A] shadow-sm" : "text-[#5B6B84] hover:text-[#1B2A4A]"
              }`}
            >
              {r === "all" ? "Tous" : ROLE_META[r].label}
            </button>
          ))}
        </div>
      </div>

      {users === null ? (
        <div className="flex justify-center py-16">
          <Spinner />
        </div>
      ) : filtered.length === 0 ? (
        <EmptyState icon={Users} title="Aucun utilisateur" desc="Aucun utilisateur ne correspond à ta recherche." />
      ) : (
        <Card className="overflow-hidden">
          {filtered.map((u, i) => {
            const meta = ROLE_META[u.role];
            return (
              <div
                key={u.id}
                className={`flex items-center gap-3 px-4 py-3 ${i > 0 ? "border-t border-[rgba(28,28,46,0.06)]" : ""}`}
              >
                <div className="w-8 h-8 rounded-full bg-[#1B2A4A] flex items-center justify-center text-white text-[11px] font-bold shrink-0">
                  {initialsFor(u.name)}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-semibold text-[#1B2A4A] truncate">{u.name}</p>
                  <p className="text-xs text-[#9CA3AF] truncate">{u.email}</p>
                </div>
                <span className="text-[10px] font-semibold px-2 py-1 rounded-sm shrink-0" style={{ backgroundColor: meta.bg, color: meta.text }}>
                  {meta.label}
                </span>
                <span className="text-xs text-[#9CA3AF] shrink-0 w-24 text-right">{formatDate(u.created_at)}</span>
              </div>
            );
          })}
        </Card>
      )}
    </div>
  );
}
