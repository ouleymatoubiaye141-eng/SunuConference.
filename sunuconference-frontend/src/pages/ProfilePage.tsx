import { useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import { ChevronLeft, Settings, Key } from "lucide-react";
import { Card, FInput, Btn } from "../app/components/common/primitives";
import { ChangePasswordForm } from "../app/components/common/ChangePasswordForm";
import { useAuth } from "../lib/auth-context";
import { updateProfileRequest } from "../lib/auth-endpoints";
import { extractApiErrorMessage } from "../lib/api-client";

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

export function ProfilePage() {
  const { user, setUser } = useAuth();
  const navigate = useNavigate();

  const [name, setName] = useState(user?.name ?? "");
  const [affiliation, setAffiliation] = useState(user?.affiliation ?? "");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!user) return null;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(false);
    setIsSubmitting(true);
    try {
      const updated = await updateProfileRequest({ name, affiliation: affiliation || undefined });
      setUser(updated);
      setSuccess(true);
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de mettre à jour le profil."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center p-4 py-10">
      <div className="w-full max-w-lg">
        <button onClick={() => navigate(-1)} className="flex items-center gap-1.5 text-sm text-[#9CA3AF] hover:text-[#5B6B84] mb-6 transition-colors">
          <ChevronLeft size={14} /> Retour
        </button>
        <Card className="overflow-hidden">
          <div className="bg-[#1B2A4A] px-6 py-8 flex items-center gap-5">
            <div className="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center text-white text-xl font-bold">
              {initialsFor(user.name)}
            </div>
            <div>
              <h2 className="text-lg font-bold text-white">{user.name}</h2>
              <p className="text-sm text-[#9CA3AF]">
                {user.email}
                {user.affiliation ? ` · ${user.affiliation}` : ""}
              </p>
              <span className="inline-block mt-1.5 px-2.5 py-0.5 rounded text-xs font-medium border border-white/15 text-white/90">
                {ROLE_LABELS[user.role]}
              </span>
            </div>
          </div>
          <div className="p-6 space-y-6">
            <form onSubmit={handleSubmit}>
              <h3 className="text-sm font-semibold text-[#1B2A4A] mb-4 flex items-center gap-2">
                <Settings size={14} className="text-[#9CA3AF]" /> Informations personnelles
              </h3>
              <div className="space-y-3">
                <FInput label="Nom complet" value={name} onChange={(e) => setName(e.target.value)} required />
                <FInput label="Affiliation" value={affiliation} onChange={(e) => setAffiliation(e.target.value)} />
                <FInput label="E-mail" defaultValue={user.email} type="email" disabled hint="Votre e-mail institutionnel ne peut pas être modifié ici." />
              </div>
              {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2 mt-3">{error}</p>}
              {success && <p className="text-xs text-[#2F7A4F] bg-[#E1F3E7] border border-[#C3E5CE] rounded-sm px-3 py-2 mt-3">Profil mis à jour.</p>}
              <div className="flex justify-end pt-4">
                <Btn type="submit" disabled={isSubmitting}>
                  {isSubmitting ? "Enregistrement…" : "Enregistrer les modifications"}
                </Btn>
              </div>
            </form>
            <div className="pt-4 border-t border-[rgba(28,28,46,0.07)]">
              <h3 className="text-sm font-semibold text-[#1B2A4A] mb-4 flex items-center gap-2">
                <Key size={14} className="text-[#9CA3AF]" /> Changer le mot de passe
              </h3>
              <ChangePasswordForm />
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
}
