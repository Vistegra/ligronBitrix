"use client";

import { Check } from "lucide-react";
import { cn } from "@/lib/utils.ts";
import type { ManagedDealer } from "@/types/user";
import { UserSelectionRow } from "./UserSelectionRow";

interface DealerAccordionItemProps {
  dealer: ManagedDealer;
  isDealerSelected: boolean;
  selectedUserId: number | null;
  onSelectDealer: () => void;
  onSelectUser: (userId: number | null) => void;
}

export function DealerAccordionItem({
                                      dealer,
                                      isDealerSelected,
                                      selectedUserId,
                                      onSelectDealer,
                                      onSelectUser,
                                    }: DealerAccordionItemProps) {
  return (
    <div
      className={cn(
        "rounded-md border transition-all duration-300 ease-in-out overflow-hidden",
        isDealerSelected
          ? "border-primary bg-primary/5"
          : "border-transparent bg-muted/30"
      )}
    >
      <div
        className="p-3 flex justify-between items-center active:bg-black/5 cursor-pointer select-none"
        onClick={onSelectDealer}
      >
        <div className="flex flex-col">
          <span className="text-sm font-medium">{dealer.name}</span>
          <span className="text-[10px] text-muted-foreground uppercase">
            {dealer.dealer_prefix}
          </span>
        </div>
        {isDealerSelected && !selectedUserId && (
          <Check className="h-4 w-4 text-primary" />
        )}
      </div>

      {isDealerSelected && dealer.users.length > 0 && (
        <div className="bg-background/50 border-t border-primary/10 animate-in slide-in-from-top-2 fade-in duration-300">
          <UserSelectionRow
            label="Все пользователи"
            isSelected={selectedUserId === null}
            onClick={() => onSelectUser(null)}
          />

          {dealer.users.map((u) => (
            <UserSelectionRow
              key={u.id}
              label={u.name}
              isSelected={selectedUserId === u.id}
              onClick={() => onSelectUser(u.id)}
              withIcon={true}
            />
          ))}
        </div>
      )}
    </div>
  );
}