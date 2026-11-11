import { apiClient } from "./api-client";
import type { ApiEnvelope, Submission } from "./types";

export type SubmissionFileType = "initial" | "revised" | "camera-ready";

/**
 * Télécharge un fichier de soumission (nécessite le token Bearer, donc on ne
 * peut pas simplement faire un <a href="..."> vers l'API).
 */
export async function downloadSubmissionFile(
  submissionId: number | string,
  type: SubmissionFileType,
  filename: string,
): Promise<void> {
  const response = await apiClient.get(`/submissions/${submissionId}/download/${type}`, {
    responseType: "blob",
  });
  const url = window.URL.createObjectURL(response.data as Blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

export interface CoAuthorInput {
  name: string;
  email?: string;
  affiliation?: string;
}

export interface CreateSubmissionInput {
  title: string;
  abstract: string;
  keywords: string[];
  file: File;
  co_authors: CoAuthorInput[];
}

export interface SubmitRevisionInput {
  title?: string;
  abstract?: string;
  keywords?: string[];
  file: File;
  response_to_reviewers: string;
}

function buildSubmissionFormData(input: {
  title?: string;
  abstract?: string;
  keywords?: string[];
  file?: File;
  co_authors?: CoAuthorInput[];
  response_to_reviewers?: string;
}): FormData {
  const form = new FormData();
  if (input.title !== undefined) form.append("title", input.title);
  if (input.abstract !== undefined) form.append("abstract", input.abstract);
  (input.keywords ?? []).forEach((kw, i) => form.append(`keywords[${i}]`, kw));
  if (input.file) form.append("file", input.file);
  (input.co_authors ?? []).forEach((coAuthor, i) => {
    form.append(`co_authors[${i}][name]`, coAuthor.name);
    if (coAuthor.email) form.append(`co_authors[${i}][email]`, coAuthor.email);
    if (coAuthor.affiliation) form.append(`co_authors[${i}][affiliation]`, coAuthor.affiliation);
  });
  if (input.response_to_reviewers !== undefined) {
    form.append("response_to_reviewers", input.response_to_reviewers);
  }
  return form;
}

export async function listMySubmissions(): Promise<Submission[]> {
  const { data } = await apiClient.get<ApiEnvelope<Submission[]>>("/author/submissions");
  return data.data;
}

export async function getMySubmission(id: number | string): Promise<Submission> {
  const { data } = await apiClient.get<ApiEnvelope<Submission>>(`/author/submissions/${id}`);
  return data.data;
}

export async function createSubmission(
  conferenceId: number | string,
  topicId: number | string,
  input: CreateSubmissionInput,
): Promise<Submission> {
  const form = buildSubmissionFormData(input);
  const { data } = await apiClient.post<ApiEnvelope<Submission>>(
    `/author/conferences/${conferenceId}/topics/${topicId}/submissions`,
    form,
    { headers: { "Content-Type": "multipart/form-data" } },
  );
  return data.data;
}

export async function submitRevision(id: number | string, input: SubmitRevisionInput): Promise<Submission> {
  const form = buildSubmissionFormData(input);
  const { data } = await apiClient.post<ApiEnvelope<Submission>>(
    `/author/submissions/${id}/revision`,
    form,
    { headers: { "Content-Type": "multipart/form-data" } },
  );
  return data.data;
}

export async function submitCameraReady(id: number | string, file: File): Promise<Submission> {
  const form = buildSubmissionFormData({ file });
  const { data } = await apiClient.post<ApiEnvelope<Submission>>(
    `/author/submissions/${id}/camera-ready`,
    form,
    { headers: { "Content-Type": "multipart/form-data" } },
  );
  return data.data;
}

export async function submitRebuttal(id: number | string, content: string): Promise<void> {
  await apiClient.post(`/author/submissions/${id}/rebuttal`, { content });
}
