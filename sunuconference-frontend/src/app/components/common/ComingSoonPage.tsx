import type { ReactElement } from "react";
import { Construction } from "lucide-react";
import { EmptyState, PageHeader } from "./primitives";

export function ComingSoonPage({ title, desc }: { title: string; desc: string }): ReactElement {
  return (
    <div>
      <PageHeader title={title} />
      <EmptyState icon={Construction} title="Bientôt disponible" desc={desc} />
    </div>
  );
}
