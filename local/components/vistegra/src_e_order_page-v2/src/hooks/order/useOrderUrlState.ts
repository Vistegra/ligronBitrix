import {useSearchParams} from "react-router-dom";
import {useCallback, useMemo} from "react";
import type {OrderFilterState} from "@/components/Order/Orders/types.ts";

const ALLOWED_FILTERS = [
  "search",
  "status_id",
  "inn_dealer",
  "salon_code",
  "origin_type",
  "created_at_from",
  "created_at_to",
  "updated_at_from",
  "updated_at_to",
];

export function useOrderUrlState(defaultLimit = 20) {
  const [searchParams, setSearchParams] = useSearchParams();

  // Сортировка
  const sortParam = searchParams.get("sort");

  const sortConfig = useMemo(() => {
    if (!sortParam) return {field: null, direction: null};
    const [field, direction] = sortParam.split(":");
    return {field, direction: direction as "asc" | "desc"};
  }, [sortParam]);

  const toggleSort = useCallback((key: string) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);
      const currentOrder = p.get("sort");

      if (currentOrder && currentOrder.startsWith(`${key}:`)) {
        const [, dir] = currentOrder.split(":");
        if (dir === "asc") {
          p.set("sort", `${key}:desc`);
        } else {
          p.delete("sort");
        }
      } else {
        p.set("sort", `${key}:asc`);
      }

      p.set("offset", "0");
      return p;
    });
  }, [setSearchParams]);

  // Пагинация
  const limit = Number(searchParams.get("limit")) || defaultLimit;
  const offset = Number(searchParams.get("offset")) || 0;

  // Поиск
  const rawSearch = searchParams.get("search") ?? '';
  const searchString = rawSearch ? rawSearch : undefined;

  // Фильтры для api (строка key=v1,v2;key2=v3)
  const filterString = useMemo(() => {
    const parts: string[] = [];
    searchParams.forEach((value, key) => {
      if (ALLOWED_FILTERS.includes(key) && value) {
        parts.push(`${key}=${value}`);
      }
    });
    return parts.join(";");
  }, [searchParams]);

  // Активные фильтры для ui
  const activeFilters = useMemo<OrderFilterState>(() => ({
    search: searchParams.get("search") || "",
    // Новая логика множественного выбора для Дилеров и Салонов
    inn_dealer: searchParams.get("inn_dealer")?.split(",").filter(Boolean) || [],
    salon_code: searchParams.get("salon_code")?.split(",").filter(Boolean) || [],

    status_id: searchParams.get("status_id")?.split(",").map(Number).filter(Boolean) || [],
    origin_type: searchParams.get("origin_type")?.split(",").map(Number).filter(Boolean) || [],

    created_at_from: searchParams.get("created_at_from") || "",
    created_at_to: searchParams.get("created_at_to") || "",
    updated_at_from: searchParams.get("updated_at_from") || "",
    updated_at_to: searchParams.get("updated_at_to") || "",
  }), [searchParams]);

  // Методы обновления
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

  const updateFilters = useCallback((newFilters: Partial<OrderFilterState>) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);

      Object.entries(newFilters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
          // Если массив не пустой, пишем через запятую, иначе удаляем ключ
          if (value.length > 0) {
            p.set(key, value.join(','));
          } else {
            p.delete(key);
          }
        } else if (value !== null && value !== undefined && value !== "") {
          p.set(key, String(value));
        } else {
          p.delete(key);
        }
      });

      p.set("offset", "0"); // Сброс страницы при любом изменении фильтра
      return p;
    });
  }, [setSearchParams]);

  return {
    limit,
    offset,
    searchString,
    searchQuery: rawSearch,
    filterString,
    activeFilters,
    setPage,
    setLimit,
    updateFilters,

    sortParam,
    sortConfig,
    toggleSort
  };
}