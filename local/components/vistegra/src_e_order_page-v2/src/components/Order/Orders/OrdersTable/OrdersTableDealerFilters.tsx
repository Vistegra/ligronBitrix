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
import {useAuthStore} from "@/store/authStore.ts";

interface OrdersTableDealerFiltersProps {
  selectedInns: string[];
  onToggle: (inns: string[]) => void;
}

export function OrdersTableDealerFilters({
                                           selectedInns,
                                           onToggle,
                                         }: OrdersTableDealerFiltersProps) {
  const {user} = useAuthStore();
  const hierarchy = user?.detailed?.hierarchy || [];

  if (hierarchy.length === 0) return <span>Дилер</span>;

  const handleSelect = (inn: string) => {
    const isSelected = selectedInns.includes(inn);
    const next = isSelected
      ? selectedInns.filter(id => id !== inn)
      : [...selectedInns, inn];
    onToggle(next);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className={`flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0 ${
            selectedInns.length > 0 ? "text-primary" : ""
          }`}
        >
          Дилер
          {selectedInns.length > 0 && ` (${selectedInns.length})`}
          <FilterIcon className="h-3 w-3 ml-1"/>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-64 custom-scrollbar">
        <DropdownMenuCheckboxItem
          checked={selectedInns.length === 0}
          onCheckedChange={() => onToggle([])}
        >
          Все дилеры
        </DropdownMenuCheckboxItem>
        <DropdownMenuSeparator/>

        {hierarchy.map((dealer) => (
          <DropdownMenuCheckboxItem
            key={dealer.inn}
            checked={selectedInns.includes(dealer.inn)}
            onCheckedChange={() => handleSelect(dealer.inn)}
            onSelect={(e) => e.preventDefault()}
          >
            <div className="flex flex-col">
              <span className="truncate">{dealer.name}</span>
              <span className="text-[10px] text-muted-foreground">{dealer.inn}</span>
            </div>
          </DropdownMenuCheckboxItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}