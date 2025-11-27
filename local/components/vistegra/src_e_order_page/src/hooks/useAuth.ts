import { useEffect, useCallback, useRef } from "react";
import { useNavigate, useLocation, useSearchParams } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useAuthStore } from "@/store/authStore";

import { PAGE } from "@/api/constants";
import { authApi, type LoginCredentials } from "@/api/authApi";
import { toast } from "sonner";

export interface LoginResult {
  success: boolean;
  error?: string;
}

export function useAuth() {
  const navigate = useNavigate();
  const location = useLocation();
  const queryClient = useQueryClient();

  const {
    user,
    token,
    login: setAuth,
    updateUserDetailed,
    logout: storeLogout,
  } = useAuthStore();

  const [searchParams, setSearchParams] = useSearchParams();
  const utToken = searchParams.get("ut");

  const utProcessed = useRef(false);

  // хелперы
  const redirectToLogin = useCallback(() => {
    if (location.pathname === PAGE.LOGIN) return;
    const fullPath = location.pathname + location.search;
    navigate(PAGE.LOGIN, { replace: true, state: { from: fullPath } });
  }, [navigate, location.pathname, location.search]);

  const redirectAfterLogin = useCallback(() => {
    const from = location.state?.from;
    const redirectTo = from && from !== PAGE.LOGIN ? from : PAGE.ORDERS;
    navigate(redirectTo, { replace: true });
  }, [location.state?.from, navigate]);

  const removeUtTokenFromUrl = useCallback(() => {
    setSearchParams((prev) => {
      const newParams = new URLSearchParams(prev);
      newParams.delete("ut");
      return newParams;
    }, { replace: true });
  }, [setSearchParams]);


  // Мутация входа по ссылке
  const utLoginMutation = useMutation({
    mutationFn: async (ut: string) => {
      const res = await authApi.loginByUt(ut);
      if (!res.data?.user || !res.data?.token) throw new Error("Нет данных пользователя");
      return res;
    },
    onSuccess: (res) => {
      console.log({res})
      setAuth({ user: res.data.user, token: res.data.token });
      toast.success("Вход выполнен по ссылке");
      removeUtTokenFromUrl();
    },
    onError: (err) => {
      toast.error(err.message || "Ссылка для входа недействительна или устарела");
      removeUtTokenFromUrl();
      redirectToLogin();
    }
  });


  useEffect(() => {

    if (utToken && !utProcessed.current) {
      utProcessed.current = true;

      if (token) {
        storeLogout();
        queryClient.clear();
      }

      utLoginMutation.mutate(utToken);
    }
  }, [utToken, token, storeLogout, queryClient]);


  // Мутация обычного входа
  const loginMutation = useMutation({
    mutationFn: async (creds: LoginCredentials) => {
      const res = await authApi.login(creds);
      if (!res.data?.user || !res.data?.token) throw new Error("Нет данных пользователя");
      return res;
    },
    onSuccess: (res) => {
      setAuth({ user: res.data.user, token: res.data.token });
      toast.success("Вход выполнен");
    },
  });

  const login = async (credentials: LoginCredentials): Promise<LoginResult> => {
    try {
      await loginMutation.mutateAsync(credentials);
      return { success: true };
    } catch (err: any) {
      const msg = err.message || "Ошибка авторизации";
      toast.error(msg);
      return { success: false, error: msg };
    }
  };


  // Загрузка детальных данных пользователя
  const profileQuery = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: () => authApi.me(),
    // Включаем запрос только если есть токен И НЕ идет процесс входа по ссылке
    // (чтобы не пытаться грузить профиль старого юзера в момент переключения)
    enabled: !!token && !utLoginMutation.isPending,
    retry: false,
    staleTime: 1000 * 60 * 5,
  });


  // Глобальная синхронизация
  useEffect(() => {
    // 1. Если идет процесс входа по ссылке — ждем, ничего не делаем
    if (utLoginMutation.isPending) return;

    // 2. Если токена нет — редирект на логин
    if (!token) {
      if (utToken) return;

      if (location.pathname !== PAGE.LOGIN) redirectToLogin();
      return;
    }

    // 3. Если ошибка получения профиля
    if (profileQuery.isError) {
      console.warn("Ошибка сессии:", profileQuery.error);
      storeLogout();
      queryClient.clear();
      redirectToLogin();
      return;
    }

    // 4. Если пришли детальные данные — обновляем стор и редиректим
    if (profileQuery.data?.data?.detailed) {
      if (JSON.stringify(user?.detailed) !== JSON.stringify(profileQuery.data.data.detailed)) {
        updateUserDetailed(profileQuery.data.data.detailed);
      }

      if (location.pathname === PAGE.LOGIN) {
        redirectAfterLogin();
      }
    }

  }, [
    token,
    profileQuery.data,
    profileQuery.isError,
    location.pathname,
    user?.detailed,
    storeLogout,
    queryClient,
    updateUserDetailed,
    redirectToLogin,
    redirectAfterLogin,
    utLoginMutation.isPending,

  ]);

  const logout = useCallback(() => {
    storeLogout();
    queryClient.clear();
    redirectToLogin();
  }, [storeLogout, queryClient, redirectToLogin]);


  const isInitializing =
    (!!token && (profileQuery.isLoading || !user?.detailed)) ||
    utLoginMutation.isPending;

  return {
    isLoading: loginMutation.isPending || isInitializing,
    error: loginMutation.error ? (loginMutation.error as Error).message : null,
    login,
    logout,
    clearError: loginMutation.reset,
  };
}