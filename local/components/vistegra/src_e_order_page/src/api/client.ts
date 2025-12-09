import axios, {type AxiosResponse} from "axios";
import {API_BASE} from "./constants";
import {useAuthStore} from "@/store/authStore";

export type ApiResponseStatus = 'success' | 'error' | 'partial';

export interface BaseApiResponse {
  status: ApiResponseStatus;
  message: string;
}

export interface SuccessApiResponse<T = any> extends BaseApiResponse {
  status: 'success';
  data: T;
}

export interface ErrorApiResponse extends BaseApiResponse {
  status: 'error';
  type?: string;
  data?: null;
}

export interface PartialApiResponse<T = any> extends BaseApiResponse {
  status: 'partial';
  data: T;
}

export type ApiResponse<T = any> =
  | SuccessApiResponse<T>
  | ErrorApiResponse
  | PartialApiResponse<T>;

const api = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    "Accept": "application/json",
  },
});

// Добавляем токен
api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) {
    config.headers['X-Auth-Token'] = token;
  }
  return config;
});

// Обработка 401 (Logout)
api.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401) {
      useAuthStore.getState().logout();
    }
    return Promise.reject(err);
  }
);

/**
 * Универсальный обработчик
 *
 * Обертка над запросами.
 * Если status === 'error' -> кидает исключение (чтобы TanStack Query понял, что это ошибка).
 * Если status === 'success' | 'partial' -> возвращает ответ целиком.
 */
export async function makeRequest<T>(
  request: () => Promise<AxiosResponse<ApiResponse<T>>>
): Promise<ApiResponse<T>> {
  try {
    const response = await request();
    const payload = response.data;

    if (payload.status === "error") {
      throw new Error(payload.message || "Ошибка выполнения операции");
    }

    // чтобы в компоненте можно было обработать 'partial' (warning).
    return payload;
  } catch (error: any) {
    // Нормализация ошибки для UI
    const message =
      error.response?.data?.message || // Ошибка от сервера (4xx, 5xx)
      error.message ||                 // Ошибка в самом приложении
      "Неизвестная ошибка";

    throw new Error(message);
  }
}

export default api;