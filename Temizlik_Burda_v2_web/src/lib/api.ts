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
  body?: Record<string, unknown> | FormData;
  cache?: RequestCache;
}

interface ApiFailure {
  success: false;
  error: string;
}

function parseJsonText<T>(raw: string): T {
  const normalized = raw.replace(/^\uFEFF/, "").trim();
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

export async function getListingDetail(id: number) {
  return apiRequest<ListingDetailResponse>(`/listings/show?id=${id}`);
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
