"use client";

import { cn } from "@/lib/utils.ts";
import type { ManagedDealer } from "@/types/user";
import { DealerAccordionItem } from "./DealerAccordionItem";

interface FilterDealersProps {
  dealers: ManagedDealer[];
  values: {
    dealer_prefix: string | null;
    dealer_user_id: number | null;
  };
  // Передаем Partial от всего стейта фильтров или конкретно эти два поля
  onChange: (patch: { dealer_prefix: string | null; dealer_user_id: number | null }) => void;
}

export function FilterDealers({
                                dealers,
                                values,
                                onChange,
                              }: FilterDealersProps) {
  if (dealers.length === 0) return null;

  const handleAllDealers = () => {
    onChange({ dealer_prefix: null, dealer_user_id: null });
  };

  const handleDealerChange = (prefix: string) => {
    // При смене дилера всегда сбрасываем конкретного пользователя
    onChange({ dealer_prefix: prefix, dealer_user_id: null });
  };

  const handleUserChange = (userId: number | null) => {
    onChange({ ...values, dealer_user_id: userId });
  };

  return (
    <div className="space-y-2">
      {/* Кнопка "Все дилеры" */}
      <div
        className={cn(
          "p-3 rounded-md border transition-all active:scale-[0.98] cursor-pointer",
          !values.dealer_prefix
            ? "border-primary bg-primary/5"
            : "border-transparent bg-muted/30"
        )}
        onClick={handleAllDealers}
      >
        <span className="text-sm font-medium">Все дилеры</span>
      </div>

      {/* Список дилеров */}
      {dealers.map((d) => (
        <DealerAccordionItem
          key={d.dealer_prefix}
          dealer={d}
          isDealerSelected={values.dealer_prefix === d.dealer_prefix}
          selectedUserId={values.dealer_user_id}
          onSelectDealer={() => handleDealerChange(d.dealer_prefix)}
          onSelectUser={handleUserChange}
        />
      ))}
    </div>
  );
}