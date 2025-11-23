import {useEffect, useState, useCallback} from "react";
import {useNavigate, useLocation} from "react-router-dom";
import {useAuthStore} from "@/store/authStore";

import {PAGE} from "@/api/constants";
import {authApi, type LoginCredentials} from "@/api/authApi.ts";

export interface LoginResult {
  success: boolean;
  error?: string;
}

export function useAuth() {
  const [isLoading, setIsLoading] = useState(false);
  const [isInitializing, setIsInitializing] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const navigate = useNavigate();
  const location = useLocation();

  const {
    user,
    token,
    login: setAuth,
    updateUserDetailed,
    logout,
  } = useAuthStore();

  /**
   * функция редиректа на login
   */
  const redirectToLogin = useCallback(
    (fromPath?: string) => {
      const fullPath = fromPath ?? (location.pathname + location.search);

      navigate(PAGE.LOGIN, {
        replace: true,
        state: {from: fullPath},
      });
    },
    [navigate, location.pathname, location.search]
  );

  /**
   * функция редиректа после логина
   */
  const redirectAfterLogin = useCallback(() => {
    const from = location.state?.from;
    const redirectTo = from && from !== PAGE.LOGIN ? from : PAGE.ORDERS;

    navigate(redirectTo, {replace: true});
  }, [location.state?.from, navigate]);

  /**
   * Инициализация: проверка токена, пользователя, загрузка detailed → редирект
   */
  useEffect(() => {
    const init = async () => {

      if (!token || !user) {
        setIsInitializing(false);

        if (location.pathname !== PAGE.LOGIN) {
          redirectToLogin();
        }

        return;
      }

      // Если подробные данные уже есть
      if (user?.detailed) {
        setIsInitializing(false);

        if (location.pathname === PAGE.LOGIN) {
          redirectAfterLogin();
        }
        return;
      }

      // Иначе загружаем детальные данные пользователя
      try {
        const res = await authApi.me();

        if (res.status !== "success") {
          throw new Error(res.message || "Ошибка получения данных пользователя");
        }

        updateUserDetailed(res.data.detailed);

        if (location.pathname === PAGE.LOGIN) {
          redirectAfterLogin();
        }
      } catch (err) {
        console.warn("Токен недействителен", err);
        logout();
        redirectToLogin();
      } finally {
        setIsInitializing(false);
      }

    };

    init();
  }, [
    token,
    user,
    location.pathname,
    navigate,
    logout,
    updateUserDetailed,
    redirectToLogin,
    redirectAfterLogin,
  ]);

  /**
   * Авторизация
   */
  const login = async (credentials: LoginCredentials): Promise<LoginResult> => {
    setIsLoading(true);
    setError(null);

    try {
      const res = await authApi.login(credentials);

      if (res.status !== "success") {
        throw new Error(res.message || "Ошибка авторизации");
      }

      const {user: baseUser, token} = res.data;
      setAuth({user: baseUser, token});

      redirectAfterLogin();

      return {success: true};
    } catch (err: any) {
      const message = err.response?.data?.message || "Ошибка авторизации";
      setError(message);

      return {success: false, error: message};
    } finally {
      setIsLoading(false);
    }
  };

  const clearError = useCallback(() => setError(null), []);

  return {
    isLoading: isLoading || isInitializing,
    error,

    login,
    logout,
    clearError,
  };

}
