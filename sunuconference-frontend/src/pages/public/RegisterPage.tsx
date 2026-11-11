import { useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Card, FInput, Btn } from "../../app/components/common/primitives";
import { useAuth } from "../../lib/auth-context";
import { extractApiErrorMessage } from "../../lib/api-client";
import { defaultDashboardPathForRole } from "../../lib/auth-endpoints";

const ROLE_OPTIONS = [
  { val: "author" as const, label: "Auteur", border: "#C3E5CE", light: "#E1F3E7", text: "#2F7A4F" },
  { val: "chair" as const, label: "PC Chair", border: "#F9B8C6", light: "#EAF3FB", text: "#1F4E6E" },
];

export function RegisterPage() {
  const { register } = useAuth();
  const navigate = useNavigate();

  const [selRole, setSelRole] = useState<"author" | "chair">("author");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [affiliation, setAffiliation] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);

    if (password !== passwordConfirmation) {
      setError("Les deux mots de passe ne correspondent pas.");
      return;
    }

    setIsSubmitting(true);
    try {
      const user = await register({
        name,
        email,
        affiliation: affiliation || undefined,
        role: selRole,
        password,
        password_confirmation: passwordConfirmation,
      });
      navigate(defaultDashboardPathForRole(user.role), { replace: true });
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de créer le compte."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center p-4 py-10">
      <div className="w-full max-w-md">
        <div className="text-center mb-7">
          <h1 className="text-xl font-bold text-[#1B2A4A]">Créer un compte</h1>
          <p className="text-sm text-[#9CA3AF] mt-1">Rejoignez SunuConférence</p>
        </div>
        <Card className="p-6">
          <form className="space-y-5" onSubmit={handleSubmit}>
            <div>
              <label className="block text-sm font-medium text-[#1B2A4A] mb-2">Je suis…</label>
              <div className="grid grid-cols-2 gap-2">
                {ROLE_OPTIONS.map((r) => {
                  const active = selRole === r.val;
                  return (
                    <button
                      key={r.val}
                      type="button"
                      onClick={() => setSelRole(r.val)}
                      style={active ? { borderColor: r.border, backgroundColor: r.light, color: r.text } : {}}
                      className={`py-3 text-sm font-semibold rounded-sm border-2 transition-all ${
                        active ? "" : "border-[rgba(28,28,46,0.1)] text-[#5B6B84] hover:border-gray-300"
                      }`}
                    >
                      {r.label}
                    </button>
                  );
                })}
              </div>
              <p className="text-[11px] text-[#9CA3AF] mt-2">
                Les comptes Relecteur sont créés par un PC Chair, et le compte Admin n'est pas auto-inscriptible.
              </p>
            </div>
            <FInput label="Nom complet" placeholder="Dr. Aminata Diallo" value={name} onChange={(e) => setName(e.target.value)} required />
            <FInput
              label="E-mail institutionnel"
              placeholder="a.diallo@ucad.sn"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="username"
            />
            <FInput
              label="Affiliation"
              placeholder="UCAD — Département Informatique"
              value={affiliation}
              onChange={(e) => setAffiliation(e.target.value)}
            />
            <FInput
              label="Mot de passe"
              placeholder="Min. 8 caractères"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="new-password"
            />
            <FInput
              label="Confirmer le mot de passe"
              placeholder="••••••••"
              type="password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              autoComplete="new-password"
            />
            {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
            <Btn type="submit" disabled={isSubmitting} className="w-full justify-center py-2.5">
              {isSubmitting ? "Création du compte…" : "Créer mon compte"}
            </Btn>
            <p className="text-center text-xs text-[#9CA3AF]">
              Déjà un compte ?{" "}
              <Link to="/connexion" className="text-[#1B2A4A] font-semibold hover:underline">
                Se connecter
              </Link>
            </p>
          </form>
        </Card>
      </div>
    </div>
  );
}
