import { useQuery } from "@tanstack/react-query";
import { orderApi } from "@/api/orderApi";

export function useOrderStatuses() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['statuses'],
    queryFn: () => orderApi.getStatuses(),
    staleTime: 1000 * 60 * 10, // 10 минут (справочник редко меняется)
    gcTime: 1000 * 60 * 60, // Хранить в памяти 1 час
  });

  const statuses = data?.data || [];

  // Хелперы для поиска статусов
  const getStatusById = (id: number) => statuses.find(s => s.id === id);
  const getStatusByCode = (code: string) => statuses.find(s => s.code === code);

  return {
    statuses,
    loading: isLoading,
    isError,
    getStatusById,
    getStatusByCode
  };
}