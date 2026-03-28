import { useQuery } from "@tanstack/react-query";
import { queries } from "@/lib/queryFactory";

export function useOrder(id: number) {

  const query = useQuery(queries.orders.detail(id));

  return {
    order: query.data?.data?.order || null,
    files: query.data?.data?.files || [],
    isLoading: query.isLoading,
    error: query.error?.message,
    refetch: query.refetch,
  };
}