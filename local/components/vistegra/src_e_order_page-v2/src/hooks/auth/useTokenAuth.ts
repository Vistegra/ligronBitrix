import {useEffect} from "react";
import {useMutation} from "@tanstack/react-query";
import {useAuthStore} from "@/store/authStore";
import {toast} from "sonner";
import {useAuthNavigation} from "./useAuthNavigation";
import {queries} from "@/lib/queryFactory";

// Глобальная блокировка повторных вызовов
let processingTokenGlobal: string | null = null;

export function useTokenAuth() {
  const {login: setAuth, logout: storeLogout} = useAuthStore();
  const {navigateToApp, navigateToLogin, clearUrlParam, location} = useAuthNavigation();

  const searchParams = new URLSearchParams(location.search);
  const utToken = searchParams.get("ut");

  const mutation = useMutation({
    ...queries.auth.loginByUt(),
    onSuccess: (data) => {
      setAuth({user: data.user, token: data.token});
      toast.success("Вход по ссылке выполнен");

      // Навигация или очистка URL
      if (location.pathname === "/login") {
        navigateToApp();
      } else {
        clearUrlParam("ut");
      }
    },
    onError: (err) => {
      toast.error(err.message);
      navigateToLogin();
    },
    onSettled: () => {
      processingTokenGlobal = null;
    }
  });

  useEffect(() => {
    if (!utToken || processingTokenGlobal === utToken) return;

    processingTokenGlobal = utToken;

    // Сброс текущей сессии перед входом по новой ссылке
    if (useAuthStore.getState().token) {
      storeLogout();
    }

    mutation.mutate(utToken);
  }, [utToken, storeLogout]);

  return {
    // Статусы
    isTokenProcessing: mutation.isPending || !!utToken,
  };
}