import axios from "axios";
import { API_BASE } from "./constants";
import {useAuthStore} from "@/store/authStore.ts";

const api = axios.create({
  baseURL: API_BASE,
  headers: { "Content-Type": "application/json" },
});

api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;

  if (token) {
    config.headers['X-Auth-Token'] = token;
  }
  return config;
});
/*api.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.clear();
      window.location.href = "/login";
    }
    return Promise.reject(err);
  }
);*/

export default api;