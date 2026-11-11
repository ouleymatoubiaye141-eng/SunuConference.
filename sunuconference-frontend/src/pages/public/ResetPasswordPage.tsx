import { useState, type FormEvent } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { ChevronLeft } from "lucide-react";
import { Card, FInput, Btn } from "../../app/components/common/primitives";
import { resetPasswordRequest } from "../../lib/auth-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";

export function ResetPasswordPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const token = searchParams.get("token") ?? "";
  const email = searchParams.get("email") ?? "";

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const missingLinkInfo = !token || !email;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);

    if (password !== passwordConfirmation) {
      setError("Les deux mots de passe ne correspondent pas.");
      return;
    }

    setIsSubmitting(true);
    try {
      await resetPasswordRequest({ token, email, password, password_confirmation: passwordConfirmation });
      setSuccess(true);
      setTimeout(() => navigate("/connexion", { replace: true }), 2000);
    } catch (err) {
      setError(extractApiErrorMessage(err, "Ce lien de réinitialisation est invalide ou a expiré."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        <Link to="/connexion" className="flex items-center gap-1.5 text-sm text-[#9CA3AF] hover:text-[#5B6B84] mb-6 transition-colors">
          <ChevronLeft size={14} /> Retour à la connexion
        </Link>
        <Card className="p-6">
          {missingLinkInfo ? (
            <p className="text-sm text-[#B4472F]">
              Ce lien de réinitialisation est incomplet. Utilisez le lien reçu par e-mail, ou{" "}
              <Link to="/mot-de-passe-oublie" className="font-semibold underline">
                demandez-en un nouveau
              </Link>
              .
            </p>
          ) : success ? (
            <p className="text-sm text-[#2F7A4F]">Votre mot de passe a été réinitialisé. Redirection vers la connexion…</p>
          ) : (
            <>
              <div className="mb-5">
                <h2 className="text-lg font-bold text-[#1B2A4A] mb-1">Réinitialiser le mot de passe</h2>
                <p className="text-xs text-[#9CA3AF] leading-relaxed">Choisissez un nouveau mot de passe pour {email}.</p>
              </div>
              <form className="space-y-4" onSubmit={handleSubmit}>
                <FInput
                  label="Nouveau mot de passe"
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
                  {isSubmitting ? "Réinitialisation…" : "Réinitialiser le mot de passe"}
                </Btn>
              </form>
            </>
          )}
        </Card>
      </div>
    </div>
  );
}
