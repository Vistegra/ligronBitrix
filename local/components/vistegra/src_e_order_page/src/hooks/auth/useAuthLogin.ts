import { useMutation } from "@tanstack/react-query";
import { useAuthStore } from "@/store/authStore";
import { authApi, type LoginCredentials } from "@/api/authApi";
import { toast } from "sonner";
import { useAuthNavigation } from "./useAuthNavigation";

export function useAuthLogin() {
  const { login: setAuth } = useAuthStore();

  const { navigateToApp } = useAuthNavigation();

  const mutation = useMutation({
    mutationFn: async (creds: LoginCredentials) => {
      const res = await authApi.login(creds);
      if (!res.data?.user) throw new Error("Нет данных");
      return res;
    },
    onSuccess: (res) => {
      setAuth({ user: res.data.user, token: res.data.token });
      toast.success("Вход выполнен");

      navigateToApp();
    },
  });

  const login = async (creds: LoginCredentials) => {
    try {
      await mutation.mutateAsync(creds);
      return { success: true };
    } catch (err: any) {
      toast.error(err.message || "Ошибка");
      return { success: false, error: err.message };
    }
  };

  return {
    login,
    isLoginLoading: mutation.isPending,
    loginError: mutation.error ? (mutation.error as Error).message : null,
    resetError: mutation.reset,
  };
}