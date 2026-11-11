import { apiClient } from "./api-client";
import type { ApiEnvelope, DecisionValue, ReviewerAssignment, ReviewSummary, Submission } from "./types";

export async function listChairSubmissions(conferenceId: number | string): Promise<Submission[]> {
  const { data } = await apiClient.get<ApiEnvelope<Submission[]>>(`/chair/conferences/${conferenceId}/submissions`);
  return data.data;
}

export async function getChairSubmission(conferenceId: number | string, submissionId: number | string): Promise<Submission> {
  const { data } = await apiClient.get<ApiEnvelope<Submission>>(
    `/chair/conferences/${conferenceId}/submissions/${submissionId}`,
  );
  return data.data;
}

export async function listSubmissionReviewers(
  conferenceId: number | string,
  submissionId: number | string,
): Promise<ReviewerAssignment[]> {
  const { data } = await apiClient.get<ApiEnvelope<ReviewerAssignment[]>>(
    `/chair/conferences/${conferenceId}/submissions/${submissionId}/reviewers`,
  );
  return data.data;
}

export async function assignReviewerToSubmission(
  conferenceId: number | string,
  submissionId: number | string,
  reviewerId: number,
): Promise<void> {
  await apiClient.post(`/chair/conferences/${conferenceId}/submissions/${submissionId}/reviewers`, {
    reviewer_id: reviewerId,
  });
}

export async function removeReviewerFromSubmission(
  conferenceId: number | string,
  submissionId: number | string,
  reviewerId: number,
): Promise<void> {
  await apiClient.delete(`/chair/conferences/${conferenceId}/submissions/${submissionId}/reviewers`, {
    data: { reviewer_id: reviewerId },
  });
}

export async function getReviewsSummary(
  conferenceId: number | string,
  submissionId: number | string,
): Promise<ReviewSummary> {
  const { data } = await apiClient.get<ApiEnvelope<ReviewSummary>>(
    `/chair/conferences/${conferenceId}/submissions/${submissionId}/reviews-summary`,
  );
  return data.data;
}

export async function recordDecision(
  conferenceId: number | string,
  submissionId: number | string,
  decision: DecisionValue,
  comments: string | null,
): Promise<void> {
  await apiClient.post(`/chair/conferences/${conferenceId}/submissions/${submissionId}/decision`, {
    decision,
    comments,
  });
}
