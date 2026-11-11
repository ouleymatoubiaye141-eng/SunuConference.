import type { ReactElement } from "react";
import { NavLink } from "react-router-dom";
import {
  LayoutDashboard, FileText, Users, Calendar, BarChart2, Plus, Grid,
  UserCheck, Clock, Globe, Inbox, Settings,
} from "lucide-react";
import type { Role } from "../../../lib/types";

const ROLE_LABELS: Record<Role, string> = {
  author: "Auteur",
  reviewer: "Relecteur",
  chair: "PC Chair",
  admin: "Admin",
};

interface NavItem {
  to: string;
  label: string;
  icon: React.ElementType;
  divider?: boolean;
  end?: boolean;
}

const NAV: Record<Role, NavItem[]> = {
  author: [
    { to: "/auteur", label: "Mes soumissions", icon: FileText, end: true },
    { to: "/auteur/nouvelle-soumission", label: "Nouvelle soumission", icon: Plus },
  ],
  reviewer: [{ to: "/relecteur", label: "Articles assignés", icon: FileText, end: true }],
  chair: [
    { to: "/chair", label: "Vue d'ensemble", icon: LayoutDashboard, end: true },
    { to: "/chair/soumissions", label: "Soumissions", icon: FileText },
    { to: "/chair/relecteurs", label: "Relecteurs", icon: Users, divider: true },
    { to: "/chair/assignations", label: "Assignation topics", icon: Grid },
    { to: "/chair/sessions", label: "Sessions", icon: Calendar },
    { to: "/chair/comite", label: "Comité", icon: UserCheck, divider: true },
    { to: "/chair/dates-limites", label: "Dates limites", icon: Clock },
    { to: "/chair/publier", label: "Publier le programme", icon: Globe },
    { to: "/chair/statistiques", label: "Statistiques", icon: BarChart2, divider: true },
    { to: "/chair/creer", label: "Créer une conférence", icon: Plus },
  ],
  admin: [
    { to: "/admin", label: "Tableau de bord", icon: LayoutDashboard, end: true },
    { to: "/admin/en-attente", label: "Demandes en attente", icon: Inbox },
    { to: "/admin/conferences", label: "Toutes les conférences", icon: Globe },
    { to: "/admin/utilisateurs", label: "Utilisateurs", icon: Users },
  ],
};

const SIDEBAR_BG = "#1B212C";
const ACTIVE_BLUE = "#235E80";

export function Sidebar({ role }: { role: Role }): ReactElement {
  const items = NAV[role];

  return (
    <aside className="w-60 flex flex-col shrink-0 overflow-y-auto text-white" style={{ background: SIDEBAR_BG }}>
      <div className="px-5 pt-4 pb-1.5">
        <p className="text-[10.5px] font-bold uppercase tracking-widest" style={{ color: "#7C8798" }}>
          {ROLE_LABELS[role]}
        </p>
      </div>

      <nav className="px-2.5 pb-2">
        {items.map((item, i) => {
          const Icon = item.icon;
          return (
            <div key={item.to}>
              {item.divider && i > 0 && <div className="my-2 mx-2.5 h-px" style={{ background: "rgba(255,255,255,0.08)" }} />}
              <NavLink
                to={item.to}
                end={item.end}
                className={({ isActive }) =>
                  `w-full flex items-center gap-2.5 px-3 py-2.5 rounded-sm text-xs transition-all text-left mb-0.5 ${
                    isActive ? "font-medium text-white" : "text-[#C6CDD7] hover:text-white hover:bg-white/5"
                  }`
                }
                style={({ isActive }) => (isActive ? { backgroundColor: ACTIVE_BLUE } : {})}
              >
                <Icon size={13} className="shrink-0" />
                {item.label}
              </NavLink>
            </div>
          );
        })}
      </nav>

      <div className="mt-auto px-5 py-2 border-t" style={{ borderColor: "rgba(255,255,255,0.08)" }}>
        <p className="text-[10.5px] font-bold uppercase tracking-widest mb-1.5" style={{ color: "#7C8798" }}>
          Paramétrage
        </p>
      </div>
      <nav className="px-2.5 pb-3">
        <NavLink
          to="/profil"
          className="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-sm text-xs text-[#C6CDD7] hover:text-white hover:bg-white/5 transition-colors"
        >
          <Settings size={13} /> Profil &amp; paramètres
        </NavLink>
      </nav>
    </aside>
  );
}
