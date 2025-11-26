import { useQuery } from "@tanstack/react-query";
import { orderApi } from "@/api/orderApi";

export function useOrder(id: number) {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['orders', 'detail', id],
    queryFn: () => orderApi.getOrder(id),
    enabled: !!id && id > 0, // Не делать запрос, если ID нет
    staleTime: 5 * 60 * 1000, // 5 минут кэша
    retry: 1,
  });

  return {
    order: data?.data?.order || null,
    files: data?.data?.files || [],
    loading: isLoading,
    error: isError ? (error as Error).message : null,
  };
}