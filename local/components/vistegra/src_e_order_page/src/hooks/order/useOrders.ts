import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { useSearchParams } from "react-router-dom";
import { orderApi } from "@/api/orderApi.ts";


const ALLOWED_FILTERS = [
  "status_id",
  "dealer_prefix",
  "dealer_user_id",
  "parent_id",
  // "date_from", // добавить в будущем
  // "date_to",
];


export function useOrders(defaultLimit = 20, isDraft: boolean) {
  const [searchParams, setSearchParams] = useSearchParams();

  // Парсим параметры из URL
  const limit = Number(searchParams.get("limit")) || defaultLimit;
  const offset = Number(searchParams.get("offset")) || 0;

  // Собираем фильтры для API (строка вида "key1=val,val;key2=val2")
  const filterParts: string[] = [];
  searchParams.forEach((value, key) => {
    if (ALLOWED_FILTERS.includes(key) && value) {
      filterParts.push(`${key}=${value}`);
    }
  });
  const filterString = filterParts.join(";");

  const { data, isLoading, isError, error, isFetching } = useQuery({
    // Уникальный ключ. Любое изменение этих переменных вызовет новый запрос.
    queryKey: ['orders', 'list', { isDraft: Number(isDraft), limit, offset, filter: filterString }],

    queryFn: () => orderApi.getOrders({
      limit,
      offset,
      is_draft: Number(isDraft),
      filter: filterString
    }),

    // Оставляем старые данные на экране, пока грузятся новые (для плавности таблицы)
    placeholderData: keepPreviousData,
    staleTime: 300, // Данные считаются свежими 300мс
  });

  // Хелперы для управления URL
  const setPage = (newOffset: number) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      p.set("offset", String(newOffset));
      return p;
    });
  };

  const setLimit = (newLimit: number) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      p.set("limit", String(newLimit));
      p.set("offset", "0");
      return p;
    });
  };

  const updateFilters = (newFilters: Record<string, string | number | null | number[]>) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);

      Object.entries(newFilters).forEach(([key, value]) => {
        // Если передан массив (например, статусы [1,2]), превращаем в "1,2"
        if (Array.isArray(value)) {
          if (value.length > 0) {
            p.set(key, value.join(','));
          } else {
            p.delete(key);
          }
        }
        // Обычные значения
        else if (value !== null && value !== undefined && value !== "") {
          p.set(key, String(value));
        }
        // Удаление
        else {
          p.delete(key);
        }
      });

      p.set("offset", "0"); // Сброс страницы при фильтрации
      return p;
    });
  };


  return {
    orders: data?.data?.orders || [],
    pagination: data?.data?.pagination || { limit, offset, total: 0 },
    loading: isLoading,
    isFetching,
    error: isError ? (error as Error).message : null,

    setPage,
    setLimit,
    updateFilters,

    activeFilters: { // Для удобства в компоненте
      dealer_prefix: searchParams.get("dealer_prefix"),
      dealer_user_id: Number(searchParams.get("dealer_user_id")) || null,
      status_id: searchParams.get("status_id")?.split(",").map(Number) || []
    }
  };
}