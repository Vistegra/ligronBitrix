"use client";

import { useMemo } from "react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { FilterIcon } from "lucide-react";
import { useAuthStore } from "@/store/authStore.ts";

interface OrdersTableSalonFiltersProps {
  selectedInns: string[];
  selectedSalons: string[];
  onToggle: (codes: string[]) => void;
}

export function OrdersTableSalonFilters({
                                          selectedInns,
                                          selectedSalons,
                                          onToggle,
                                        }: OrdersTableSalonFiltersProps) {
  const { user } = useAuthStore();

  // eslint-disable-next-line react-hooks/exhaustive-deps
  const hierarchy = user?.detailed?.hierarchy || [];

  // Собираем список салонов для отображения
  const availableSalons = useMemo(() => {
    // Если выбраны конкретные ИНН, берем салоны только этих дилеров
    const dealers = selectedInns.length > 0
      ? hierarchy.filter(d => selectedInns.includes(d.inn))
      : hierarchy;

    // Плоский массив уникальных салонов
    const map = new Map<string, string>();
    dealers.forEach(d => {
      d.salons.forEach(s => map.set(s.salon_code, s.name));
    });

    return Array.from(map.entries()).map(([code, name]) => ({ code, name }));
  }, [hierarchy, selectedInns]);

  const handleSelect = (code: string) => {
    const isSelected = selectedSalons.includes(code);
    const next = isSelected
      ? selectedSalons.filter(c => c !== code)
      : [...selectedSalons, code];
    onToggle(next);
  };

  if (availableSalons.length === 0) return <span>Салон</span>;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className={`flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0 ${
            selectedSalons.length > 0 ? "text-primary" : ""
          }`}
        >
          Салон
          {selectedSalons.length > 0 && ` (${selectedSalons.length})`}
          <FilterIcon className="h-3 w-3 ml-1" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-64">
        <DropdownMenuCheckboxItem
          checked={selectedSalons.length === 0}
          onCheckedChange={() => onToggle([])}
        >
          Все салоны
        </DropdownMenuCheckboxItem>
        <DropdownMenuSeparator />

        {availableSalons.map((s) => (
          <DropdownMenuCheckboxItem
            key={s.code}
            checked={selectedSalons.includes(s.code)}
            onCheckedChange={() => handleSelect(s.code)}
            onSelect={(e) => e.preventDefault()}
          >
            <div className="flex flex-col">
              <span className="truncate">{s.name}</span>
              <span className="text-[10px] text-muted-foreground">{s.code}</span>
            </div>
          </DropdownMenuCheckboxItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}