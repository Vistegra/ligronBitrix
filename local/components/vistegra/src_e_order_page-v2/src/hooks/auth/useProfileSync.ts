import {useQuery} from "@tanstack/react-query";
import {useAuthStore} from "@/store/authStore";
import {useContextStore} from "@/store/contextStore";
import {useEffect, useRef} from "react";
import {queries} from "@/lib/queryFactory.ts";
import type {ApiResponse} from "@/api/client";
import type {DetailedResponse} from "@/api/authApi";

interface UseProfileSyncProps {
  isTokenProcessing: boolean;
}

export function useProfileSync({isTokenProcessing}: UseProfileSyncProps) {
  const {token, user, updateUserDetailed, logout} = useAuthStore();
  const {_set} = useContextStore();

  const hasInitializedRef = useRef(false);

  const query = useQuery({
    ...queries.auth.me(),
    initialData: user?.detailed
      ? ({
        status: "success",
        message: "from cache",
        data: {detailed: user.detailed},
      } as ApiResponse<DetailedResponse>)
      : undefined,
    enabled: !!token && !isTokenProcessing,
  });

  // Авто-установка контекста для Дилера при входе
  useEffect(() => {
    // Если юзера нет (вышли из аккаунта), сбрасываем флаг
    if (!user) {
      hasInitializedRef.current = false;
      return;
    }

    // Если это дилер и мы еще не ставили ему дефолт в этой сессии
    if (user.provider === "dealer" && !hasInitializedRef.current) {
      hasInitializedRef.current = true; // Отмечаем, что инициализация прошла

      // Берем текущий ИНН напрямую из стора, чтобы не добавлять его в зависимости useEffect
      const currentInn = useContextStore.getState().inn;

      if (!currentInn && user.inn_dealer && user.salon_code) {
        _set(user.inn_dealer, user.salon_code);
      }
    }
  }, [user, _set]);

  // Синхронизация данных профиля с состоянием
  useEffect(() => {
    if (isTokenProcessing) return;

    if (query.isError) {
      logout();
      return;
    }

    const incomingDetailed = query.data?.data?.detailed;

    if (incomingDetailed) {
      if (JSON.stringify(user?.detailed) !== JSON.stringify(incomingDetailed)) {
        updateUserDetailed(incomingDetailed);
      }
    }
  }, [query.data, query.isError, user?.detailed, isTokenProcessing, logout, updateUserDetailed]);

  return {
    isProfileLoading: !!token && (query.isLoading || !user?.detailed),
  };
}