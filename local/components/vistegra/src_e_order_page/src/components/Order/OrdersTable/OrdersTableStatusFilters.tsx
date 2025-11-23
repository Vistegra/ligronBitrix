"use client";

import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import {ChevronsUpDownIcon} from "lucide-react";

interface Status {
  id: number;
  name: string;
  color: string;
}

interface OrdersTableFiltersProps {
  selectedStatusIds: number[];
  statuses: Status[];
  statusesLoading: boolean;
  onStatusToggle: (statusIds: number[]) => void;
}

export function OrdersTableStatusFilters({
                                           selectedStatusIds,
                                           statuses,
                                           statusesLoading,
                                           onStatusToggle,
                                         }: OrdersTableFiltersProps) {

  const handleStatusToggle = (statusId: number) => {
    const isSelected = selectedStatusIds.includes(statusId);
    let newIds: number[];

    if (isSelected) {
      newIds = selectedStatusIds.filter((id) => id !== statusId);
    } else {
      newIds = [...selectedStatusIds, statusId];
    }

    onStatusToggle(newIds);
  };

  const handleSelectAll = () => {
    onStatusToggle([]);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className={`flex items-center gap-1 p-0 h-auto font-medium focus-visible:outline-none focus-visible:ring-0 ${selectedStatusIds.length > 0 ? "text-primary" : ""}`}
        >
          Статус
          {selectedStatusIds.length > 0 && ` (${selectedStatusIds.length})`}
          <ChevronsUpDownIcon className="h-4 w-4"/>
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="start" className="w-56 max-h-80 overflow-y-auto">
        {statusesLoading ? (
          <div className="p-2 text-sm">Загрузка...</div>
        ) : (
          <>
            {/* "Все статусы" */}
            <DropdownMenuCheckboxItem
              checked={selectedStatusIds.length === 0}
              onCheckedChange={handleSelectAll}
            >
              Все статусы
            </DropdownMenuCheckboxItem>

            <DropdownMenuSeparator/>

            {/* Список статусов */}
            {statuses.map((status) => (
              <DropdownMenuCheckboxItem
                key={status.id}
                checked={selectedStatusIds.includes(status.id)}
                onCheckedChange={() => handleStatusToggle(status.id)}
                onSelect={(e) => e.preventDefault()}
              >
                <div className="flex items-center gap-2">
                  <div
                    className="h-3 w-3 rounded-full"
                    style={{backgroundColor: status.color}}
                  />
                  {status.name}
                </div>
              </DropdownMenuCheckboxItem>
            ))}
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}