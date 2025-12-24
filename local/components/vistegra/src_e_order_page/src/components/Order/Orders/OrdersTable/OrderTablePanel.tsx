"use client";

import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {Columns2Icon, ChevronDownIcon, PlusIcon} from "lucide-react";
import {COLUMN_DEFINITIONS, type ColumnKey, type PartVisibleColumns} from "../types.ts";

import NewOrderForm from "@/components/Order/NewOrderForm";

import {useAuthStore} from "@/store/authStore.ts";
import {ResponsiveSheet} from "@/components/ResponsiveSheet";
import {OrdersModalFilters} from "@/components/Order/Orders/OrdersModalFilters/OrdersModalFilters";
import {OrdersSearch} from "@/components/Order/Orders/OrdersSearch";
import {checkCanCreateOrder} from "@/components/Order/Orders/utils.ts";

interface OrdersTablePanelProps {
  visibleColumns: PartVisibleColumns;
  setVisibleColumns: (
    cols: PartVisibleColumns | ((prev: PartVisibleColumns) => PartVisibleColumns)
  ) => void;
  selectedUserId: number | null;
  isDraft: boolean;
}

export function OrdersTablePanel({
                                   visibleColumns,
                                   setVisibleColumns,
                                   selectedUserId,
                                   isDraft
                                 }: OrdersTablePanelProps) {
  const {user} = useAuthStore();

   const canCreateOrder = checkCanCreateOrder(user, selectedUserId)

  return (
    <div className="flex flex-col gap-4">
      <h2 className="text-2xl font-bold">Заказы</h2>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        {/* Левая часть: Поиск */}
        <div className="w-full sm:w-auto">
          <OrdersSearch className="w-full sm:w-[300px] lg:w-[600px]" placeholder="Поиск по названию ..."/>
        </div>

        {/* Правая часть: Фильтры, Колонки, Создание */}
        <div className="flex flex-row flex-wrap items-center gap-2">

          {!isDraft && <OrdersModalFilters/>}

          {/* Выбор колонок */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline" className="gap-1">
                <Columns2Icon className="h-4 w-4"/>
                <span className="hidden sm:inline">Колонки</span>
                <ChevronDownIcon className="h-4 w-4"/>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
              {Object.entries(visibleColumns).map(([key, isVisible]) => {
                const column = COLUMN_DEFINITIONS[key as ColumnKey];
                return (
                  <DropdownMenuCheckboxItem
                    key={key}
                    checked={isVisible}
                    onCheckedChange={(checked) =>
                      setVisibleColumns((prev) => ({
                        ...prev,
                        [key]: checked,
                      }))
                    }
                    onSelect={(e) => e.preventDefault()}
                  >
                    {column.label}
                  </DropdownMenuCheckboxItem>
                );
              })}
            </DropdownMenuContent>
          </DropdownMenu>

          {/* Кнопка создания заказа*/}
          {canCreateOrder && (
            <ResponsiveSheet
              title="Новый заказ"
              description="Заполните данные для создания черновика"
              trigger={
                <Button>
                  <PlusIcon className="mr-2 h-4 w-4"/>
                  <span className="hidden sm:inline">Добавить заказ</span>
                  <span className="sm:hidden">Добавить</span>
                </Button>
              }
            >
              <NewOrderForm/>
            </ResponsiveSheet>
          )}
        </div>
      </div>
    </div>
  );
}