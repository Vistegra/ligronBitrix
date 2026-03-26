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
    const isSelecting = !values.inn_dealer.includes(inn);

    const nextInns = isSelecting
      ? [...values.inn_dealer, inn]
      : values.inn_dealer.filter(i => i !== inn);

    let nextSalons = [...values.salon_code];
    if (!isSelecting) {
      const dealer = hierarchy.find(d => d.inn === inn);
      if (dealer) {
        const dealerSalonCodes = dealer.salons.map(s => s.salon_code);
        nextSalons = nextSalons.filter(code => !dealerSalonCodes.includes(code));
      }
    }

    onChange({
      inn_dealer: nextInns,
      salon_code: nextSalons
    });
  };

  const handleToggleSalon = (code: string) => {
    const isSelecting = !values.salon_code.includes(code);

    const nextSalons = isSelecting
      ? [...values.salon_code, code]
      : values.salon_code.filter(c => c !== code);

    let nextInns = [...values.inn_dealer];
    if (isSelecting) {
      const parentDealer = hierarchy.find(d => d.salons.some(s => s.salon_code === code));
      if (parentDealer && !nextInns.includes(parentDealer.inn)) {
        nextInns.push(parentDealer.inn);
      }
    }

    onChange({
      inn_dealer: nextInns,
      salon_code: nextSalons
    });
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