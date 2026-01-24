
import {useState, useCallback} from "react";
import {safeStorage} from "@/helpers/storage";
import type {PartVisibleColumns, PageSize} from "@/components/Order/Orders/types";

interface UseTableSettingsProps {
  storageKey: string;
  initialVisibleColumns: PartVisibleColumns;
  initialPageSize: PageSize;
}

export function useTableSettings({
                                   storageKey,
                                   initialVisibleColumns,
                                   initialPageSize,
                                 }: UseTableSettingsProps) {
  // Ключи для localStorage
  const COLUMNS_KEY = `table_cols_${storageKey}`;
  const PAGE_SIZE_KEY = `table_size_${storageKey}`;

  // Инициализация состояния из localStorage или значений по умолчанию
  const [visibleColumns, setVisibleColumnsState] = useState<PartVisibleColumns>(() => {
    const saved = safeStorage.get(COLUMNS_KEY);
    return saved ? {...initialVisibleColumns, ...saved} : initialVisibleColumns;
  });

  const [pageSize, setPageSizeState] = useState<PageSize>(() => {
    const saved = safeStorage.get(PAGE_SIZE_KEY);
    return saved ? (saved as PageSize) : initialPageSize;
  });

  // Метод для обновления колонок
  const setVisibleColumns = useCallback(
    (updater: PartVisibleColumns | ((prev: PartVisibleColumns) => PartVisibleColumns)) => {
      setVisibleColumnsState((prev) => {
        const next = typeof updater === "function" ? updater(prev) : updater;
        safeStorage.set(COLUMNS_KEY, next);
        return next;
      });
    },
    [COLUMNS_KEY]
  );

  // Метод для обновления размера страницы
  const setPageSize = useCallback(
    (size: PageSize) => {
      setPageSizeState(size);
      safeStorage.set(PAGE_SIZE_KEY, size);
    },
    [PAGE_SIZE_KEY]
  );

  return {
    visibleColumns,
    setVisibleColumns,
    pageSize,
    setPageSize,
  };
}