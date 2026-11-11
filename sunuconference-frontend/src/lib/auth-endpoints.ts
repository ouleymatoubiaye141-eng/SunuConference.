import { apiClient } from "./api-client";
import type { ApiEnvelope, Role, User } from "./types";

export interface RegisterPayload {
  name: string;
  email: string;
  affiliation?: string;
  role: "author" | "chair";
  password: string;
  password_confirmation: string;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface AuthResult {
  user: User;
  token: string;
}

export async function registerRequest(payload: RegisterPayload): Promise<AuthResult> {
  const { data } = await apiClient.post<ApiEnvelope<AuthResult>>("/auth/register", payload);
  return data.data;
}

export async function loginRequest(payload: LoginPayload): Promise<AuthResult> {
  const { data } = await apiClient.post<ApiEnvelope<AuthResult>>("/auth/login", payload);
  return data.data;
}

export async function logoutRequest(): Promise<void> {
  await apiClient.post("/auth/logout");
}

export async function meRequest(): Promise<User> {
  const { data } = await apiClient.get<ApiEnvelope<User>>("/auth/me");
  return data.data;
}

export async function changePasswordRequest(payload: {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
}): Promise<void> {
  await apiClient.post("/auth/change-password", payload);
}

export async function forgotPasswordRequest(email: string): Promise<string> {
  const { data } = await apiClient.post<{ message: string }>("/auth/forgot-password", { email });
  return data.message;
}

export async function resetPasswordRequest(payload: {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<string> {
  const { data } = await apiClient.post<{ message: string }>("/auth/reset-password", payload);
  return data.message;
}

export async function updateProfileRequest(payload: { name: string; affiliation?: string }): Promise<User> {
  const { data } = await apiClient.put<ApiEnvelope<User>>("/auth/me", payload);
  return data.data;
}

export function defaultDashboardPathForRole(role: Role): string {
  switch (role) {
    case "author":
      return "/auteur";
    case "reviewer":
      return "/relecteur";
    case "chair":
      return "/chair";
    case "admin":
      return "/admin";
  }
}
