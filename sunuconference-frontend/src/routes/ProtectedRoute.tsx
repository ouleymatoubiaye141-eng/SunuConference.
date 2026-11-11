import type { ReactNode } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../lib/auth-context";
import type { Role } from "../lib/types";

export function ProtectedRoute({ children, allow }: { children: ReactNode; allow?: Role[] }) {
  const { user, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return <div className="min-h-screen bg-[#F8F7F5] flex items-center justify-center text-sm text-[#9CA3AF]">Chargement…</div>;
  }

  if (!user) {
    return <Navigate to="/connexion" replace state={{ from: location.pathname }} />;
  }

  if (user.must_change_password && location.pathname !== "/changer-mot-de-passe") {
    return <Navigate to="/changer-mot-de-passe" replace />;
  }

  if (allow && !allow.includes(user.role)) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}

/** Empêche un utilisateur déjà connecté de revoir les pages connexion/inscription. */
export function GuestOnlyRoute({ children }: { children: ReactNode }) {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return <div className="min-h-screen bg-[#F8F7F5] flex items-center justify-center text-sm text-[#9CA3AF]">Chargement…</div>;
  }

  if (user) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}
