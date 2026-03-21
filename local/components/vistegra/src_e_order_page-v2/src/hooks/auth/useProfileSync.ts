import { useQuery } from "@tanstack/react-query";
import { useAuthStore } from "@/store/authStore";
import { authApi } from "@/api/authApi";
import { useEffect } from "react";
import {queries} from "@/lib/queryFactory.ts";

interface UseProfileSyncProps {
  isTokenProcessing: boolean;
}

export function useProfileSync({ isTokenProcessing }: UseProfileSyncProps) {
  const { token, user, updateUserDetailed, logout } = useAuthStore();

  const query = useQuery({
    ...queries.auth.me(),

    queryFn: () => authApi.me(),
    enabled: !!token && !isTokenProcessing,
    initialData: user?.detailed
      ? {
        status: 'success' as const,
        message: 'from cache',
        data: { detailed: user.detailed }
      }
      : undefined,
  });


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
  },[
    query.data,
    query.isError,
    user?.detailed,
    isTokenProcessing,
    logout,
    updateUserDetailed
  ]);

  const isProfileLoading = !!token && (query.isLoading || !user?.detailed);

  return { isProfileLoading };
}