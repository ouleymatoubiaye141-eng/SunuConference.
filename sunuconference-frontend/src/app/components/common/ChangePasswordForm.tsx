import { useState, type FormEvent } from "react";
import { FInput, Btn } from "./primitives";
import { changePasswordRequest } from "../../../lib/auth-endpoints";
import { extractApiErrorMessage } from "../../../lib/api-client";

export function ChangePasswordForm({ onSuccess, submitLabel = "Enregistrer" }: { onSuccess?: () => void; submitLabel?: string }) {
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(false);

    if (newPassword !== confirmation) {
      setError("Les deux mots de passe ne correspondent pas.");
      return;
    }

    setIsSubmitting(true);
    try {
      await changePasswordRequest({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmation,
      });
      setCurrentPassword("");
      setNewPassword("");
      setConfirmation("");
      setSuccess(true);
      onSuccess?.();
    } catch (err) {
      setError(extractApiErrorMessage(err, "Impossible de changer le mot de passe."));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form className="space-y-3" onSubmit={handleSubmit}>
      <FInput
        label="Mot de passe actuel"
        placeholder="••••••••"
        type="password"
        value={currentPassword}
        onChange={(e) => setCurrentPassword(e.target.value)}
        required
        autoComplete="current-password"
      />
      <FInput
        label="Nouveau mot de passe"
        placeholder="••••••••"
        type="password"
        value={newPassword}
        onChange={(e) => setNewPassword(e.target.value)}
        required
        autoComplete="new-password"
      />
      <FInput
        label="Confirmer le nouveau mot de passe"
        placeholder="••••••••"
        type="password"
        value={confirmation}
        onChange={(e) => setConfirmation(e.target.value)}
        required
        autoComplete="new-password"
      />
      {error && <p className="text-xs text-[#B4472F] bg-[#FBE3DE] border border-[#F0C4B8] rounded-sm px-3 py-2">{error}</p>}
      {success && <p className="text-xs text-[#2F7A4F] bg-[#E1F3E7] border border-[#C3E5CE] rounded-sm px-3 py-2">Mot de passe modifié avec succès.</p>}
      <Btn type="submit" disabled={isSubmitting} className="w-full justify-center">
        {isSubmitting ? "Enregistrement…" : submitLabel}
      </Btn>
    </form>
  );
}
