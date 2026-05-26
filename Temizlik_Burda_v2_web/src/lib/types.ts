export type UserRole = "homeowner" | "worker" | "admin";

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  city?: string | null;
  phone?: string | null;
  bio?: string | null;
  avatar?: string | null;
  avatar_url?: string | null;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  icon?: string | null;
}

export interface Home {
  id: number;
  user_id: number;
  title: string;
  address: string;
  district: string;
  city: string;
  room_config: string;
  floor: number | null;
  has_elevator: number;
  bathroom_count: number;
  sqm: number | null;
  notes?: string | null;
  photo?: string | null;
  photo_url?: string | null;
  created_at: string;
}

export interface Listing {
  id: number;
  user_id?: number;
  owner_id?: number;
  home_id?: number;
  category_id?: number;
  title: string;
  description: string;
  preferred_date?: string | null;
  preferred_time?: string | null;
  budget?: number | null;
  status?: string;
  view_count?: number;
  created_at: string;
  cat_name?: string;
  cat_icon?: string | null;
  cat_slug?: string;
  owner_name?: string;
  owner_rating?: number | null;
  owner_reviews?: number | null;
  city?: string;
  district?: string;
  room_config?: string;
  sqm?: number | null;
  bathroom_count?: number | null;
  has_elevator?: number;
  floor?: number | null;
  home_notes?: string | null;
  home_photo?: string | null;
  home_photo_url?: string | null;
  offer_count?: number;
  offers_scope?: "all" | "mine" | "none";
  viewer_has_offer?: boolean;
  viewer_is_owner?: boolean;
  viewer_can_message_owner?: boolean;
}

export interface Offer {
  id: number;
  listing_id?: number;
  user_id?: number;
  homeowner_id?: number;
  message_contact_id?: number | null;
  can_message?: boolean;
  price: number;
  message?: string | null;
  status?: string;
  created_at: string;
  worker_name?: string;
  worker_rating?: number | null;
  worker_avatar?: string | null;
  worker_avatar_url?: string | null;
  listing_title?: string;
  listing_status?: string;
  homeowner_name?: string;
  city?: string;
  district?: string;
}

export interface MessageThread {
  thread_key: string;
  listing_id: number;
  listing_title: string;
  listing_status?: string;
  contact_id: number;
  contact_name: string;
  contact_avatar_url?: string | null;
  last_message: string;
  last_message_at: string;
  last_message_from_me: boolean;
  unread_count: number;
}

export interface ChatMessage {
  id: number;
  sender_id: number;
  receiver_id: number;
  listing_id: number;
  message: string;
  is_read: number;
  created_at: string;
  is_mine: boolean;
}

export interface ConversationMeta {
  listing_id: number;
  listing_title: string;
  contact_id: number;
  contact_name: string;
  contact_role?: string;
  contact_avatar_url?: string | null;
}

export interface Pagination {
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

export interface Session {
  token: string;
  user: User;
}
