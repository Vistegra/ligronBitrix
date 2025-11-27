import { useEffect, useState } from "react";
import { useAuthStore } from "@/store/authStore";
import { authApi } from "@/api/authApi";
import { toast } from "sonner";
import { useQueryClient } from "@tanstack/react-query";
import { useAuthNavigation } from "./useAuthNavigation";

// Глобальная блокировка
let processingTokenGlobal: string | null = null;

export function useTokenAuth() {
  const queryClient = useQueryClient();

  const { navigateToApp, navigateToLogin, clearUrlParam, location } = useAuthNavigation();

  const searchParams = new URLSearchParams(location.search);
  const utToken = searchParams.get("ut");

  const [isProcessing, setIsProcessing] = useState(!!utToken);

  useEffect(() => {
    if (!utToken) {
      setIsProcessing(false);
      return;
    }

    if (processingTokenGlobal === utToken) {
      setIsProcessing(true);
      return;
    }

    const processLogin = async () => {
      try {
        processingTokenGlobal = utToken;
        setIsProcessing(true);

        const currentToken = useAuthStore.getState().token;
        if (currentToken) {
          useAuthStore.getState().logout();
          queryClient.clear();
        }

        const res = await authApi.loginByUt(utToken);

        if (!res.data?.user || !res.data?.token) {
          throw new Error("Пустой ответ от сервера");
        }

        useAuthStore.getState().login({
          user: res.data.user,
          token: res.data.token
        });

        toast.success("Вход по ссылке выполнен");

        if (location.pathname === "/login") {
          // PAGE.LOGIN нельзя использовать из-за  циклической ссылки
          navigateToApp();
        } else {
          clearUrlParam("ut");
        }

      } catch (err: any) {
        console.error("Token auth error", err);
        toast.error(err.message || "Ссылка недействительна");

        navigateToLogin();

      } finally {
        processingTokenGlobal = null;
        setIsProcessing(false);
      }
    };

    processLogin();

  }, [utToken, queryClient, navigateToApp, navigateToLogin, clearUrlParam, location.pathname]);

  return {
    isTokenProcessing: isProcessing || !!utToken
  };
}