import axios from "axios";
import { API_BASE } from "./constants";
import {useAuthStore} from "@/store/authStore.ts";

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
}

export interface PartialSuccessApiResponse<T = any> extends BaseApiResponse {
  status: 'partial';
  data: T;
}

export type ApiResponse<T = any> =
  | SuccessApiResponse<T>
  | ErrorApiResponse
  | PartialSuccessApiResponse<T>;


const api = axios.create({
  baseURL: API_BASE/*,
  headers: { "Content-Type": "application/json" },*/
});

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;

  if (token) {
    config.headers['X-Auth-Token'] = token;
  }

  return config;
});

api.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401) {
      useAuthStore.getState().logout();
    }
    return Promise.reject(err);
  }
);

export default api;