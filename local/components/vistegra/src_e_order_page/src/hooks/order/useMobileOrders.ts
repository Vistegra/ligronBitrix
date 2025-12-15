import { useInfiniteQuery } from "@tanstack/react-query";
import { orderApi } from "@/api/orderApi";
import { useOrderUrlState } from "./useOrderUrlState";

const PAGE_SIZE = 10;

export function useMobileOrders(isDraft: boolean) {
  const { filterString, searchString, activeFilters } = useOrderUrlState(PAGE_SIZE);

  const query = useInfiniteQuery({
    queryKey: ["orders", "mobile", { isDraft: Number(isDraft), filter: filterString, search: searchString }],

    queryFn: async ({ pageParam = 0 }) => {
      const res = await orderApi.getOrders({
        limit: PAGE_SIZE,
        offset: pageParam,
        is_draft: Number(isDraft),
        filter: filterString,
        search: searchString,
      });
      return res.data;
    },

    initialPageParam: 0,
    getNextPageParam: (lastPage, allPages) => {
      const currentTotal = allPages.reduce((acc, page) => acc + (page?.orders?.length || 0), 0);

      if (lastPage && currentTotal < lastPage.pagination.total) {
        return currentTotal;
      }
      return undefined;
    },
    staleTime: 1000 * 60,
  });

  const orders = query.data?.pages.flatMap((page) => page?.orders) || [];
  const total = query.data?.pages[0]?.pagination.total || 0;

  return {
    orders,
    total,
    isLoading: query.isLoading,
    isError: query.isError,
    error: query.error,
    fetchNextPage: query.fetchNextPage,
    hasNextPage: query.hasNextPage,
    isFetchingNextPage: query.isFetchingNextPage,
    refetch: query.refetch,

    activeFilters
  };
}