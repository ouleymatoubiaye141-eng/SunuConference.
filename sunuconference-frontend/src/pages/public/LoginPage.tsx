import { useState, type FormEvent } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { BookOpen } from "lucide-react";
import { Card, FInput, Btn } from "../../app/components/common/primitives";
import { useAuth } from "../../lib/auth-context";
import { extractApiErrorMessage } from "../../lib/api-client";
import { defaultDashboardPathForRole } from "../../lib/auth-endpoints";

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const from = (location.state as { from?: string } | null)?.from;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      const user = await login({ email, password });
      if (user.must_change_password) {
        navigate("/changer-mot-de-passe", { replace: true });
        return;
      }
      navigate(from ?? defaultDashboardPathForRole(user.role), { replace: true });
    } catch (err) {
      setError(extractApiErrorMessage(err, "Identifiants incorrects."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <div className="w-10 h-10 rounded-sm bg-[#1B2A4A] flex items-center justify-center mx-auto mb-4">
            <BookOpen size={17} className="text-white" />
          </div>
          <h1 className="text-xl font-bold text-[#1B2A4A]">SunuConférence</h1>
          <p className="text-sm text-[#9CA3AF] mt-1">Connectez-vous à votre espace</p>
        </div>
        <Card className="p-6">
          <form className="space-y-4" onSubmit={handleSubmit}>
            <FInput
              label="Adresse e-mail"
              placeholder="vous@institution.sn"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="username"
            />
            <div>
              <FInput
                label="Mot de passe"
                placeholder="••••••••"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoComplete="current-password"
              />
              <div className="flex justify-end mt-1.5">
                <Link to="/mot-de-passe-oublie" className="text-xs text-[#9CA3AF] hover:text-[#5B6B84] transition-colors">
                  Mot de passe oublié ?
                </Link>
              </div>
            </div>
            {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
            <Btn type="submit" disabled={isSubmitting} className="w-full justify-center py-2.5">
              {isSubmitting ? "Connexion…" : "Se connecter"}
            </Btn>
          </form>
          <div className="mt-5 pt-4 border-t border-[rgba(28,28,46,0.07)] text-center">
            <p className="text-xs text-[#9CA3AF]">
              Pas encore de compte ?{" "}
              <Link to="/inscription" className="text-[#1B2A4A] font-semibold hover:underline">
                Créer un compte
              </Link>
            </p>
          </div>
        </Card>
      </div>
    </div>
  );
}
