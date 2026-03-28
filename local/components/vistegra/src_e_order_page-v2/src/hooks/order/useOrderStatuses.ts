import {useQuery} from "@tanstack/react-query";
import {queries} from "@/lib/queryFactory";

export function useOrderStatuses() {
  const {data, isLoading, isError} = useQuery(queries.statuses.all());

  const statuses = data?.data || [];
  const getStatusById = (id: number) => statuses.find(s => s.id === id);
  const getStatusByCode = (code: string) => statuses.find(s => s.code === code);

  return {
    // Данные
    statuses,

    // Статусы
    loading: isLoading,
    isError,

    // Хелперы
    getStatusById,
    getStatusByCode
  };
}