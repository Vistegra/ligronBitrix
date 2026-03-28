import {useMutation} from "@tanstack/react-query";
import {useAuthStore} from "@/store/authStore";
import {toast} from "sonner";
import {useAuthNavigation} from "./useAuthNavigation";
import {queries} from "@/lib/queryFactory";
import type {LoginCredentials} from "@/api/authApi.ts";

export function useAuthLogin() {
  const {login: setAuth} = useAuthStore();
  const {navigateToApp} = useAuthNavigation();

  const mutation = useMutation({
    ...queries.auth.login(),
    onSuccess: (data) => {
      // data (user и token)
      setAuth({user: data.user, token: data.token});
      toast.success("Вход выполнен");
      navigateToApp();
    },
  });

  // Обертка для обработки ошибок в UI
  const login = async (creds: LoginCredentials) => {
    try {
      await mutation.mutateAsync(creds);
      return {success: true};
    } catch (err: any) {
      toast.error(err.message || "Ошибка");
      return {success: false, error: err.message};
    }
  };

  return {
    // Методы
    login,
    resetError: mutation.reset,

    // Статусы
    isLoading: mutation.isPending,
    error: mutation.error ? (mutation.error as Error).message : null,
  };

}