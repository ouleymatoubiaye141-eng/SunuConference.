import type { SubmissionStatus } from "../../../lib/types";

const STATUS_META: Record<SubmissionStatus, { label: string; bg: string; text: string }> = {
  submitted: { label: "Soumis", bg: "#E7ECF3", text: "#3C4A63" },
  under_review: { label: "En relecture", bg: "#EAF3FB", text: "#1F4E6E" },
  revision_required: { label: "Révision demandée", bg: "#FBE3DE", text: "#B4472F" },
  revised_submitted: { label: "Révision soumise", bg: "#EAF3FB", text: "#1F4E6E" },
  accepted: { label: "Accepté", bg: "#E1F3E7", text: "#2F7A4F" },
  rejected: { label: "Rejeté", bg: "#FBE3DE", text: "#8C3320" },
  camera_ready_submitted: { label: "Camera-ready soumise", bg: "#E1F3E7", text: "#2F7A4F" },
};

export function StatusBadge({ status }: { status: SubmissionStatus }) {
  const meta = STATUS_META[status];
  return (
    <span
      className="inline-flex items-center px-2.5 py-1 rounded-sm text-[11px] font-semibold"
      style={{ backgroundColor: meta.bg, color: meta.text }}
    >
      {meta.label}
    </span>
  );
}
