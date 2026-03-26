import {useQuery} from "@tanstack/react-query";
import {useAuthStore} from "@/store/authStore";
import {useContextStore} from "@/store/contextStore";
import {authApi} from "@/api/authApi";
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
    queryFn: () => authApi.me(),
    enabled: !!token && !isTokenProcessing,
    initialData: user?.detailed
      ? ({
        status: 'success',
        message: 'from cache',
        data: {detailed: user.detailed}
      } as ApiResponse<DetailedResponse>)
      : undefined,
  });

  // Инициализация дефолтного контекста для Дилера
  useEffect(() => {
    // Если зашел Дилер и в сторе пусто
    if (user?.provider === 'dealer' && !inn) {
      if (user.inn_dealer && user.salon_code) {
        _set(user.inn_dealer, user.salon_code);
      }
    }
  }, [user, inn, _set]);

  // Синхронизация данных профиля
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
  }, [
    query.data,
    query.isError,
    user?.detailed,
    isTokenProcessing,
    logout,
    updateUserDetailed
  ]);

  const isProfileLoading = !!token && (query.isLoading || !user?.detailed);

  return {isProfileLoading};
}