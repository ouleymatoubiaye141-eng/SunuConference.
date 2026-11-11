import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Bell, BookOpen } from "lucide-react";
import { useAuth } from "../../../lib/auth-context";

const ROLE_LABELS: Record<string, string> = {
  author: "Auteur",
  reviewer: "Relecteur",
  chair: "PC Chair",
  admin: "Admin",
};

const ROLE_DASHBOARD_PATH: Record<string, string> = {
  author: "/auteur",
  reviewer: "/relecteur",
  chair: "/chair",
  admin: "/admin",
};

function initialsFor(name: string): string {
  const parts = name.trim().split(/\s+/);
  const first = parts[0]?.[0] ?? "";
  const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
  return (first + last).toUpperCase();
}

export function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [notifOpen, setNotifOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate("/");
  };

  return (
    <header
      className="h-13 bg-white border-b border-[rgba(28,28,46,0.08)] flex items-center px-4 gap-3 sticky top-0 z-40 shrink-0"
      style={{ height: 52 }}
    >
      <Link to="/" className="flex items-center gap-2 mr-2">
        <div className="w-7 h-7 rounded-sm bg-[#1B2A4A] flex items-center justify-center">
          <BookOpen size={13} className="text-white" />
        </div>
        <span className="font-bold text-[#1B2A4A] text-sm tracking-tight hidden sm:block">SunuConférence</span>
      </Link>

      <div className="flex-1" />

      {/* Liens publics (appel à communications / programme), toujours visibles */}
      <div className="hidden md:flex items-center gap-1 mr-2">
        {user && ROLE_DASHBOARD_PATH[user.role] && (
          <Link
            to={ROLE_DASHBOARD_PATH[user.role]}
            className="px-3 py-1.5 text-xs font-medium text-[#5B6B84] hover:text-[#1B2A4A] rounded hover:bg-gray-100 transition-colors"
          >
            Mon espace
          </Link>
        )}
        <Link to="/appel-a-communications" className="px-3 py-1.5 text-xs font-medium text-[#5B6B84] hover:text-[#1B2A4A] rounded hover:bg-gray-100 transition-colors">
          Appel à communications
        </Link>
        <Link to="/programme" className="px-3 py-1.5 text-xs font-medium text-[#5B6B84] hover:text-[#1B2A4A] rounded hover:bg-gray-100 transition-colors">
          Programme
        </Link>
      </div>

      {user ? (
        <>
          {/* Notifications — brancher sur un vrai flux (table notifications) est prévu pour une phase ultérieure */}
          <div className="relative">
            <button
              onClick={() => setNotifOpen(!notifOpen)}
              className="relative p-2 rounded-sm hover:bg-gray-100 text-[#5B6B84] transition-colors"
            >
              <Bell size={16} />
            </button>
            {notifOpen && (
              <div className="absolute right-0 top-10 w-80 bg-white rounded-sm border border-[rgba(28,28,46,0.1)] shadow-xl z-50 overflow-hidden">
                <div className="px-4 py-3 border-b border-[rgba(28,28,46,0.07)]">
                  <span className="text-sm font-semibold text-[#1B2A4A]">Notifications</span>
                </div>
                <div className="px-4 py-6 text-center text-xs text-[#9CA3AF]">Aucune notification pour le moment.</div>
              </div>
            )}
          </div>

          <Link to="/profil" className="flex items-center gap-2.5 pl-3 border-l border-[rgba(28,28,46,0.08)]">
            <div className="w-7 h-7 rounded-full bg-[#1B2A4A] flex items-center justify-center text-white text-[11px] font-bold">
              {initialsFor(user.name)}
            </div>
            <div className="hidden md:block">
              <p className="text-xs font-semibold text-[#1B2A4A] leading-none">{user.name}</p>
              <p className="text-[11px] text-[#9CA3AF] mt-0.5">
                {ROLE_LABELS[user.role]}
                {user.affiliation ? ` · ${user.affiliation}` : ""}
              </p>
            </div>
          </Link>
          <button
            onClick={handleLogout}
            className="text-xs font-medium text-[#9CA3AF] hover:text-[#B4472F] transition-colors pl-2"
          >
            Se déconnecter
          </button>
        </>
      ) : (
        <div className="flex items-center gap-2 pl-3 border-l border-[rgba(28,28,46,0.08)]">
          <Link to="/connexion" className="px-3 py-1.5 text-xs font-medium text-[#5B6B84] hover:text-[#1B2A4A] rounded hover:bg-gray-100 transition-colors">
            Connexion
          </Link>
          <Link
            to="/inscription"
            className="px-3 py-1.5 text-xs font-medium rounded bg-[#1B2A4A] text-white hover:bg-[#1F4E6E] transition-colors"
          >
            Créer un compte
          </Link>
        </div>
      )}
    </header>
  );
}
