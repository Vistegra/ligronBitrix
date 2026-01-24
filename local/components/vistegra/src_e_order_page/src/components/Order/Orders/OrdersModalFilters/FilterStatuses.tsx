"use client";

import {Checkbox} from "@/components/ui/checkbox";
import type {OrderStatus} from "@/api/orderApi.ts";

interface FilterStatusesProps {
  statuses: OrderStatus[];
  values: number[]; // Массив выбранных ID
  onChange: (nextValues: number[]) => void; // Возвращает обновленный массив родителю
}

export function FilterStatuses({statuses, values, onChange}: FilterStatusesProps) {

  const handleToggle = (id: number) => {
    const next = values.includes(id)
      ? values.filter((v) => v !== id)
      : [...values, id];
    onChange(next);
  };

  return (
    <div className="grid grid-cols-1 gap-3">
      {statuses.map((status) => (
        <div key={status.id} className="flex items-center space-x-3 py-1">
          <Checkbox
            id={`st-${status.id}`}
            checked={values.includes(status.id)}
            onCheckedChange={() => handleToggle(status.id)}
            className="h-5 w-5"
          />
          <label
            htmlFor={`st-${status.id}`}
            className="flex items-center gap-2 text-sm font-medium leading-none w-full py-1 cursor-pointer select-none"
          >
            <div
              className="h-3 w-3 rounded-full shrink-0"
              style={{backgroundColor: status.color || "#ccc"}}
            />
            {status.name}
          </label>
        </div>
      ))}
    </div>
  );
}