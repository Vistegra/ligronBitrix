"use client";

import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import {FilterIcon} from "lucide-react";
import {useAuthStore} from "@/store/authStore";
import {useMemo} from "react";
import type {ManagerDetailed} from "@/types/user";

interface OrdersTableUserFiltersProps {
  selectedDealerPrefix: string | null;
  selectedUserId: number | null;
  // Обновляем сигнатуру: теперь принимаем и префикс
  onSelect: (userId: number | null, dealerPrefix?: string | null) => void;
}

export function OrdersTableUserFilters({
                                         selectedDealerPrefix,
                                         selectedUserId,
                                         onSelect,
                                       }: OrdersTableUserFiltersProps) {
  const {user} = useAuthStore();

  // Вычисляем список пользователей с привязкой к префиксу дилера
  const userOptions = useMemo(() => {
    const allDealers = (user?.detailed as ManagerDetailed)?.managed_dealers || [];

    // 1. Определяем, каких дилеров сканировать
    const dealersToScan = selectedDealerPrefix
      ? allDealers.filter((d) => d.dealer_prefix === selectedDealerPrefix)
      : allDealers;

    // 2. Собираем плоский список: { ...user, dealerPrefix }
    return dealersToScan.flatMap((dealer) =>
      dealer.users.map((u) => ({
        id: u.id,
        name: u.name,
        dealerPrefix: dealer.dealer_prefix, // Запоминаем префикс
      }))
    );
  }, [user, selectedDealerPrefix]);

  const handleSelect = (userId: number, prefix: string) => {
    if (userId === selectedUserId) {
      // Если снимаем выделение — просто сбрасываем юзера (префикс можно оставить или нет, зависит от логики, пока оставляем как есть)
      onSelect(null);
    } else {
      // Если выбираем — передаем и ID, и Префикс этого юзера
      onSelect(userId, prefix);
    }
  };

  // Если список пуст, просто выводим текст
  if (userOptions.length === 0) return <span>Пользователь</span>;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className={`flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0 ${selectedUserId ? "text-primary" : ""}`}
        >
          Пользователь
          <FilterIcon className="h-3 w-3 ml-1"/>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-56 max-h-80 overflow-y-auto">
        <DropdownMenuCheckboxItem
          checked={selectedUserId === null}
          onCheckedChange={() => onSelect(null)}
        >
          Все пользователи
        </DropdownMenuCheckboxItem>
        <DropdownMenuSeparator/>

        {userOptions.map((u) => (
          <DropdownMenuCheckboxItem
            key={`${u.dealerPrefix}_${u.id}`} // Уникальный ключ на случай одинаковых ID в разных базах
            checked={selectedUserId === u.id}
            // Передаем префикс конкретного пользователя
            onCheckedChange={() => handleSelect(u.id, u.dealerPrefix)}
            onSelect={(e) => e.preventDefault()}
          >
            {u.name}
          </DropdownMenuCheckboxItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}