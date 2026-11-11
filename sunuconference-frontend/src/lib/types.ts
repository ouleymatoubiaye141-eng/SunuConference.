export type Role = "author" | "reviewer" | "chair" | "admin";

export interface User {
  id: number;
  name: string;
  email: string;
  affiliation: string | null;
  role: Role;
  must_change_password: boolean;
  created_at: string;
  updated_at: string;
}

export type ConferenceStatus = "pending" | "validated" | "rejected";

export interface Conference {
  id: number;
  chair_id: number;
  name: string;
  acronym: string | null;
  description: string | null;
  call_for_papers: string | null;
  start_date: string | null;
  end_date: string | null;
  submission_deadline: string | null;
  review_deadline: string | null;
  revision_deadline: string | null;
  camera_ready_deadline: string | null;
  status: ConferenceStatus;
  rejection_reason: string | null;
  program_published: boolean;
  created_at: string;
  updated_at: string;
  chair?: { id: number; name: string; email: string; affiliation: string | null } | null;
}

export interface Topic {
  id: number;
  conference_id: number;
  name: string;
  description: string | null;
}

export type SubmissionStatus =
  | "submitted"
  | "under_review"
  | "revision_required"
  | "revised_submitted"
  | "accepted"
  | "rejected"
  | "camera_ready_submitted";

export interface CoAuthor {
  id: number;
  name: string;
  email: string | null;
  affiliation: string | null;
  position: number;
}

export interface PublicReview {
  public_comment: string | null;
  recommendation: string | null;
  submitted_at: string | null;
}

export type AuthorResponseType = "pre_decision" | "revision_response";

export interface AuthorResponseItem {
  id: number;
  submission_id: number;
  author_id: number;
  type: AuthorResponseType;
  content: string;
  created_at: string;
  updated_at: string;
}

export type DecisionValue = "accept" | "reject" | "revision_required";

export interface Decision {
  id: number;
  submission_id: number;
  chair_id: number;
  decision: DecisionValue;
  comments: string | null;
  decided_at: string;
}

export interface ConferenceSession {
  id: number;
  conference_id: number;
  title: string;
  room: string | null;
  session_date: string;
  start_time: string;
  end_time: string;
  submissions?: Submission[];
}

export interface Submission {
  id: number;
  conference_id: number;
  topic_id: number;
  conference_session_id: number | null;
  author_id: number;
  title: string;
  abstract: string;
  keywords: string[] | null;
  initial_file_path: string | null;
  revised_file_path: string | null;
  camera_ready_file_path: string | null;
  status: SubmissionStatus;
  submitted_at: string | null;
  revised_at: string | null;
  camera_ready_submitted_at: string | null;
  created_at: string;
  updated_at: string;
  conference?: Conference;
  topic?: Topic;
  conference_session?: ConferenceSession | null;
  author?: User;
  co_authors?: CoAuthor[];
  reviews?: PublicReview[];
  author_responses?: AuthorResponseItem[];
  latest_decision?: Decision | null;
  decisions?: Decision[];
  reviewer_submissions?: ReviewerAssignment[];
}

export interface ApiEnvelope<T> {
  message?: string;
  data: T;
}

export type ReviewStatus = "draft" | "submitted" | "conflict_declared";
export type Recommendation = "accept" | "minor_revision" | "major_revision" | "reject";

export interface Review {
  id: number;
  reviewer_submission_id: number;
  originality_score: number | null;
  relevance_score: number | null;
  presentation_score: number | null;
  overall_score: number | null;
  public_comment: string | null;
  private_comment: string | null;
  recommendation: Recommendation | null;
  conflict_of_interest: boolean;
  conflict_reason: string | null;
  submitted_at: string | null;
  status: ReviewStatus;
}

export interface ReviewerAssignment {
  id: number;
  submission_id: number;
  reviewer_id: number;
  assigned_by: number;
  assigned_at: string;
  submission?: Submission;
  reviewer?: User;
  review?: Review | null;
}

export interface ConferenceInput {
  name: string;
  acronym: string;
  description?: string | null;
  call_for_papers?: string | null;
  start_date: string;
  end_date: string;
  submission_deadline: string;
  review_deadline: string;
  revision_deadline: string;
  camera_ready_deadline: string;
}

export interface SubmittedReviewWithReviewer extends Review {
  reviewer_submission?: ReviewerAssignment & { reviewer?: User };
}

export interface ReviewSummary {
  submission_id: number;
  submitted_reviews: SubmittedReviewWithReviewer[];
  average_scores: {
    originality: number | null;
    relevance: number | null;
    presentation: number | null;
    overall: number | null;
  };
  recommendations_distribution: Partial<Record<Recommendation, number>>;
  conflicts: ReviewerAssignment[];
  pending_assignments: ReviewerAssignment[];
}
