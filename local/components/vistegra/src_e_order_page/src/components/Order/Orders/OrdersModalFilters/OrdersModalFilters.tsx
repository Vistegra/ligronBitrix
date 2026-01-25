"use client";

import {useState, useEffect, useCallback, useMemo} from "react";
import {Filter} from "lucide-react";
import {Button} from "@/components/ui/button";
import {Badge} from "@/components/ui/badge";
import {ResponsiveSheet} from "@/components/ResponsiveSheet";
import {useOrderUrlState} from "@/hooks/order/useOrderUrlState";
import {useOrderStatuses} from "@/hooks/order/useOrderStatuses";
import {useAuthStore} from "@/store/authStore";

import {FilterSection} from "./FilterSection";
import {FilterStatuses} from "./FilterStatuses";
import {FilterOrigin} from "./FilterOrigin";
import {FilterDealers} from "./FilterDealers/FilterDealers";
import {FilterDateSection} from "./FilterDate/FilterDateSection";
import type {OrderFilterState} from "@/components/Order/Orders/types.ts";
import type {ManagerDetailed} from "@/types/user";

// Хелпер, который возвращает карту "активности" каждой группы
const getFilterActivity = (f: OrderFilterState) => ({
  statuses: f.status_id.length > 0,
  dates: !!(f.created_at_from || f.created_at_to || f.updated_at_from || f.updated_at_to),
  origin: f.origin_type.length > 0,
  dealers: !!f.dealer_prefix, // или !!f.dealer_user_id
});

export function OrdersModalFilters() {
  const {activeFilters, updateFilters} = useOrderUrlState();
  const {statuses} = useOrderStatuses();
  const {user} = useAuthStore();

  const [pendingFilters, setPendingFilters] = useState<OrderFilterState>(activeFilters as OrderFilterState);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (open) setPendingFilters(activeFilters as OrderFilterState);
  }, [open, activeFilters]);

  const updatePendingFilters = useCallback((patch: Partial<OrderFilterState>) => {
    setPendingFilters(prev => ({...prev, ...patch}));
  }, []);


  // Для текущих выбранных в модалке (для подсветки и раскрытия групп)
  const pendingActivity = useMemo(() => getFilterActivity(pendingFilters), [pendingFilters]);

  // Для уже примененных в URL (для счетчика на кнопке открытия)
  const activeCount = useMemo(() => {
    const activity = getFilterActivity(activeFilters);
    return Object.values(activity).filter(Boolean).length;
  }, [activeFilters]);

  const handleApply = () => {
    updateFilters(pendingFilters);
    setOpen(false);
  };

  const handleClear = () => {
    setPendingFilters({
      status_id: [],
      dealer_prefix: null,
      dealer_user_id: null,
      origin_type: [],
      created_at_from: "",
      created_at_to: "",
      updated_at_from: "",
      updated_at_to: "",
    });
  };

  const dealers = (user?.detailed as ManagerDetailed )?.managed_dealers || [];

  return (
    <ResponsiveSheet
      open={open}
      onOpenChange={setOpen}
      title="Фильтры"
      trigger={
        <Button variant="outline" size="icon" className="relative shrink-0">
          <Filter className="h-5 w-5"/>
          {activeCount > 0 && (
            <Badge
              className="absolute -top-2 -right-2 h-5 w-5 p-0 flex justify-center items-center rounded-full text-[10px]">
              {activeCount}
            </Badge>
          )}
        </Button>
      }
      headerAction={
        <Button variant="ghost" size="sm" onClick={handleClear} className="text-muted-foreground">
          Сбросить
        </Button>
      }
    >
      <div className="flex flex-col h-full space-y-2 pb-20">
        <FilterSection
          title="Статусы"
          value="statuses"
          isActive={pendingActivity.statuses}
        >
          <FilterStatuses
            statuses={statuses}
            values={pendingFilters.status_id}
            onChange={(val) => updatePendingFilters({status_id: val})}
          />
        </FilterSection>

        <FilterSection
          title="Даты"
          value="dates"
          isActive={pendingActivity.dates}
        >
          <FilterDateSection
            values={pendingFilters}
            onChange={updatePendingFilters}
          />
        </FilterSection>

        <FilterSection
          title="Источник заказа"
          value="origin"
          isActive={pendingActivity.origin}
        >
          <FilterOrigin
            values={pendingFilters.origin_type}
            onChange={(val) => updatePendingFilters({origin_type: val})}
          />
        </FilterSection>

        {dealers.length > 0 && (
          <FilterSection
            title="Дилеры и пользователи"
            value="dealers"
            isActive={pendingActivity.dealers}
          >
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

/*
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
}*/
