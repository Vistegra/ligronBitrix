// src/hooks/useOrders.ts
import { useState, useEffect, useCallback } from "react";
import { orderApi, type OrdersListResponse, type Order } from "@/api/orderApi";

interface UseOrdersOptions {
  limit?: number;
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
  filter: string;
  fetchOrders: (offset?: number, filter?: string) => Promise<void>;
  setLimit: (limit: number) => void;
  setFilter: (filter: string) => void;
}

export function useOrders(options: UseOrdersOptions = {}): UseOrdersReturn {
  const initialLimit = options.limit || 20;

  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [limit, setLimit] = useState(initialLimit);
  const [offset, setOffset] = useState(0);
  const [total, setTotal] = useState(0);
  const [filter, setFilter] = useState(""); // Исправлено: начальное значение ""

  const fetchOrders = useCallback(
      async (newOffset = 0, newFilter = filter) => {
        setLoading(true);
        setError(null);

        try {
          const params: any = {
            limit,
            offset: newOffset,
          };

          if (newFilter) {
            params.filter = newFilter;
          }

          const response = await orderApi.getOrders(params);

          if (response.status !== "success") {
            throw new Error(response.message || "Ошибка загрузки заказов");
          }

          setOrders(response.data.order);
          setTotal(response.data.pagination.total);
          setOffset(newOffset);
          setFilter(newFilter);
        } catch (err: any) {
          const msg = err.response?.data?.message || err.message || "Неизвестная ошибка";
          setError(msg);
        } finally {
          setLoading(false);
        }
      },
      [limit, filter]
  );

  useEffect(() => {
    fetchOrders(0, filter);
  }, [limit]);

  const setLimitAndReset = (newLimit: number) => {
    setLimit(newLimit);
  };

  const setFilterAndReset = (newFilter: string) => {
    setFilter(newFilter);
    fetchOrders(0, newFilter);
  };

  return {
    orders,
    loading,
    error,
    pagination: { limit, offset, total },
    filter,
    fetchOrders,
    setLimit: setLimitAndReset,
    setFilter: setFilterAndReset,
  };
}