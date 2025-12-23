"use client";

import {useState, useEffect} from "react";
import {Filter} from "lucide-react";
import {Button} from "@/components/ui/button";
import {Badge} from "@/components/ui/badge";
import {useOrderStatuses} from "@/hooks/order/useOrderStatuses.ts";
import {useAuthStore} from "@/store/authStore.ts";
import type {ManagerDetailed} from "@/types/user";
import {ResponsiveSheet} from "@/components/ResponsiveSheet";
import {useOrderUrlState} from "@/hooks/order/useOrderUrlState";

import {FilterStatuses} from "./FilterStatuses";
import {FilterDealers} from "./FilterDealers/FilterDealers";
import {FilterOrigin} from "@/components/Order/Orders/OrdersModalFilters/FilterOrigin.tsx";

export function OrdersModalFilters() {
  const {activeFilters, updateFilters} = useOrderUrlState();

  const {statuses} = useOrderStatuses();
  const {user} = useAuthStore();

  const [selectedStatuses, setSelectedStatuses] = useState<number[]>([]);
  const [selectedDealer, setSelectedDealer] = useState<string | null>(null);
  const [selectedUser, setSelectedUser] = useState<number | null>(null);
  const [selectedOrigins, setSelectedOrigins] = useState<number[]>([]);

  const [open, setOpen] = useState(false);


  useEffect(() => {
    if (open) {
      setSelectedStatuses(activeFilters.status_id);
      setSelectedDealer(activeFilters.dealer_prefix);
      setSelectedUser(activeFilters.dealer_user_id);
      setSelectedOrigins(activeFilters.origin_type);
    }
  }, [open, activeFilters]);

  const handleApply = () => {

    updateFilters({
      status_id: selectedStatuses,
      dealer_prefix: selectedDealer,
      dealer_user_id: selectedUser,
      origin_type: selectedOrigins,
    });

    setOpen(false);
  };

  const handleClear = () => {
    setSelectedStatuses([]);
    setSelectedDealer(null);
    setSelectedUser(null);
    setSelectedOrigins([]);
  };

  const toggleOrigin = (id: number) => {
    setSelectedOrigins((prev) =>
      prev.includes(id) ? prev.filter((s) => s !== id) : [...prev, id]
    );
  };


  const handleDealerChange = (prefix: string | null) => {
    if (selectedDealer === prefix) return;
    setSelectedDealer(prefix);
    setSelectedUser(null);
  };

  const toggleStatus = (id: number) => {
    setSelectedStatuses((prev) =>
      prev.includes(id) ? prev.filter((s) => s !== id) : [...prev, id]
    );
  };

  // Счетчик активных фильтров можно считать
  const activeCount =
    (activeFilters.status_id.length > 0 ? 1 : 0) +
    (activeFilters.dealer_prefix ? 1 : 0) +
    (activeFilters.origin_type.length > 0 ? 1 : 0) +
    (activeFilters.dealer_user_id ? 1 : 0);

  const dealers = (user?.detailed as ManagerDetailed)?.managed_dealers || [];

  // Кнопка сброса внутри модалки
  const showReset = selectedStatuses.length > 0 || selectedDealer !== null;

  return (
    <ResponsiveSheet
      open={open}
      onOpenChange={setOpen}
      title="Фильтры"
      description="Настройте параметры отображения"
      headerAction={
        showReset && (
          <Button
            variant="ghost"
            size="sm"
            onClick={handleClear}
            className="h-auto p-0 text-muted-foreground hover:text-destructive font-normal"
          >
            Сбросить
          </Button>
        )
      }
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
    >
      <div className="flex flex-col h-full">
        <div className="space-y-8 pb-20">

          <FilterStatuses
            statuses={statuses}
            selectedIds={selectedStatuses}
            onToggle={toggleStatus}
          />

          <FilterOrigin
            selectedOrigins={selectedOrigins}
            onToggle={toggleOrigin}
          />

          <FilterDealers
            dealers={dealers}
            selectedDealer={selectedDealer}
            selectedUser={selectedUser}
            onDealerChange={handleDealerChange}
            onUserChange={setSelectedUser}
          />

        </div>

        <div className="mt-auto pt-4 border-t sticky bottom-0 bg-background pb-safe">
          <Button className="w-full h-12 text-base" onClick={handleApply}>
            Показать результаты
          </Button>
        </div>
      </div>
    </ResponsiveSheet>
  );
}