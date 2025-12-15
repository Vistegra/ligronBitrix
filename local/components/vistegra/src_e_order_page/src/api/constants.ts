export const API_BASE = "https://ligron.ru/local/api-e-order";
export const URL_BASE = "https://ligron.ru";
export const PAGE = {
  LOGIN: '/login',
  ORDERS: '/orders',
  ORDERS_ALL: '/orders/all',
  ORDERS_CANCELED: '/orders/canceled',
  DRAFTS: '/drafts',
  draftDetail: (id: number | string) => `/drafts/${id}`,  // Для навигации
  PROFILE: '/profile',
  ORDER_DETAIL: '/orders/:id',
  DRAFT_DETAIL: '/drafts/:id',
  orderDetail: (id: number | string) => `/orders/${id}`,  // Для навигации
  REQUESTS: "/requests"
} as const;

export const ENDPOINT = {
  AUTH_LOGIN: '/auth/login',
  AUTH_DETAILED: '/auth/me',
  AUTH_SSO: '/auth/sso',
  ORDERS: '/orders',
  STATUSES: '/statuses',
}

