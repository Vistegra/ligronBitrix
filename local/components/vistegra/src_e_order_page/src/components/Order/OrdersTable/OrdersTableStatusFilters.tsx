"use client";

import {Button} from "@/components/ui/button";
import {useState} from "react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {ChevronsUpDownIcon} from "lucide-react";

import {useDebounce} from "@/hooks/useDebounce.ts";

interface Status {
  id: number;
  name: string;
  color: string;
}

interface OrdersTableFiltersProps {
  filter: string;
  statuses: Status[];
  statusesLoading: boolean;
  onStatusToggle: (statusIds: number[]) => void;
}

export function OrdersTableStatusFilters({
                                     statuses,
                                     statusesLoading,
                                     onStatusToggle,
                                   }: OrdersTableFiltersProps) {

  const [selectedStatusIds, setSelectedStatusIds] = useState<number[]>([])


  const debouncedToggle = useDebounce((ids: number[]) => {
    onStatusToggle(ids);
  }, 800);

  const handleStatusToggle = (statusId: number) => {
    setSelectedStatusIds((prev) => {
      const newSelectedIds = prev.includes(statusId)
        ? prev.filter(id => id !== statusId)
        : [...prev, statusId];

      debouncedToggle(newSelectedIds);
      return newSelectedIds;
    })
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0">
          Статус
          {selectedStatusIds.length > 0 && ` (${selectedStatusIds.length})`}
          <ChevronsUpDownIcon className="h-4 w-4"/>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start" className="w-56">
        {statusesLoading ? (
          <div className="p-2 text-sm">Загрузка...</div>
        ) : (
          statuses.map(status => (
            <DropdownMenuCheckboxItem
              key={status.id}
              checked={selectedStatusIds.includes(status.id)}
              onCheckedChange={() => handleStatusToggle(status.id)}
              onSelect={(e) => e.preventDefault()}
            >
              <div className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full" style={{backgroundColor: status.color}}/>
                {status.name}
              </div>
            </DropdownMenuCheckboxItem>
          ))
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}