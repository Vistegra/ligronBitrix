import {useQuery, keepPreviousData} from "@tanstack/react-query";
import {orderApi} from "@/api/orderApi.ts";
import {useOrderUrlState} from "./useOrderUrlState";

export function useOrders(defaultLimit = 20, isDraft: boolean) {

  const {
    limit,
    offset,
    filterString,
    searchString,
    activeFilters,
    setPage,
    setLimit,
    updateFilters,

    sortParam,
    sortConfig,
    toggleSort,
  } = useOrderUrlState(defaultLimit);

  const {data, isLoading, isError, error, isFetching} = useQuery({
    queryKey: ['orders', 'list',
      {
        isDraft: Number(isDraft),
        limit,
        offset,
        filter: filterString,
        search: searchString,
        sort: sortParam
      }
    ],

    queryFn: () => orderApi.getOrders({
      limit,
      offset,
      is_draft: Number(isDraft),
      filter: filterString,
      search: searchString,
      sort: sortParam
    }),

    placeholderData: keepPreviousData,
    staleTime: 300,
  });

  return {
    orders: data?.data?.orders || [],
    pagination: data?.data?.pagination || {limit, offset, total: 0},
    loading: isLoading,
    isFetching,
    error: isError ? (error as Error).message : null,

    // методы и данные из useOrderUrlState
    activeFilters,
    setPage,
    setLimit,
    updateFilters,
    sortConfig,
    toggleSort,
  };
}