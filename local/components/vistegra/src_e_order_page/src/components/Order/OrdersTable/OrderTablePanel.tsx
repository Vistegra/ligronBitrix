"use client";

import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {Columns2Icon, ChevronDownIcon, PlusIcon} from "lucide-react";
import {COLUMN_DEFINITIONS, type ColumnKey, type PartVisibleColumns} from "./types";

import NewOrderForm from "@/components/Order/NewOrderForm.tsx";
import {Modal} from "@/components/Modal.tsx";
import {useAuthStore} from "@/store/authStore";

interface OrdersTablePanelProps {
  visibleColumns: PartVisibleColumns;
  setVisibleColumns: (
    cols: PartVisibleColumns | ((prev: PartVisibleColumns) => PartVisibleColumns)
  ) => void;
  selectedUserId: number | null;
}

export function OrdersTablePanel({
                                   visibleColumns,
                                   setVisibleColumns,
                                   selectedUserId,
                                 }: OrdersTablePanelProps) {
  const {user} = useAuthStore();

  // Логика отображения кнопки:
  // 1. Дилер видит кнопку всегда.
  // 2. Менеджер Лигрон видит кнопку ТОЛЬКО если выбран конкретный пользователь (selectedUserId не null).

  const canCreateOrder =
    user?.role === 'dealer' ||
    (user?.role === 'manager' && !!selectedUserId);

  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h2 className="text-2xl font-bold">Заказы</h2>

      <div className="flex flex-row gap-4 align-items-stretch">
        {/* Выбор колонок */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" className="gap-1">
              <Columns2Icon className="h-4 w-4"/>
              Колонки <ChevronDownIcon className="h-4 w-4"/>
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
          <Modal
            title="Новый заказ"
            description="Заполните необходимые поля для создания нового заказа"
            trigger={
              <Button>
                <PlusIcon className="mr-2 h-4 w-4"/>
                Добавить заказ
              </Button>
            }
          >
            <NewOrderForm/>
          </Modal>
        )}
      </div>
    </div>
  );
}