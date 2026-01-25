import { useState, useCallback, useEffect } from "react";
import { safeStorage } from "@/helpers/storage";
import type { PartVisibleColumns, PageSize, ColumnKey } from "@/components/Order/Orders/types";

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
  const COLUMNS_KEY = `table_cols_${storageKey}`;
  const PAGE_SIZE_KEY = `table_size_${storageKey}`;

  /**
   * Очищает и фильтрует данные из хранилища.
   */
  const getFilteredColumns = useCallback(
    (saved: unknown): PartVisibleColumns => {
      // Проверяем, что saved — это объект и не null
      if (!saved || typeof saved !== "object") {
        return initialVisibleColumns;
      }

      // Приводим к типу записи, чтобы можно было обращаться по ключам
      const savedData = saved as Record<string, boolean>;
      const filtered: PartVisibleColumns = {};

      // Итерируемся строго по ключам ПРЕСЕТА (initialVisibleColumns)
      (Object.keys(initialVisibleColumns) as ColumnKey[]).forEach((key) => {
        // Если в сохраненных есть значение — берем его, иначе — дефолт из пресета
        filtered[key] = savedData[key] !== undefined ? savedData[key] : initialVisibleColumns[key];
      });

      return filtered;
    },
    [initialVisibleColumns]
  );

  // Инициализация колонок
  const [visibleColumns, setVisibleColumnsState] = useState<PartVisibleColumns>(() => {
    const saved = safeStorage.get(COLUMNS_KEY);
    return getFilteredColumns(saved);
  });

  // Инициализация размера страницы
  const [pageSize, setPageSizeState] = useState<PageSize>(() => {
    const saved = safeStorage.get(PAGE_SIZE_KEY);
    // Проверяем, что сохраненное значение является валидным PageSize (числом)
    return typeof saved === "number" ? (saved as PageSize) : initialPageSize;
  });

  // Синхронизация при смене storageKey или пресета
  useEffect(() => {
    const saved = safeStorage.get(COLUMNS_KEY);
    setVisibleColumnsState(getFilteredColumns(saved));

    const savedSize = safeStorage.get(PAGE_SIZE_KEY);
    if (typeof savedSize === "number") {
      setPageSizeState(savedSize as PageSize);
    }
  }, [storageKey, getFilteredColumns, COLUMNS_KEY, PAGE_SIZE_KEY]);

  const setVisibleColumns = useCallback(
    (updater: PartVisibleColumns | ((prev: PartVisibleColumns) => PartVisibleColumns)) => {
      setVisibleColumnsState((prev) => {
        const next = typeof updater === "function" ? updater(prev) : updater;

        // В localStorage сохраняем только те ключи, которые разрешены в начальном пресете
        const toSave: PartVisibleColumns = {};
        (Object.keys(initialVisibleColumns) as ColumnKey[]).forEach((key) => {
          if (next[key] !== undefined) {
            toSave[key] = next[key];
          }
        });

        safeStorage.set(COLUMNS_KEY, toSave);
        return next;
      });
    },
    [COLUMNS_KEY, initialVisibleColumns]
  );

  const setPageSize = useCallback(
    (size: PageSize) => {
      setPageSizeState(size);
      safeStorage.set(PAGE_SIZE_KEY, size);
    },
    [PAGE_SIZE_KEY]
  );

  return { visibleColumns, setVisibleColumns, pageSize, setPageSize };
}