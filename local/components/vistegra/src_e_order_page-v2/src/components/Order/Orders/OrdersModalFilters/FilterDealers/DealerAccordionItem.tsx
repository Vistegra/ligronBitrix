"use client";

import {useState} from "react";
import {Checkbox} from "@/components/ui/checkbox";
import {cn} from "@/lib/utils.ts";
import {SalonSelectionRow} from "./SalonSelectionRow";
import type {DealerNode} from "@/types/user";
import {Collapsible, CollapsibleContent, CollapsibleTrigger} from "@/components/ui/collapsible";
import {Building2, ChevronDown} from "lucide-react";

interface DealerAccordionItemProps {
  dealer: DealerNode;
  selectedInns: string[];
  selectedSalons: string[];
  onToggleInn: (inn: string) => void;
  onToggleSalon: (code: string) => void;
}

export function DealerAccordionItem({
                                      dealer,
                                      selectedInns,
                                      selectedSalons,
                                      onToggleInn,
                                      onToggleSalon,
                                    }: DealerAccordionItemProps) {

  const isInnSelected = selectedInns.includes(dealer.inn);

  // Проверяем, выбран ли хоть один салон этого дилера
  const hasActiveSalons = dealer.salons.some(s => selectedSalons.includes(s.salon_code));

  // по умолчанию открыто, если есть выбранные салоны
  const [isOpen, setIsOpen] = useState(hasActiveSalons);

  return (
    <Collapsible
      open={isOpen}
      onOpenChange={setIsOpen}
      className={cn(
        "rounded-xl border mb-3 overflow-hidden transition-all duration-200",
        isInnSelected
          ? "border-primary/40 shadow-sm bg-primary/[0.02]"
          : "border-border shadow-none"
      )}
    >
      {/* Шапка Дилера */}
      <div className="flex items-center p-3 gap-3">
        <Checkbox
          checked={isInnSelected}
          onCheckedChange={() => onToggleInn(dealer.inn)}
          className="h-5 w-5"
        />

        <CollapsibleTrigger asChild>
          <div className="flex flex-1 items-center justify-between cursor-pointer group">
            <div className="flex items-center gap-3 min-w-0">
              <div className={cn(
                "p-2 rounded-lg transition-colors",
                isInnSelected ? "bg-primary/10 text-primary" : "bg-muted text-muted-foreground"
              )}>
                <Building2 className="h-5 w-5"/>
              </div>
              <div className="flex flex-col min-w-0">
                <span className="text-sm font-bold text-foreground leading-tight truncate">
                  {dealer.name}
                </span>
                <span className="text-[10px] text-muted-foreground font-mono">
                  ИНН {dealer.inn}
                </span>
              </div>
            </div>

            <ChevronDown className={cn(
              "h-4 w-4 text-muted-foreground transition-transform duration-200",
              isOpen && "rotate-180"
            )}/>
          </div>
        </CollapsibleTrigger>
      </div>

      {/* Список салонов */}
      <CollapsibleContent className="bg-background/50 border-t border-border/50 animate-in fade-in slide-in-from-top-1">
        <div className="flex flex-col">
          {dealer.salons.map((s) => (
            <SalonSelectionRow
              key={s.salon_code}
              label={s.name}
              code={s.salon_code}
              isSelected={selectedSalons.includes(s.salon_code)}
              onToggle={onToggleSalon}
            />
          ))}
        </div>
      </CollapsibleContent>
    </Collapsible>
  );
}