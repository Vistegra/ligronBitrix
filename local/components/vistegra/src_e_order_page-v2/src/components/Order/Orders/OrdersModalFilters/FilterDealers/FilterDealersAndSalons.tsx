"use client";

import type {DealerNode} from "@/types/user";
import {DealerAccordionItem} from "./DealerAccordionItem";

interface FilterDealersAndSalonsProps {
  hierarchy: DealerNode[];
  values: {
    inn_dealer: string[];
    salon_code: string[];
  };
  onChange: (patch: Partial<FilterDealersAndSalonsProps["values"]>) => void;
}

export function FilterDealersAndSalons({
                                         hierarchy,
                                         values,
                                         onChange,
                                       }: FilterDealersAndSalonsProps) {
  if (hierarchy.length === 0) return null;

  const handleToggleInn = (inn: string) => {
    const next = values.inn_dealer.includes(inn)
      ? values.inn_dealer.filter(i => i !== inn)
      : [...values.inn_dealer, inn];
    onChange({inn_dealer: next});
  };

  const handleToggleSalon = (code: string) => {
    const next = values.salon_code.includes(code)
      ? values.salon_code.filter(c => c !== code)
      : [...values.salon_code, code];
    onChange({salon_code: next});
  };

  return (
    <div className="space-y-1">
      {hierarchy.map((d) => (
        <DealerAccordionItem
          key={d.inn}
          dealer={d}
          selectedInns={values.inn_dealer}
          selectedSalons={values.salon_code}
          onToggleInn={handleToggleInn}
          onToggleSalon={handleToggleSalon}
        />
      ))}
    </div>
  );
}