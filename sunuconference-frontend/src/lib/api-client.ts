import axios from "axios";

const TOKEN_STORAGE_KEY = "sunuconference_token";

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? "/api",
  headers: {
    Accept: "application/json",
  },
});

export function getStoredToken(): string | null {
  return localStorage.getItem(TOKEN_STORAGE_KEY);
}

export function setStoredToken(token: string | null): void {
  if (token) {
    localStorage.setItem(TOKEN_STORAGE_KEY, token);
  } else {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
  }
}

// Injecte le token Bearer courant sur chaque requête.
apiClient.interceptors.request.use((config) => {
  const token = getStoredToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Quand le token n'est plus valide, on prévient l'AuthContext (découplé pour
// éviter un import circulaire) qui se charge de déconnecter l'utilisateur.
export const SESSION_EXPIRED_EVENT = "sunuconference:session-expired";

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      window.dispatchEvent(new Event(SESSION_EXPIRED_EVENT));
    }
    return Promise.reject(error);
  },
);

/**
 * Forme standard d'une erreur API Laravel : soit une exception métier
 * ({ message }), soit une erreur de validation ({ message, errors: { field: [msg] } }).
 */
export interface ApiErrorPayload {
  message?: string;
  errors?: Record<string, string[]>;
}

export function extractApiErrorMessage(error: unknown, fallback = "Une erreur est survenue."): string {
  if (axios.isAxiosError(error)) {
    const payload = error.response?.data as ApiErrorPayload | undefined;
    if (payload?.errors) {
      const firstField = Object.values(payload.errors)[0];
      if (firstField?.[0]) return firstField[0];
    }
    if (payload?.message) return payload.message;
  }
  return fallback;
}
