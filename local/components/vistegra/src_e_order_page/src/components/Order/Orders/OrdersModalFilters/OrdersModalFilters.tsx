"use client";

import { useState, useEffect, useCallback } from "react";
import { Filter } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ResponsiveSheet } from "@/components/ResponsiveSheet";
import { useOrderUrlState } from "@/hooks/order/useOrderUrlState";
import { useOrderStatuses } from "@/hooks/order/useOrderStatuses";
import { useAuthStore } from "@/store/authStore";

import { FilterSection } from "./FilterSection";
import { FilterStatuses } from "./FilterStatuses";
import { FilterOrigin } from "./FilterOrigin";
import { FilterDealers } from "./FilterDealers/FilterDealers";
import { FilterDateSection } from "./FilterDate/FilterDateSection";
import type { OrderFilterState } from "@/components/Order/Orders/types.ts";

export function OrdersModalFilters() {
  const { activeFilters, updateFilters } = useOrderUrlState();
  const { statuses } = useOrderStatuses();
  const { user } = useAuthStore();

  // pendingFilters — состояние фильтров, ожидающих применения
  const [pendingFilters, setPendingFilters] = useState<OrderFilterState>(activeFilters as OrderFilterState);
  const [open, setOpen] = useState(false);

  // Синхронизация локального состояния с URL при открытии модалки
  useEffect(() => {
    if (open) setPendingFilters(activeFilters as OrderFilterState);
  }, [open, activeFilters]);

  // Универсальный хелпер для обновления полей в "ожидающих" фильтрах
  const updatePendingFilters = useCallback((patch: Partial<OrderFilterState>) => {
    setPendingFilters(prev => ({ ...prev, ...patch }));
  }, []);

  const handleApply = () => {
    updateFilters(pendingFilters);
    setOpen(false);
  };

  const handleClear = () => {
    const emptyFilters: OrderFilterState = {
      status_id: [],
      dealer_prefix: null,
      dealer_user_id: null,
      origin_type: [],
      created_at_from: "",
      created_at_to: "",
      updated_at_from: "",
      updated_at_to: "",
    };
    setPendingFilters(emptyFilters);
  };

  const dealers = (user?.detailed as any)?.managed_dealers || [];

  return (
    <ResponsiveSheet
      open={open}
      onOpenChange={setOpen}
      title="Фильтры"
      trigger={
        <Button variant="outline" size="icon" className="relative shrink-0">
          <Filter className="h-5 w-5" />
          <FilterCounter filters={activeFilters} />
        </Button>
      }
      headerAction={
        <Button variant="ghost" size="sm" onClick={handleClear} className="text-muted-foreground">
          Сбросить
        </Button>
      }
    >
      <div className="flex flex-col h-full space-y-2 pb-20">
        <FilterSection title="Статусы" value="statuses">
          <FilterStatuses
            statuses={statuses}
            values={pendingFilters.status_id}
            onChange={(val) => updatePendingFilters({ status_id: val })}
          />
        </FilterSection>

        <FilterSection title="Даты" value="dates" defaultOpen={false}>
          <FilterDateSection
            values={pendingFilters}
            onChange={updatePendingFilters}
          />
        </FilterSection>

        <FilterSection title="Источник заказа" value="origin" defaultOpen={false}>
          <FilterOrigin
            values={pendingFilters.origin_type}
            onChange={(val) => updatePendingFilters({ origin_type: val })}
          />
        </FilterSection>

        {dealers.length > 0 && (
          <FilterSection title="Дилеры и пользователи" value="dealers" defaultOpen={false}>
            <FilterDealers
              dealers={dealers}
              values={pendingFilters}
              onChange={updatePendingFilters}
            />
          </FilterSection>
        )}
      </div>

      <div className="mt-auto pt-4 border-t sticky bottom-0 bg-background pb-safe">
        <Button className="w-full h-12 text-base" onClick={handleApply}>
          Показать результаты
        </Button>
      </div>
    </ResponsiveSheet>
  );
}

function FilterCounter({ filters }: { filters: OrderFilterState }) {
  const count = [
    filters.status_id.length > 0,
    !!filters.dealer_prefix,
    filters.origin_type.length > 0,
    !!filters.created_at_from || !!filters.created_at_to,
    !!filters.updated_at_from || !!filters.updated_at_to
  ].filter(Boolean).length;

  if (count === 0) return null;
  return (
    <Badge className="absolute -top-2 -right-2 h-5 w-5 p-0 flex justify-center items-center rounded-full text-[10px]">
      {count}
    </Badge>
  );
}