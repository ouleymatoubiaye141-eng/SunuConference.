import { apiClient } from "./api-client";
import type {
  ApiEnvelope,
  Conference,
  ConferenceInput,
  ConferenceSession,
  Decision,
  DecisionValue,
  ReviewerAssignment,
  ReviewSummary,
  Submission,
  Topic,
  User,
} from "./types";

export async function getMyConference(): Promise<Conference | null> {
  const { data } = await apiClient.get<ApiEnvelope<Conference | null>>("/chair/conference");
  return data.data;
}

export async function createConference(input: ConferenceInput): Promise<Conference> {
  const { data } = await apiClient.post<ApiEnvelope<Conference>>("/chair/conferences", input);
  return data.data;
}

export async function updateConference(conferenceId: number | string, input: ConferenceInput): Promise<Conference> {
  const { data } = await apiClient.put<ApiEnvelope<Conference>>(`/chair/conferences/${conferenceId}`, input);
  return data.data;
}

export async function listChairTopics(conferenceId: number | string): Promise<Topic[]> {
  const { data } = await apiClient.get<ApiEnvelope<Topic[]>>(`/chair/conferences/${conferenceId}/topics`);
  return data.data;
}

export async function createTopic(
  conferenceId: number | string,
  input: { name: string; description?: string | null },
): Promise<Topic> {
  const { data } = await apiClient.post<ApiEnvelope<Topic>>(`/chair/conferences/${conferenceId}/topics`, input);
  return data.data;
}

export async function updateTopic(
  conferenceId: number | string,
  topicId: number | string,
  input: { name: string; description?: string | null },
): Promise<Topic> {
  const { data } = await apiClient.put<ApiEnvelope<Topic>>(`/chair/conferences/${conferenceId}/topics/${topicId}`, input);
  return data.data;
}

export async function deleteTopic(conferenceId: number | string, topicId: number | string): Promise<void> {
  await apiClient.delete(`/chair/conferences/${conferenceId}/topics/${topicId}`);
}

export async function listTopicReviewers(conferenceId: number | string, topicId: number | string): Promise<User[]> {
  const { data } = await apiClient.get<ApiEnvelope<User[]>>(
    `/chair/conferences/${conferenceId}/topics/${topicId}/reviewers`,
  );
  return data.data;
}

export async function assignReviewerToTopic(
  conferenceId: number | string,
  topicId: number | string,
  reviewerId: number,
): Promise<void> {
  await apiClient.post(`/chair/conferences/${conferenceId}/topics/${topicId}/reviewers`, { reviewer_id: reviewerId });
}

export async function unassignReviewerFromTopic(
  conferenceId: number | string,
  topicId: number | string,
  reviewerId: number,
): Promise<void> {
  await apiClient.delete(`/chair/conferences/${conferenceId}/topics/${topicId}/reviewers`, {
    data: { reviewer_id: reviewerId },
  });
}

export async function listConferenceReviewers(conferenceId: number | string): Promise<User[]> {
  const { data } = await apiClient.get<ApiEnvelope<User[]>>(`/chair/conferences/${conferenceId}/reviewers`);
  return data.data;
}

export async function createReviewer(
  conferenceId: number | string,
  input: { name: string; email: string },
): Promise<User> {
  const { data } = await apiClient.post<ApiEnvelope<{ user: User }>>(
    `/chair/conferences/${conferenceId}/reviewers`,
    input,
  );
  return data.data.user;
}

export async function resendReviewerCredentials(conferenceId: number | string, reviewerId: number): Promise<void> {
  await apiClient.post(`/chair/conferences/${conferenceId}/reviewers/${reviewerId}/resend-credentials`);
}

export async function removeReviewerFromConference(conferenceId: number | string, reviewerId: number): Promise<void> {
  await apiClient.delete(`/chair/conferences/${conferenceId}/reviewers/${reviewerId}`);
}

export async function listChairSubmissions(conferenceId: number | string): Promise<Submission[]> {
  const { data } = await apiClient.get<ApiEnvelope<Submission[]>>(`/chair/conferences/${conferenceId}/submissions`);
  return data.data;
}

export async function getChairSubmission(
  conferenceId: number | string,
  submissionId: number | string,
): Promise<Submission> {
  const { data } = await apiClient.get<ApiEnvelope<Submission>>(
    `/chair/conferences/${conferenceId}/submissions/${submissionId}`,
  );
  return data.data;
}

export async function listSubmissionReviewerAssignments(
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
  comments?: string | null,
): Promise<{ decision: Decision; warnings?: string[] }> {
  const { data } = await apiClient.post<{ message: string; data: Decision; warnings?: string[] }>(
    `/chair/conferences/${conferenceId}/submissions/${submissionId}/decision`,
    { decision, comments },
  );
  return { decision: data.data, warnings: data.warnings };
}

export interface SessionInput {
  title: string;
  room?: string | null;
  session_date: string;
  start_time: string;
  end_time: string;
}

export async function listSessions(conferenceId: number | string): Promise<ConferenceSession[]> {
  const { data } = await apiClient.get<ApiEnvelope<ConferenceSession[]>>(`/chair/conferences/${conferenceId}/sessions`);
  return data.data;
}

export async function createSession(conferenceId: number | string, input: SessionInput): Promise<ConferenceSession> {
  const { data } = await apiClient.post<ApiEnvelope<ConferenceSession>>(
    `/chair/conferences/${conferenceId}/sessions`,
    input,
  );
  return data.data;
}

export async function updateSession(
  conferenceId: number | string,
  sessionId: number | string,
  input: SessionInput,
): Promise<ConferenceSession> {
  const { data } = await apiClient.put<ApiEnvelope<ConferenceSession>>(
    `/chair/conferences/${conferenceId}/sessions/${sessionId}`,
    input,
  );
  return data.data;
}

export async function deleteSession(conferenceId: number | string, sessionId: number | string): Promise<void> {
  await apiClient.delete(`/chair/conferences/${conferenceId}/sessions/${sessionId}`);
}

export async function assignSubmissionToSession(
  conferenceId: number | string,
  sessionId: number | string,
  submissionId: number,
): Promise<void> {
  await apiClient.post(`/chair/conferences/${conferenceId}/sessions/${sessionId}/papers`, {
    submission_id: submissionId,
  });
}

export async function removeSubmissionFromSession(
  conferenceId: number | string,
  sessionId: number | string,
  submissionId: number | string,
): Promise<void> {
  await apiClient.delete(`/chair/conferences/${conferenceId}/sessions/${sessionId}/papers/${submissionId}`);
}

export async function publishProgram(conferenceId: number | string): Promise<Conference> {
  const { data } = await apiClient.post<ApiEnvelope<Conference>>(`/chair/conferences/${conferenceId}/publish`);
  return data.data;
}

export async function unpublishProgram(conferenceId: number | string): Promise<Conference> {
  const { data } = await apiClient.post<ApiEnvelope<Conference>>(`/chair/conferences/${conferenceId}/unpublish`);
  return data.data;
}
