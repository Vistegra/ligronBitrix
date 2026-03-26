"use client";

import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {ChevronDownIcon, Columns2Icon, PlusIcon} from "lucide-react";
import {COLUMN_DEFINITIONS, type ColumnKey, type PartVisibleColumns} from "../types.ts";

import NewOrderForm from "@/components/Order/NewOrderForm";

import {useAuthStore} from "@/store/authStore.ts";
import {useContextStore} from "@/store/contextStore.ts";
import {ResponsiveSheet} from "@/components/ResponsiveSheet";
import {OrdersModalFilters} from "@/components/Order/Orders/OrdersModalFilters/OrdersModalFilters";
import {OrdersSearch} from "@/components/Order/Orders/OrdersSearch";
import {checkCanCreateOrder} from "@/components/Order/Orders/utils.ts";

interface OrdersTablePanelProps {
  visibleColumns: PartVisibleColumns;
  setVisibleColumns: (
    cols: PartVisibleColumns | ((prev: PartVisibleColumns) => PartVisibleColumns)
  ) => void;
  isDraft: boolean;
}

export function OrdersTablePanel({
                                   visibleColumns,
                                   setVisibleColumns,
                                   isDraft
                                 }: OrdersTablePanelProps) {
  const {user} = useAuthStore();

  const {inn, salonCode} = useContextStore();

  const canCreateOrder = checkCanCreateOrder(user, inn, salonCode);

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold">{isDraft ? "Черновики" : "Заказы"}</h2>

        {/* Левая часть: Поиск */}
        <div className="w-full sm:w-auto">
          <OrdersSearch className="w-full sm:w-[300px] lg:w-[600px]" placeholder="Поиск по названию или номеру..."/>
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
              {(Object.keys(COLUMN_DEFINITIONS) as ColumnKey[]).map((key) => {
                if (!(key in visibleColumns)) return null;

                const column = COLUMN_DEFINITIONS[key];

                return (
                  <DropdownMenuCheckboxItem
                    key={key}
                    checked={!!visibleColumns[key]}
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

          {/* Кнопка создания заказа */}
          {canCreateOrder && (
            <ResponsiveSheet
              title="Новый заказ"
              description={`Оформление для салона: ${salonCode}`}
              trigger={
                <Button className="bg-green-600 hover:bg-green-700 text-white shadow-sm">
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