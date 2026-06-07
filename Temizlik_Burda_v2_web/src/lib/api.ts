import {
  Category,
  ChatMessage,
  ConversationMeta,
  Home,
  Listing,
  MessageThread,
  Offer,
  Pagination,
  User,
} from "@/lib/types";

const API_BASE =
  process.env.NEXT_PUBLIC_API_BASE_URL ?? "https://temizciburada.com/api";

type RequestMethod = "GET" | "POST" | "PUT" | "DELETE";

interface RequestOptions {
  method?: RequestMethod;
  token?: string;
  body?: object | FormData;
  cache?: RequestCache;
}

interface ApiFailure {
  success: false;
  error: string;
}

function parseJsonText<T>(raw: string): T {
  const normalized = raw.replace(/^\uFEFF+/, "").trim();
  if (normalized === "") {
    return {} as T;
  }
  return JSON.parse(normalized) as T;
}

async function parseResponseJson<T>(response: Response): Promise<T> {
  const text = await response.text();
  return parseJsonText<T>(text);
}

function isFormData(value: unknown): value is FormData {
  return typeof FormData !== "undefined" && value instanceof FormData;
}

async function parseErrorMessage(response: Response): Promise<string> {
  try {
    const payload = (await parseResponseJson<ApiFailure & { message?: string }>(
      response,
    )) as ApiFailure & { message?: string };
    return payload.error ?? payload.message ?? `HTTP ${response.status}`;
  } catch {
    return `HTTP ${response.status}`;
  }
}

export async function apiRequest<T>(
  path: string,
  options: RequestOptions = {},
): Promise<T> {
  const { method = "GET", token, body, cache = "no-store" } = options;
  const headers: HeadersInit = {};

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const init: RequestInit = { method, headers, cache };

  if (body !== undefined) {
    if (isFormData(body)) {
      init.body = body;
    } else {
      headers["Content-Type"] = "application/json";
      init.body = JSON.stringify(body);
    }
  }

  const response = await fetch(`${API_BASE}${path}`, init);
  if (!response.ok) {
    const errorMessage = await parseErrorMessage(response);
    throw new Error(errorMessage);
  }

  return parseResponseJson<T>(response);
}

export interface AuthResponse {
  success: true;
  token: string;
  user: User;
}

export interface ListResponse<T> {
  success: true;
  data: T[];
}

export interface ListingsResponse {
  success: true;
  data: Listing[];
  pagination: Pagination;
}

export interface ListingDetailResponse {
  success: true;
  listing: Listing;
  offers: Offer[];
}

export interface ProfileResponse {
  success: true;
  user: User;
}

export interface ConversationResponse {
  success: true;
  data: ChatMessage[];
  meta: ConversationMeta;
  has_more: boolean;
}

export interface UnreadMessagesResponse {
  success: true;
  count: number;
}

export interface FavoriteStatusResponse {
  success: true;
  is_favorite: boolean;
}

export interface FavoriteToggleResponse {
  success: true;
  action: "added" | "removed";
  is_favorite: boolean;
  message: string;
}

export interface FavoriteIdsResponse {
  success: true;
  ids: number[];
  favorites?: number[];
}

export interface FavoriteListingsResponse {
  success: true;
  data: Listing[];
  ids: number[];
  favorites?: number[];
}

export interface ReviewItem {
  id: number;
  listing_id: number;
  reviewer_id: number;
  reviewee_id: number;
  rating: number;
  comment?: string | null;
  created_at: string;
  reviewer_name: string;
  reviewer_role: "homeowner" | "worker" | "admin";
  reviewee_name: string;
  reviewee_role: "homeowner" | "worker" | "admin";
  listing_title?: string;
}

export interface ReviewTarget {
  id: number;
  name: string;
  role: "homeowner" | "worker" | "admin";
  rating?: number | null;
  review_count?: number | null;
  is_owner?: boolean;
}

export interface ReviewsResponse {
  success: true;
  data: ReviewItem[];
  summary: {
    count: number;
    average: number;
  };
  viewer: {
    can_review: boolean;
    targets: ReviewTarget[];
  };
}

export interface ReviewCreateResponse {
  success: true;
  review: ReviewItem | null;
  summary: {
    count: number;
    average: number;
  };
}

export interface ReportCreateResponse {
  success: true;
  report_id: number;
  message: string;
}

