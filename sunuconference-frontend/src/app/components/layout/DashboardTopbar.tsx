import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Bell, Menu } from "lucide-react";
import { useAuth } from "../../../lib/auth-context";

const ROLE_LABELS: Record<string, string> = {
  author: "Auteur",
  reviewer: "Relecteur",
  chair: "PC Chair",
  admin: "Admin",
};

function initialsFor(name: string): string {
  const parts = name.trim().split(/\s+/);
  const first = parts[0]?.[0] ?? "";
  const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
  return (first + last).toUpperCase();
}

export function DashboardTopbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [notifOpen, setNotifOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate("/");
  };

  if (!user) return null;

  return (
    <header
      className="h-13 flex items-center px-5 gap-3 sticky top-0 z-40 shrink-0 text-white"
      style={{ height: 52, background: "#235E80" }}
    >
      <Link to="/" className="flex items-center gap-2">
        <span className="font-bold text-[15px] tracking-tight" style={{ fontFamily: "'Zilla Slab', serif" }}>
          SUNU <span className="font-normal opacity-85">CONFERENCE</span>
        </span>
      </Link>
      <Menu size={16} className="opacity-70 ml-2 hidden sm:block" />

      <div className="flex-1" />

      <div className="relative">
        <button
          onClick={() => setNotifOpen(!notifOpen)}
          className="relative p-2 rounded-lg hover:bg-white/10 transition-colors"
        >
          <Bell size={16} />
        </button>
        {notifOpen && (
          <div className="absolute right-0 top-10 w-80 bg-white rounded-md border border-[rgba(28,28,46,0.1)] shadow-xl z-50 overflow-hidden text-[#1B2A4A]">
            <div className="px-4 py-3 border-b border-[rgba(28,28,46,0.07)]">
              <span className="text-sm font-semibold">Notifications</span>
            </div>
            <div className="px-4 py-6 text-center text-xs text-[#9CA3AF]">Aucune notification pour le moment.</div>
          </div>
        )}
      </div>

      <Link to="/profil" className="flex items-center gap-2.5 pl-3 border-l border-white/15">
        <div
          className="w-7 h-7 rounded-full flex items-center justify-center text-white text-[11px] font-bold font-mono"
          style={{ background: "rgba(255,255,255,0.18)" }}
        >
          {initialsFor(user.name)}
        </div>
        <div className="hidden md:block">
          <p className="text-xs font-semibold leading-none">{user.name}</p>
          <p className="text-[11px] opacity-70 mt-0.5">{ROLE_LABELS[user.role]}</p>
        </div>
      </Link>
      <button onClick={handleLogout} className="text-xs font-medium opacity-70 hover:opacity-100 transition-opacity pl-2">
        Se déconnecter
      </button>
    </header>
  );
}
