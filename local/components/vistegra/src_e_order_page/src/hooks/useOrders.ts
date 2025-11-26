import {useState, useCallback, useEffect, useMemo} from "react";
import {useSearchParams} from "react-router-dom";
import {orderApi, type Order} from "@/api/orderApi";

// Доступные фильтры
interface FilterParams {
  status_id?: number[];
  dealer_prefix?: string | null;
  dealer_user_id?: number | null;
}

interface Pagination {
  limit: number;
  offset: number;
  total: number
}

// Если функция возвращает null/пустую строку, параметр будет удален из URL.
const FILTER_HANDLERS: Record<keyof FilterParams, (value: any) => string | null> = {
  status_id: (value: number[]) => (value?.length ? value.join(",") : null),
  dealer_prefix: (value: string) => value || null,
  dealer_user_id: (value: number) => (value ? String(value) : null),
};

interface UseOrdersReturn {
  orders: Order[];
  loading: boolean;
  error: string | null;
  pagination: Pagination;
  activeFilters: Required<FilterParams>;
  updateFilters: (newFilters: Partial<FilterParams>) => void;
  setPage: (offset: number) => void;
  setLimit: (limit: number) => void;
  refresh: () => void;
}

export function useOrders(defaultLimit: number = 20, isDraft: boolean): UseOrdersReturn {
  const [searchParams, setSearchParams] = useSearchParams();

  // Состояние данных
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [total, setTotal] = useState(0);

  // Читаем пагинацию
  const limit = Number(searchParams.get("limit")) || defaultLimit;
  const offset = Number(searchParams.get("offset")) || 0;

  // Парсим фильтры из URL
  const activeFilters = useMemo(() => ({
    status_id: searchParams.get("status_id")?.split(",").map(Number) || [],
    dealer_prefix: searchParams.get("dealer_prefix") || null,
    dealer_user_id: searchParams.get("dealer_user_id") ? Number(searchParams.get("dealer_user_id")) : null,
  }), [searchParams]);

  // Загрузка данных
  const fetchOrders = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      // Собираем строку фильтра для API
      const filterParts: string[] = [];
      searchParams.forEach((value, key) => {
        if (key !== "limit" && key !== "offset" && value) {
          filterParts.push(`${key}=${value}`);
        }
      });

      const filterString = filterParts.join(";");

      const response = await orderApi.getOrders({limit, offset, filter: filterString, is_draft: Number(isDraft)});

      if (response.status !== "success") throw new Error(response.message || "Ошибка");

      setOrders(response.data.orders);
      setTotal(response.data.pagination.total);

    } catch (err: any) {
      setError(err.response?.data?.message || err.message || "Ошибка загрузки заказов");

    } finally {
      setLoading(false);
    }
  }, [searchParams, limit, offset]);

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);

  // Функция обновления параметров фильтров
  const updateFilters = useCallback((newFilters: Partial<FilterParams>) => {
    setSearchParams((prev) => {
      const newParams = new URLSearchParams(prev);

      // Проходимся по ключам пришедшего объекта (например, только { dealer_prefix: 'abc' })
      (Object.keys(newFilters) as Array<keyof FilterParams>).forEach((key) => {
        const rawValue = newFilters[key];
        const handler = FILTER_HANDLERS[key];

        // Если для ключа есть обработчик в конфиге
        if (handler) {
          const stringValue = handler(rawValue);

          if (stringValue) {
            newParams.set(key, stringValue);
          } else {
            newParams.delete(key);
          }
        }
      });

      // При изменении фильтров сбрасываем пагинацию
      newParams.set("offset", "0");
      return newParams;
    });
  }, [setSearchParams]);

  const setPage = useCallback((newOffset: number) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      p.set("offset", String(newOffset));
      return p;
    });
  }, [setSearchParams]);

  const setLimit = useCallback((newLimit: number) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      p.set("limit", String(newLimit));
      p.set("offset", "0");
      return p;
    });
  }, [setSearchParams]);

  return {
    orders,
    loading,
    error,
    pagination: {limit, offset, total},
    activeFilters,
    updateFilters,
    setPage,
    setLimit,
    refresh: fetchOrders,
  };
}