export type ReportStatus = "open" | "reviewing" | "resolved" | "rejected";

export interface ReportItem {
  id: number;
  reporter_id: number;
  reporter_name: string;
  listing_id?: number | null;
  listing_title?: string | null;
  reported_user_id?: number | null;
  reported_user_name?: string | null;
  reason: "spam" | "fraud" | "abuse" | "wrong_info" | "other";
  message: string;
  status: ReportStatus;
  admin_note?: string | null;
  created_at: string;
  updated_at: string;
}

export interface ReportsResponse {
  success: true;
  scope: "my" | "all";
  data: ReportItem[];
  summary: {
    total: number;
    open: number;
    reviewing: number;
    resolved: number;
    rejected: number;
  };
}

export interface ReportUpdateResponse {
  success: true;
  report: ReportItem;
  message: string;
}

export async function login(email: string, password: string) {
  return apiRequest<AuthResponse>("/auth/login", {
    method: "POST",
    body: { email, password },
  });
}

export async function register(payload: {
  name: string;
  email: string;
  password: string;
  role: "homeowner" | "worker";
  customer_type?: "individual" | "corporate";
  company_name?: string;
  phone?: string;
  city?: string;
}) {
  return apiRequest<AuthResponse>("/auth/register", {
    method: "POST",
    body: payload,
  });
}

export async function getCategories() {
  return apiRequest<ListResponse<Category>>("/categories/index");
}

export async function getListings(query: {
  q?: string;
  cat?: string;
  city?: string;
  status?: "all" | "open" | "in_progress" | "closed" | "cancelled";
  sort?:
    | "newest"
    | "oldest"
    | "budget_high"
    | "budget_low"
    | "offers_high"
    | "views_high";
  page?: number;
}) {
  const params = new URLSearchParams();
  if (query.q) params.set("q", query.q);
  if (query.cat) params.set("cat", query.cat);
  if (query.city) params.set("city", query.city);
  if (query.status && query.status !== "open") params.set("status", query.status);
  if (query.sort && query.sort !== "newest") params.set("sort", query.sort);
  if (query.page && query.page > 1) params.set("page", String(query.page));
  const qs = params.toString();
  return apiRequest<ListingsResponse>(`/listings/index${qs ? `?${qs}` : ""}`);
}

export async function getListingDetail(id: number, token?: string) {
  return apiRequest<ListingDetailResponse>(`/listings/show?id=${id}`, { token });
}

export async function getMyListings(token: string) {
  return apiRequest<ListResponse<Listing>>("/listings/my", { token });
}

export async function createListing(
  token: string,
  payload: {
    home_id: number;
    category_slug: string;
    title: string;
    description: string;
    budget?: number;
    preferred_date?: string;
    preferred_time?: string;
  },
) {
  return apiRequest<{ success: true; message: string; listing_id: number }>(
    "/listings/store",
    { method: "POST", token, body: payload },
  );
}

export async function uploadListingAttachments(
  token: string,
  listingId: number,
  files: File[],
) {
  const form = new FormData();
  form.append("listing_id", String(listingId));
  files.forEach((file) => form.append("attachments[]", file));
  return apiRequest<{ success: true; message: string }>(
    "/listings/attachments",
    { method: "POST", token, body: form },
  );
}

export async function removeListingAttachment(
  token: string,
  attachmentId: number,
) {
  return apiRequest<{ success: true; message: string }>(
    "/listings/attachments",
    {
      method: "DELETE",
      token,
      body: { attachment_id: attachmentId },
    },
  );
}

