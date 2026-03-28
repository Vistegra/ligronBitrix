import {useQuery} from "@tanstack/react-query";
import {queries} from "@/lib/queryFactory";

export function useChildOrders(parentId: number) {
  // Конфигурация из фабрики
  const {data, isLoading} = useQuery(queries.orders.children(parentId));

  return {
    // Вложенные заказы
    children: data?.data?.orders || [],
    loading: isLoading
  };

}