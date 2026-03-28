import {useInfiniteQuery} from "@tanstack/react-query";
import {useOrderUrlState} from "./useOrderUrlState";
import {useContextSync} from "@/hooks/order/useContextSync.ts";
import {queries} from "@/lib/queryFactory";

const PAGE_SIZE = 10;

export function useMobileOrders(isDraft: boolean) {
  // Состояние из URL
  const {filterString, searchString, activeFilters, updateFilters} = useOrderUrlState(PAGE_SIZE);

  // Синхронизация контекста
  useContextSync(activeFilters, updateFilters);

  // Параметры без offset
  const requestParams = {
    limit: PAGE_SIZE,
    is_draft: Number(isDraft),
    filter: filterString,
    search: searchString,
  };

  const query = useInfiniteQuery(queries.orders.infiniteList(requestParams));

  const orders = query.data?.pages.flatMap((page) => page?.orders) || [];
  const total = query.data?.pages[0]?.pagination.total || 0;

  return {
    // Данные
    orders,
    total,

    // Статусы
    isLoading: query.isLoading,
    isError: query.isError,
    error: query.error,
    fetchNextPage: query.fetchNextPage,
    hasNextPage: query.hasNextPage,
    isFetchingNextPage: query.isFetchingNextPage,
    refetch: query.refetch,

    // Фильтры
    activeFilters
  };
}