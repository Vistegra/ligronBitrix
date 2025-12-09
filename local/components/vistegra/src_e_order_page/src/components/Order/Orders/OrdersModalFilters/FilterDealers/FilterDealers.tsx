"use client";

import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils.ts";
import type { ManagedDealer } from "@/types/user";
import { DealerAccordionItem } from "./DealerAccordionItem";

interface FilterDealersProps {
  dealers: ManagedDealer[];                         // Список дилеров для отображения в фильтре
  selectedDealer: string | null;                    // Префикс выбранного дилера (null = "Все дилеры")
  selectedUser: number | null;                      // ID выбранного пользователя (null = "Все пользователи")
  onDealerChange: (prefix: string | null) => void;  // Колбэк при изменении выбора дилера
  onUserChange: (userId: number | null) => void;    // Колбэк при изменении выбора пользователя
}
export function FilterDealers({
                                dealers,
                                selectedDealer,
                                selectedUser,
                                onDealerChange,
                                onUserChange,
                              }: FilterDealersProps) {
  if (dealers.length === 0) return null;

  return (
    <div className="space-y-4">
      <Label className="text-base font-semibold">Дилеры и пользователи</Label>
      <div className="space-y-2">

        {/* Кнопка "Все дилеры" */}
        <div
          className={cn(
            "p-3 rounded-md border transition-all active:scale-[0.98] cursor-pointer",
            !selectedDealer
              ? "border-primary bg-primary/5"
              : "border-transparent bg-muted/30"
          )}
          onClick={() => onDealerChange(null)}
        >
          <span className="text-sm font-medium">Все дилеры</span>
        </div>

        {/* Список дилеров */}
        {dealers.map((d) => (
          <DealerAccordionItem
            key={d.dealer_prefix}
            dealer={d}
            isDealerSelected={selectedDealer === d.dealer_prefix}
            selectedUserId={selectedUser}
            onSelectDealer={() => onDealerChange(d.dealer_prefix)}
            onSelectUser={onUserChange}
          />
        ))}

      </div>
    </div>
  );
}