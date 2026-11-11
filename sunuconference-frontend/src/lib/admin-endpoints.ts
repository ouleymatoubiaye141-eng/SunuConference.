import { apiClient } from "./api-client";
import type { ApiEnvelope, Conference, User } from "./types";

export async function listAllConferences(): Promise<Conference[]> {
  const { data } = await apiClient.get<ApiEnvelope<Conference[]>>("/admin/conferences");
  return data.data;
}

export async function validateConference(conferenceId: number | string): Promise<Conference> {
  const { data } = await apiClient.post<ApiEnvelope<Conference>>(`/admin/conferences/${conferenceId}/validate`);
  return data.data;
}

export async function rejectConference(conferenceId: number | string, rejectionReason: string): Promise<Conference> {
  const { data } = await apiClient.post<ApiEnvelope<Conference>>(`/admin/conferences/${conferenceId}/reject`, {
    rejection_reason: rejectionReason,
  });
  return data.data;
}

export async function listUsers(params?: { role?: string; q?: string }): Promise<User[]> {
  const { data } = await apiClient.get<ApiEnvelope<User[]>>("/admin/users", { params });
  return data.data;
}
