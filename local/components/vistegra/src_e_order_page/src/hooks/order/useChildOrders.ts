import { useQuery } from "@tanstack/react-query";
import { orderApi } from "@/api/orderApi.ts";

export function useChildOrders(parentId: number) {
  const { data, isLoading } = useQuery({
    queryKey: ['orders', 'children', parentId],
    queryFn: () => orderApi.getOrders({ filter: `parent_id=${parentId}`, is_draft: 0 }),
    enabled: !!parentId
  });

  return {
    children: data?.data?.orders || [],
    loading: isLoading
  };
}