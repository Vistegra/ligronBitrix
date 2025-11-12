// hooks/useAuth.ts
import {useEffect, useState} from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import api from "@/api/client.ts";
import {ENDPOINT, PAGE} from "@/api/constants.ts";
import type {ProviderType} from "@/types/user";


interface LoginCredentials {
  login: string;
  password: string;
  providerType: ProviderType;
}

export interface LoginResult {
  success: boolean;
  error?: string;
}

export function useAuth() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();
  const { user, token, login: setAuth } = useAuthStore();

  // Проверяем авторизацию при монтировании хука
  useEffect(() => {
    if (user && token) {
      navigate(PAGE.ORDERS);
    }
  }, [user, token, navigate]);

  const login = async (credentials: LoginCredentials): Promise<LoginResult> => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await api.post(ENDPOINT.AUTH_LOGIN, credentials);
      const responseData = response.data?.data;

      if (!responseData?.user || !responseData?.token) {
        const errorMessage = "Неверный формат ответа от сервера";
        setError(errorMessage);
        return {
          success: false,
          error: errorMessage
        };
      }

      // Устанавливаем авторизацию в store
      setAuth({
        user: responseData.user,
        token: responseData.token
      });

      // Перенаправление после успешного входа
      navigate(PAGE.ORDERS);

      return { success: true };
    } catch (err: any) {
      const errorMessage = err.response?.data?.data?.message ||
        err.response?.data?.message ||
        "Ошибка входа";

      setError(errorMessage);
      return {
        success: false,
        error: errorMessage
      };
    } finally {
      setIsLoading(false);
    }
  };

  const clearError = () => setError(null);

  return {
    // State
    isLoading,
    error,

    // Actions
    login,
    clearError,
  };
}