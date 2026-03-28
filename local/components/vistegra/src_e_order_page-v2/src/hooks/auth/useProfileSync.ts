import {useQuery} from "@tanstack/react-query";
import {useAuthStore} from "@/store/authStore";
import {useContextStore} from "@/store/contextStore";
import {useEffect} from "react";
import {queries} from "@/lib/queryFactory.ts";
import type {ApiResponse} from "@/api/client";
import type {DetailedResponse} from "@/api/authApi";

interface UseProfileSyncProps {
  isTokenProcessing: boolean;
}

export function useProfileSync({isTokenProcessing}: UseProfileSyncProps) {
  const {token, user, updateUserDetailed, logout} = useAuthStore();
  const {inn, _set} = useContextStore();

  const query = useQuery({
    ...queries.auth.me(),
    // Данные из кэша для мгновенного отображения
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
    if (user?.provider === "dealer" && !inn) {
      if (user.inn_dealer && user.salon_code) {
        _set(user.inn_dealer, user.salon_code);
      }
    }
  }, [user, inn, _set]);

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
    // Статусы
    isProfileLoading: !!token && (query.isLoading || !user?.detailed),
  };

}