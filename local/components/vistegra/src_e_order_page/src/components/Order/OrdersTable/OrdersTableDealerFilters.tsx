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
import type {ManagerDetailed} from "@/types/user";

interface OrdersTableDealerFiltersProps {
  selectedDealerPrefix: string | null;
  onSelect: (prefix: string | null) => void;
}

export function OrdersTableDealerFilters({
                                           selectedDealerPrefix,
                                           onSelect,
                                         }: OrdersTableDealerFiltersProps) {
  const {user} = useAuthStore();
  const dealers = (user?.detailed as ManagerDetailed)?.managed_dealers || [];

  if (dealers.length === 0) return <span>Дилер</span>;

  const handleSelect = (prefix: string | null) => {
    // Если кликнули по уже выбранному - сбрасываем
    if (prefix === selectedDealerPrefix) {
      onSelect(null);
    } else {
      onSelect(prefix);
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className={`flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0 ${selectedDealerPrefix ? "text-primary" : ""}`}
        >
          Дилер
          <FilterIcon className="h-3 w-3 ml-1"/>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-64 max-h-80 overflow-y-auto">
        <DropdownMenuCheckboxItem
          checked={selectedDealerPrefix === null}
          onCheckedChange={() => onSelect(null)}
        >
          Все дилеры
        </DropdownMenuCheckboxItem>
        <DropdownMenuSeparator/>

        {dealers.map((dealer) => (
          <DropdownMenuCheckboxItem
            key={dealer.dealer_prefix}
            checked={selectedDealerPrefix === dealer.dealer_prefix}
            onCheckedChange={() => handleSelect(dealer.dealer_prefix)}
            onSelect={(e) => e.preventDefault()}
          >
            <div className="flex flex-col">
              <span>{dealer.name}</span>
              <span className="text-[10px] text-muted-foreground">{dealer.dealer_prefix}</span>
            </div>
          </DropdownMenuCheckboxItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}