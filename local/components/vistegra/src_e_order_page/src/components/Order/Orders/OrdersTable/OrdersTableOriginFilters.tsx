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
import {getOriginLabel} from "@/components/Order/Orders/utils";

// Константы типов источников (0=App, 1=1C, 2=Calc)
const ORIGINS = [0, 1, 2];

interface OrdersTableOriginFiltersProps {
  selectedOrigins: number[];
  onToggle: (ids: number[]) => void;
}

export function OrdersTableOriginFilters({
                                           selectedOrigins,
                                           onToggle,
                                         }: OrdersTableOriginFiltersProps) {

  const handleToggle = (id: number) => {
    const isSelected = selectedOrigins.includes(id);
    let newIds: number[];

    if (isSelected) {
      newIds = selectedOrigins.filter((currentId) => currentId !== id);
    } else {
      newIds = [...selectedOrigins, id];
    }

    onToggle(newIds);
  };

  const handleSelectAll = () => {
    onToggle([]);
  };

  // Хелпер для получения чистого цвета фона для кружочка в меню
  const getDotColorClass = (id: number) => {
    switch (id) {
      case 1:
        return "bg-yellow-500"; // 1C
      case 2:
        return "bg-green-500";   // Calc
      default:
        return "bg-gray-500";  // Site
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className={`flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0 ${
            selectedOrigins.length > 0 ? "text-primary" : ""
          }`}
        >
          Источник
          {selectedOrigins.length > 0 && ` (${selectedOrigins.length})`}
          <FilterIcon className="h-3 w-3 ml-1"/>
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="start" className="w-56">
        {/* Пункт "Все источники" */}
        <DropdownMenuCheckboxItem
          checked={selectedOrigins.length === 0}
          onCheckedChange={handleSelectAll}
        >
          Все источники
        </DropdownMenuCheckboxItem>

        <DropdownMenuSeparator/>

        {/* Список источников */}
        {ORIGINS.map((id) => {
          const {label} = getOriginLabel(id);
          const dotColor = getDotColorClass(id);

          return (
            <DropdownMenuCheckboxItem
              key={id}
              checked={selectedOrigins.includes(id)}
              onCheckedChange={() => handleToggle(id)}
              onSelect={(e) => e.preventDefault()}
            >
              <div className="flex items-center gap-2">
                <div className={`h-3 w-3 rounded-full shrink-0 ${dotColor}`}/>
                {label}
              </div>
            </DropdownMenuCheckboxItem>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}