import { apiClient } from "./api-client";
import type { ApiEnvelope, ReviewerAssignment, Recommendation, Topic } from "./types";

export interface ReviewDraftInput {
  originality_score?: number | null;
  relevance_score?: number | null;
  presentation_score?: number | null;
  overall_score?: number | null;
  public_comment?: string | null;
  private_comment?: string | null;
  recommendation?: Recommendation | null;
}

export interface ReviewSubmitInput {
  originality_score: number;
  relevance_score: number;
  presentation_score: number;
  overall_score: number;
  public_comment: string;
  private_comment?: string | null;
  recommendation: Recommendation;
}

export async function listAssignedTopics(): Promise<Topic[]> {
  const { data } = await apiClient.get<ApiEnvelope<Topic[]>>("/reviewer/topics");
  return data.data;
}

export async function listAssignedSubmissions(): Promise<ReviewerAssignment[]> {
  const { data } = await apiClient.get<ApiEnvelope<ReviewerAssignment[]>>("/reviewer/submissions");
  return data.data;
}

export async function saveReviewDraft(assignmentId: number | string, input: ReviewDraftInput): Promise<void> {
  await apiClient.put(`/reviewer/submissions/${assignmentId}/review`, input);
}

export async function submitReview(assignmentId: number | string, input: ReviewSubmitInput): Promise<void> {
  await apiClient.post(`/reviewer/submissions/${assignmentId}/review/submit`, input);
}

export async function declareReviewConflict(assignmentId: number | string, reason: string): Promise<void> {
  await apiClient.post(`/reviewer/submissions/${assignmentId}/conflict`, { reason });
}
