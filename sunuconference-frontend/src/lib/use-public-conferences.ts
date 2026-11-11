import { useEffect, useState } from "react";
import { listPublicConferences } from "./conference-endpoints";
import type { Conference } from "./types";

interface State {
  conferences: Conference[];
  /** La conférence à mettre en avant (la plus récente/à venir) */
  featured: Conference | null;
  isLoading: boolean;
  error: string | null;
}

export function usePublicConferences(): State {
  const [state, setState] = useState<State>({
    conferences: [],
    featured: null,
    isLoading: true,
    error: null,
  });

  useEffect(() => {
    let cancelled = false;

    listPublicConferences()
      .then((conferences) => {
        if (cancelled) return;
        setState({
          conferences,
          featured: conferences[0] ?? null,
          isLoading: false,
          error: null,
        });
      })
      .catch(() => {
        if (cancelled) return;
        setState({
          conferences: [],
          featured: null,
          isLoading: false,
          error: "Impossible de charger les conférences pour le moment.",
        });
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return state;
}
