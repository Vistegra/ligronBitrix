import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { useOrderUrlState } from "./useOrderUrlState";
import { useContextSync } from "@/hooks/order/useContextSync.ts";
import { queries } from "@/lib/queryFactory";

export function useOrders(defaultLimit = 20, isDraft: boolean) {
  // Состояние из URL
  const {
    limit,
    offset,
    filterString,
    searchString,
    activeFilters,
    updateFilters,
    sortParam,
    ...urlStateMethods
  } = useOrderUrlState(defaultLimit);

  // Синхронизация контекста с фильтрами URL
  useContextSync(activeFilters, updateFilters);

  // Объект параметров для запроса
  const requestParams = {
    limit,
    offset,
    is_draft: Number(isDraft),
    filter: filterString,
    search: searchString,
    sort: sortParam,
  };


  const { data, isLoading, isError, error, isFetching } = useQuery({
    ...queries.orders.list(requestParams),
    placeholderData: keepPreviousData,
  });

  return {
    // Данные
    orders: data?.data?.orders || [],
    pagination: data?.data?.pagination || { limit, offset, total: 0 },

    // Статусы загрузки
    loading: isLoading,
    isFetching,
    error: isError ? (error as Error).message : null,

    // Методы управления (фильтры, пагинация, сортировка)
    activeFilters,
    updateFilters,
    ...urlStateMethods,
  };
}