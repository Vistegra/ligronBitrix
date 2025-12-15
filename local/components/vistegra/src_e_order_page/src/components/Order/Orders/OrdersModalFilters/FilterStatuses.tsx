"use client";

import {Label} from "@/components/ui/label";
import {Checkbox} from "@/components/ui/checkbox";
import type {OrderStatus} from "@/api/orderApi.ts";

interface FilterStatusesProps {
  statuses: OrderStatus[];
  selectedIds: number[];
  onToggle: (id: number) => void;
}

export function FilterStatuses({statuses, selectedIds, onToggle}: FilterStatusesProps) {
  return (
    <div className="space-y-4">
      <Label className="text-base font-semibold">Статусы</Label>
      <div className="grid grid-cols-1 gap-3">
        {statuses.map((status) => (
          <div key={status.id} className="flex items-center space-x-3 py-1">
            <Checkbox
              id={`st-${status.id}`}
              checked={selectedIds.includes(status.id)}
              onCheckedChange={() => onToggle(status.id)}
              className="h-5 w-5"
            />
            <label
              htmlFor={`st-${status.id}`}
              className="flex items-center gap-2 text-sm font-medium leading-none w-full py-1"
            >
              <div
                className="h-3 w-3 rounded-full shrink-0"
                style={{backgroundColor: status.color}}
              />
              {status.name}
            </label>
          </div>
        ))}
      </div>
    </div>
  );
}