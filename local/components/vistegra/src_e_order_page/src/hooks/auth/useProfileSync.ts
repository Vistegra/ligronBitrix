import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useAuthStore } from "@/store/authStore";
import { authApi } from "@/api/authApi";
import { useEffect } from "react";

interface UseProfileSyncProps {
  isTokenProcessing: boolean;
}

export function useProfileSync({ isTokenProcessing }: UseProfileSyncProps) {
  const { token, user, updateUserDetailed, logout } = useAuthStore();
  const queryClient = useQueryClient();

  const query = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: () => authApi.me(),
    // Не грузим, пока обрабатывается токен из URL
    enabled: !!token && !isTokenProcessing,
    retry: false,
   
  });

  useEffect(() => {
    if (isTokenProcessing) return;

    if (query.isError) {
      logout();
      queryClient.clear();
      return;
    }

    if (query.data?.data?.detailed) {
      updateUserDetailed(query.data.data.detailed);
    }
  }, [
    query.data,
    query.isError,
    user?.detailed,
    isTokenProcessing,
    logout,
    queryClient,
    updateUserDetailed
  ]);

  const isProfileLoading = !!token && (query.isLoading || !user?.detailed);

  return { isProfileLoading };
}