export async function submitOffer(
  token: string,
  payload: { listing_id: number; price: number; message: string },
) {
  return apiRequest<{ success: true; offer_id: number }>("/offers/store", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function getMyOffers(token: string) {
  return apiRequest<ListResponse<Offer>>("/offers/my", { token });
}

export async function getHomes(token: string) {
  return apiRequest<ListResponse<Home>>("/homes/index", { token });
}

export async function createHome(token: string, payload: FormData) {
  return apiRequest<{ success: true; home_id: number; photo: string | null }>(
    "/homes/index",
    { method: "POST", token, body: payload },
  );
}

export async function getProfile(token: string) {
  return apiRequest<ProfileResponse>("/profile/index", { token });
}

export async function updateProfile(
  token: string,
  payload: Record<string, string>,
) {
  return apiRequest<ProfileResponse>("/profile/index", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function uploadProfileAvatar(token: string, file: File) {
  const form = new FormData();
  form.append("avatar", file);
  return apiRequest<ProfileResponse>("/profile/avatar", {
    method: "POST",
    token,
    body: form,
  });
}

export async function removeProfileAvatar(token: string) {
  const form = new FormData();
  form.append("remove", "1");
  return apiRequest<ProfileResponse>("/profile/avatar", {
    method: "POST",
    token,
    body: form,
  });
}

export async function getMessageThreads(token: string) {
  return apiRequest<{ success: true; data: MessageThread[]; unread_count: number }>(
    "/messages/index?mode=threads",
    { token },
  );
}

export async function getUnreadMessageCount(token: string) {
  return apiRequest<UnreadMessagesResponse>("/messages/index?mode=unread_count", {
    token,
  });
}

export async function getConversation(
  token: string,
  listingId: number,
  contactId: number,
  beforeId?: number,
) {
  const params = new URLSearchParams();
  params.set("mode", "conversation");
  params.set("listing_id", String(listingId));
  params.set("contact_id", String(contactId));
  if (beforeId && beforeId > 0) {
    params.set("before_id", String(beforeId));
  }
  return apiRequest<ConversationResponse>(`/messages/index?${params.toString()}`, {
    token,
  });
}

export async function sendMessage(
  token: string,
  payload: { listing_id: number; receiver_id: number; message: string },
) {
  return apiRequest<{ success: true; message: ChatMessage; meta: ConversationMeta }>(
    "/messages/index?mode=send",
    {
      method: "POST",
      token,
      body: payload,
    },
  );
}

export async function getFavoriteStatus(token: string, listingId: number) {
  return apiRequest<FavoriteStatusResponse>(`/favorites?listing_id=${listingId}`, {
    token,
  });
}

export async function getFavoriteIds(token: string) {
  return apiRequest<FavoriteIdsResponse>("/favorites?mode=ids", { token });
}

export async function getFavorites(token: string) {
  return apiRequest<FavoriteListingsResponse>("/favorites", { token });
}

export async function toggleFavorite(token: string, listingId: number) {
  return apiRequest<FavoriteToggleResponse>("/favorites", {
    method: "POST",
    token,
    body: { listing_id: listingId },
  });
}

export async function getReviews(listingId: number, token?: string) {
  return apiRequest<ReviewsResponse>(`/reviews/index?listing_id=${listingId}`, {
    token,
  });
}

export async function submitReview(
  token: string,
  payload: { listing_id: number; reviewee_id: number; rating: number; comment?: string },
) {
  return apiRequest<ReviewCreateResponse>("/reviews/index", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function submitReport(
  token: string,
  payload: {
    listing_id?: number;
    reported_user_id?: number;
    reason: "spam" | "fraud" | "abuse" | "wrong_info" | "other";
    message: string;
  },
) {
  return apiRequest<ReportCreateResponse>("/reports/index", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function getReports(
  token: string,
  options?: { mode?: "my" | "all"; status?: ReportStatus },
) {
  const params = new URLSearchParams();
  if (options?.mode) params.set("mode", options.mode);
  if (options?.status) params.set("status", options.status);
  const qs = params.toString();
  return apiRequest<ReportsResponse>(`/reports/index${qs ? `?${qs}` : ""}`, {
    token,
  });
}

export async function updateReport(
  token: string,
  payload: { report_id: number; status: ReportStatus; admin_note?: string },
) {
  return apiRequest<ReportUpdateResponse>("/reports/index?mode=update", {
    method: "POST",
    token,
    body: payload,
  });
}

export interface AdApplicationPayload {
  package_key: "baslangic" | "buyume" | "premium";
  company_name: string;
  contact_name: string;
  email?: string;
  phone?: string;
  city?: string;
  monthly_budget?: string;
  note?: string;
  preferred_channel: "whatsapp" | "email";
  draft_text?: string;
  source_url?: string;
}

export interface AdApplicationResponse {
  success: true;
  application_id: number;
  message: string;
}

export interface AdApplication {
  id: number;
  package_key: "baslangic" | "buyume" | "premium";
  package_name: string;
  company_name: string;
  contact_name: string;
  email?: string | null;
  phone?: string | null;
  city?: string | null;
  monthly_budget?: number | null;
  note?: string | null;
  preferred_channel: "whatsapp" | "email";
  status: "new" | "contacted" | "converted" | "closed";
  created_at: string;
  updated_at: string;
}

export interface AdApplicationsResponse {
  success: true;
  scope: "my" | "all";
  data: AdApplication[];
  summary: {
    total: number;
    new: number;
    contacted: number;
    converted: number;
    closed: number;
  };
}

export async function submitAdApplication(
  payload: AdApplicationPayload,
  token?: string,
) {
  return apiRequest<AdApplicationResponse>("/ads/store", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function getAdApplications(
  token: string,
  options?: { mode?: "my" | "all"; limit?: number },
) {
  const params = new URLSearchParams();
  if (options?.mode) params.set("mode", options.mode);
  if (options?.limit) params.set("limit", String(options.limit));
  const qs = params.toString();
  return apiRequest<AdApplicationsResponse>(`/ads/index${qs ? `?${qs}` : ""}`, {
    token,
  });
}

export interface SupportTicket {
  id: number;
  user_id: number;
  user_name: string;
  subject: string;
  status: "open" | "in_progress" | "closed";
  priority: "low" | "normal" | "high";
  admin_note?: string | null;
  created_at: string;
  last_message_at: string;
  message_count: number;
}

export interface SupportMessage {
  id: number;
  ticket_id: number;
  sender_id: number;
  sender_name: string;
  sender_role: "homeowner" | "worker" | "admin";
  message: string;
  created_at: string;
}

export interface SupportTicketsResponse {
  success: true;
  scope: "my" | "all";
  data: SupportTicket[];
  summary: {
    total: number;
    open: number;
    in_progress: number;
    closed: number;
  };
}

export interface SupportTicketDetailResponse {
  success: true;
  ticket: SupportTicket;
  messages: SupportMessage[];
}

export interface SupportTicketCreateResponse {
  success: true;
  ticket_id: number;
  message_id: number;
  message: string;
}

export interface SupportTicketReplyResponse {
  success: true;
  ticket: SupportTicket | null;
  new_message: SupportMessage;
}

export interface SupportTicketUpdateResponse {
  success: true;
  ticket: SupportTicket | null;
  updated_by: number;
}

export async function getSupportTickets(
  token: string,
  options?: { mode?: "my" | "all"; status?: "open" | "in_progress" | "closed"; limit?: number },
) {
  const params = new URLSearchParams();
  if (options?.mode) params.set("mode", options.mode);
  if (options?.status) params.set("status", options.status);
  if (options?.limit) params.set("limit", String(options.limit));
  const qs = params.toString();
  return apiRequest<SupportTicketsResponse>(`/support/index${qs ? `?${qs}` : ""}`, {
    token,
  });
}

export async function getSupportTicketDetail(token: string, ticketId: number) {
  return apiRequest<SupportTicketDetailResponse>(
    `/support/index?mode=detail&ticket_id=${ticketId}`,
    { token },
  );
}

export async function createSupportTicket(
  token: string,
  payload: { subject: string; message: string; priority: "low" | "normal" | "high" },
) {
  return apiRequest<SupportTicketCreateResponse>("/support/index?mode=create", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function replySupportTicket(
  token: string,
  payload: { ticket_id: number; message: string; status?: "open" | "in_progress" | "closed" },
) {
  return apiRequest<SupportTicketReplyResponse>("/support/index?mode=reply", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function updateSupportTicket(
  token: string,
  payload: {
    ticket_id: number;
    status?: "open" | "in_progress" | "closed";
    priority?: "low" | "normal" | "high";
    admin_note?: string;
  },
) {
  return apiRequest<SupportTicketUpdateResponse>("/support/index?mode=update", {
    method: "POST",
    token,
    body: payload,
  });
}

export interface SellerApplication {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  account_type: "individual" | "company";
  full_name: string;
  company_name?: string | null;
  tax_number?: string | null;
  tax_office?: string | null;
  identity_number?: string | null;
  city?: string | null;
  district?: string | null;
  address?: string | null;
  phone: string;
  email: string;
  service_categories: string[];
  portfolio_text?: string | null;
  verification_status:
    | "pending"
    | "under_review"
    | "approved"
    | "rejected"
    | "suspended";
  admin_note?: string | null;
  created_at: string;
  updated_at: string;
  reviewed_at?: string | null;
  reviewed_by?: number | null;
}

export interface SellerApplicationsResponse {
  success: true;
  scope: "my" | "all";
  data: SellerApplication[];
  summary: {
    total: number;
    pending: number;
    under_review: number;
    approved: number;
    rejected: number;
    suspended: number;
  };
}

export interface SellerApplyResponse {
  success: true;
  application_id: number;
  message: string;
}

export interface SellerReviewResponse {
  success: true;
  application: SellerApplication;
}

export async function getSellerApplications(
  token: string,
  options?: {
    mode?: "my" | "all";
    status?: "pending" | "under_review" | "approved" | "rejected" | "suspended";
    limit?: number;
  },
) {
  const params = new URLSearchParams();
  if (options?.mode) params.set("mode", options.mode);
  if (options?.status) params.set("status", options.status);
  if (options?.limit) params.set("limit", String(options.limit));
  const qs = params.toString();
  return apiRequest<SellerApplicationsResponse>(
    `/seller/index${qs ? `?${qs}` : ""}`,
    { token },
  );
}

export async function applySeller(
  token: string,
  payload: {
    account_type: "individual" | "company";
    full_name: string;
    company_name?: string;
    tax_number?: string;
    tax_office?: string;
    identity_number?: string;
    city?: string;
    district?: string;
    address?: string;
    phone: string;
    email: string;
    service_categories?: string[] | string;
    portfolio_text?: string;
  },
) {
  return apiRequest<SellerApplyResponse>("/seller/index?mode=apply", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function reviewSellerApplication(
  token: string,
  payload: {
    application_id: number;
    status: "pending" | "under_review" | "approved" | "rejected" | "suspended";
    admin_note?: string;
  },
) {
  return apiRequest<SellerReviewResponse>("/seller/index?mode=review", {
    method: "POST",
    token,
    body: payload,
  });
}

export interface PlatformNotification {
  id: number;
  user_id: number;
  type: string;
  message: string;
  link?: string | null;
  is_read: boolean;
  created_at: string;
}

export interface NotificationsResponse {
  success: true;
  data: PlatformNotification[];
  unread_count: number;
}

export interface NotificationUnreadResponse {
  success: true;
  count: number;
}

export interface NotificationMarkReadResponse {
  success: true;
  updated: boolean;
  unread_count: number;
}

export interface AdminCategory extends Category {
  listing_count: number;
}

export interface AdminOverviewResponse {
  success: true;
  users: User[];
  categories: AdminCategory[];
  summary: {
    users: number;
    active_users: number;
    workers: number;
    homeowners: number;
    categories: number;
  };
}

export interface AdminUserUpdateResponse {
  success: true;
  user: User;
}

export interface AdminCategorySaveResponse {
  success: true;
  category: AdminCategory;
}

export interface AdminCategoryDeleteResponse {
  success: true;
  deleted_id: number;
}

export async function getAdminOverview(token: string) {
  return apiRequest<AdminOverviewResponse>("/admin/index?mode=overview", {
    token,
  });
}

export async function updateAdminUser(
  token: string,
  payload: { user_id: number; role?: "homeowner" | "worker"; is_active?: 0 | 1 },
) {
  return apiRequest<AdminUserUpdateResponse>("/admin/index?mode=user_update", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function saveAdminCategory(
  token: string,
  payload: { id?: number; name: string; slug: string; icon?: string },
) {
  return apiRequest<AdminCategorySaveResponse>("/admin/index?mode=category_save", {
    method: "POST",
    token,
    body: payload,
  });
}

export async function deleteAdminCategory(token: string, id: number) {
  return apiRequest<AdminCategoryDeleteResponse>(
    "/admin/index?mode=category_delete",
    {
      method: "POST",
      token,
      body: { id },
    },
  );
}

export async function getNotifications(token: string, limit = 40) {
  return apiRequest<NotificationsResponse>(`/notifications/index?limit=${limit}`, {
    token,
  });
}

export async function getNotificationUnreadCount(token: string) {
  return apiRequest<NotificationUnreadResponse>(
    "/notifications/index?mode=unread_count",
    {
      token,
    },
  );
}

export async function markNotificationsRead(
  token: string,
  payload: { id?: number; all?: boolean },
) {
  return apiRequest<NotificationMarkReadResponse>(
    "/notifications/index?mode=mark_read",
    {
      method: "POST",
      token,
      body: payload,
    },
  );
}
