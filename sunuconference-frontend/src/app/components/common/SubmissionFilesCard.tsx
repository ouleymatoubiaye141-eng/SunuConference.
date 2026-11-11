import { useState } from "react";
import { Download } from "lucide-react";
import { toast } from "sonner";
import { Card } from "./primitives";
import { downloadSubmissionFile, type SubmissionFileType } from "../../../lib/submission-endpoints";
import { extractApiErrorMessage } from "../../../lib/api-client";
import type { Submission } from "../../../lib/types";

const FILE_ROWS: { type: SubmissionFileType; label: string; pathKey: keyof Submission }[] = [
  { type: "initial", label: "Version initiale", pathKey: "initial_file_path" },
  { type: "revised", label: "Version révisée", pathKey: "revised_file_path" },
  { type: "camera-ready", label: "Version camera-ready", pathKey: "camera_ready_file_path" },
];

export function SubmissionFilesCard({ submission }: { submission: Submission }) {
  const [downloadingType, setDownloadingType] = useState<SubmissionFileType | null>(null);

  const availableRows = FILE_ROWS.filter((row) => {
    const path = submission[row.pathKey];
    return typeof path === "string" && path.length > 0 && path !== "__pending__";
  });

  if (availableRows.length === 0) return null;

  const handleDownload = async (type: SubmissionFileType) => {
    setDownloadingType(type);
    try {
      await downloadSubmissionFile(submission.id, type, `${submission.id}-${type}.pdf`);
    } catch (err) {
      toast.error(extractApiErrorMessage(err, "Impossible de télécharger ce fichier."));
    } finally {
      setDownloadingType(null);
    }
  };

  return (
    <Card className="p-5">
      <h3 className="text-xs font-bold uppercase tracking-wider text-[#9CA3AF] mb-3">Fichiers déposés</h3>
      <div className="space-y-2">
        {availableRows.map((row) => (
          <div key={row.type} className="flex items-center justify-between p-2.5 bg-[#EDF0F2] rounded-sm">
            <p className="text-xs font-semibold text-[#1B2A4A]">{row.label}</p>
            <button
              onClick={() => handleDownload(row.type)}
              disabled={downloadingType === row.type}
              className="p-1.5 text-[#9CA3AF] hover:text-[#1B2A4A] transition-colors disabled:opacity-40"
              aria-label={`Télécharger ${row.label}`}
            >
              <Download size={13} />
            </button>
          </div>
        ))}
      </div>
    </Card>
  );
}
