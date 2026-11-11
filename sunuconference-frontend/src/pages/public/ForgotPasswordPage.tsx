import { useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { ChevronLeft, Mail } from "lucide-react";
import { Card, FInput, Btn } from "../../app/components/common/primitives";
import { forgotPasswordRequest } from "../../lib/auth-endpoints";
import { extractApiErrorMessage } from "../../lib/api-client";

export function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [sent, setSent] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      await forgotPasswordRequest(email);
      setSent(true);
    } catch (err) {
      setError(extractApiErrorMessage(err));
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
          {!sent ? (
            <>
              <div className="mb-5">
                <h2 className="text-lg font-bold text-[#1B2A4A] mb-1">Mot de passe oublié</h2>
                <p className="text-xs text-[#9CA3AF] leading-relaxed">
                  Saisissez votre adresse e-mail. Nous vous enverrons un lien de réinitialisation.
                </p>
              </div>
              <form className="space-y-4" onSubmit={handleSubmit}>
                <FInput label="Adresse e-mail" placeholder="vous@institution.sn" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
                <Btn type="submit" disabled={isSubmitting} className="w-full justify-center py-2.5">
                  {isSubmitting ? "Envoi…" : "Envoyer le lien"}
                </Btn>
              </form>
            </>
          ) : (
            <div className="text-center">
              <div className="w-12 h-12 rounded-full bg-[#E1F3E7] border-4 border-[#C3E5CE] flex items-center justify-center mx-auto mb-3">
                <Mail size={20} className="text-[#2F7A4F]" />
              </div>
              <h2 className="text-base font-bold text-[#1B2A4A] mb-1">E-mail envoyé !</h2>
              <p className="text-xs text-[#9CA3AF] leading-relaxed">
                Si cette adresse correspond à un compte, un lien de réinitialisation vient de lui être envoyé. Vérifiez votre boîte de réception.
              </p>
            </div>
          )}
        </Card>
      </div>
    </div>
  );
}
