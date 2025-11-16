import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import api from "@/api/client";
import { ENDPOINT, PAGE } from "@/api/constants";

export function useCheckAuth() {
  const navigate = useNavigate();
  const { token, logout } = useAuthStore();

  useEffect(() => {
    const checkToken = async () => {

      if (!token) {
        navigate(PAGE.LOGIN, { replace: true });
        return;
      }

      try {
        // Отправляем запрос с токеном (он уже в заголовке через интерцептор)
        await api.get(ENDPOINT.AUTH_CHECK );

      } catch (err: any) {
        // 401 → разлогинить
        if (err.response?.status === 401) {
          logout();
        }
        // Любая ошибка → на логин
        navigate(PAGE.LOGIN, { replace: true });
      }
    };

    checkToken();
  }, [logout, navigate, token]);
}