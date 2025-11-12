// hooks/useOrders.ts
import { useState, useEffect, useCallback } from "react";
import { orderApi, type OrdersListResponse, type Order } from "@/api/orderApi";
import type { ApiResponse } from "@/api/client";

interface UseOrdersOptions {
  limit?: number;
  initialFilter?: { status?: string };
}

interface UseOrdersReturn {
  orders: Order[];
  loading: boolean;
  error: string | null;
  pagination: {
    limit: number;
    offset: number;
    total: number;
  };
  fetchOrders: (offset?: number, filter?: { status?: string }) => Promise<void>;
}

export function useOrders(options: UseOrdersOptions = {}): UseOrdersReturn {
  const { limit = 20, initialFilter = {} } = options;

  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState({
    limit,
    offset: 0,
    total: 0,
  });
  const [currentFilter, setCurrentFilter] = useState(initialFilter);

  const fetchOrders = useCallback(
    async (offset = 0, filter = currentFilter) => {
      setLoading(true);
      setError(null);

      try {
        const params: any = {
          limit: pagination.limit,
          offset,
        };

        if (filter.status) {
          params.filter = { status_id: filter.status };
        }

        const response: ApiResponse<OrdersListResponse> = await orderApi.getOrders(params);

        if (response.status !== "success") {
          throw new Error(response.message || "Ошибка загрузки заказов");
        }

        setOrders(response.data.order);
        setPagination((prev) => ({
          ...prev,
          offset,
          total: response.data.pagination.total,
        }));
        setCurrentFilter(filter);
      } catch (err: any) {
        const msg = err.response?.data?.message || err.message || "Неизвестная ошибка";
        setError(msg);
      } finally {
        setLoading(false);
      }
    },
    [pagination.limit]
  );


  useEffect(() => {
    fetchOrders(0, initialFilter);
  }, [fetchOrders]);

  return {
    orders,
    loading,
    error,
    pagination,
    fetchOrders,

  };
}