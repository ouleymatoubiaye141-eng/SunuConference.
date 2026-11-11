import { useNavigate } from "react-router-dom";
import { KeyRound } from "lucide-react";
import { Card } from "../../app/components/common/primitives";
import { ChangePasswordForm } from "../../app/components/common/ChangePasswordForm";
import { useAuth } from "../../lib/auth-context";
import { defaultDashboardPathForRole } from "../../lib/auth-endpoints";

export function ForcedPasswordChangePage() {
  const { user, setUser } = useAuth();
  const navigate = useNavigate();

  if (!user) return null;

  const handleSuccess = () => {
    setUser({ ...user, must_change_password: false });
    setTimeout(() => navigate(defaultDashboardPathForRole(user.role), { replace: true }), 900);
  };

  return (
    <div className="min-h-screen bg-[#EDF0F2] flex items-center justify-center p-4">
      <div className="w-full max-w-sm">
        <div className="text-center mb-6">
          <div className="w-10 h-10 rounded-sm bg-[#1B2A4A] flex items-center justify-center mx-auto mb-4">
            <KeyRound size={17} className="text-white" />
          </div>
          <h1 className="text-lg font-bold text-[#1B2A4A]">Changement de mot de passe requis</h1>
          <p className="text-sm text-[#9CA3AF] mt-1">
            Pour des raisons de sécurité, vous devez choisir un nouveau mot de passe avant de continuer.
          </p>
        </div>
        <Card className="p-6">
          <ChangePasswordForm onSuccess={handleSuccess} submitLabel="Changer le mot de passe et continuer" />
        </Card>
      </div>
    </div>
  );
}
