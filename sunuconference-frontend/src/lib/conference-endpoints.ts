import { apiClient } from "./api-client";
import type { ApiEnvelope, Conference, ConferenceSession, Topic } from "./types";

export async function listPublicConferences(): Promise<Conference[]> {
  const { data } = await apiClient.get<ApiEnvelope<Conference[]>>("/conferences");
  return data.data;
}

export async function getPublicConference(conferenceId: number | string): Promise<Conference> {
  const { data } = await apiClient.get<ApiEnvelope<Conference>>(`/conferences/${conferenceId}`);
  return data.data;
}

export async function listPublicTopics(conferenceId: number | string): Promise<Topic[]> {
  const { data } = await apiClient.get<ApiEnvelope<Topic[]>>(`/conferences/${conferenceId}/topics`);
  return data.data;
}

export async function listPublicProgramme(conferenceId: number | string): Promise<ConferenceSession[]> {
  const { data } = await apiClient.get<ApiEnvelope<ConferenceSession[]>>(`/conferences/${conferenceId}/programme`);
  return data.data;
}
