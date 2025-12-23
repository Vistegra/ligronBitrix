import {useSearchParams} from "react-router-dom";
import {useCallback, useMemo} from "react";

const ALLOWED_FILTERS = [
  "status_id",
  "dealer_prefix",
  "dealer_user_id",
  "parent_id",
  "origin_type",
  // "date_from",
  // "date_to",
];

export function useOrderUrlState(defaultLimit = 20) {
  const [searchParams, setSearchParams] = useSearchParams();

  // 1. Пагинация
  const limit = Number(searchParams.get("limit")) || defaultLimit;
  const offset = Number(searchParams.get("offset")) || 0;

  // 2. Поиск
  const rawSearch = searchParams.get("search") ?? '';
  // Превращаем "тест" в "name=тест" для API
  const searchString = rawSearch ? `name=${rawSearch}` : undefined;

  // 3. Фильтры
  const filterString = useMemo(() => {
    const parts: string[] = [];
    searchParams.forEach((value, key) => {
      if (ALLOWED_FILTERS.includes(key) && value) {
        parts.push(`${key}=${value}`);
      }
    });
    return parts.join(";");
  }, [searchParams]);

  // 4. Активные фильтры (объект для UI)
  const activeFilters = useMemo(() => ({
    dealer_prefix: searchParams.get("dealer_prefix"),
    dealer_user_id: Number(searchParams.get("dealer_user_id")) || null,
    status_id: searchParams.get("status_id")?.split(",").map(Number) || [],
    origin_type: searchParams.get("origin_type")?.split(",").map(Number) || []
  }), [searchParams]);

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

  const updateFilters = useCallback((newFilters: Record<string, string | number | null | number[]>) => {
    setSearchParams((prev) => {
      const p = new URLSearchParams(prev);

      Object.entries(newFilters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
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

      p.set("offset", "0"); // Сброс страницы при фильтрации
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
  };
}