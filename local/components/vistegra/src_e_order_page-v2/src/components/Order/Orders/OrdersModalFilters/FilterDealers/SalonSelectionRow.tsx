"use client";

import {Store} from "lucide-react";
import {Checkbox} from "@/components/ui/checkbox";
import {cn} from "@/lib/utils.ts";

interface SalonSelectionRowProps {
  label: string;
  code: string;
  isSelected: boolean;
  onToggle: (code: string) => void;
}

export function SalonSelectionRow({
                                    label,
                                    code,
                                    isSelected,
                                    onToggle
                                  }: SalonSelectionRowProps) {
  return (
    <div
      className={cn(
        "flex flex-row items-center w-full gap-3 px-4 py-3 border-b border-border/50 last:border-0 active:bg-muted/50 cursor-pointer",
        isSelected && "bg-primary/5"
      )}
      onClick={() => onToggle(code)}
    >
      <Checkbox
        checked={isSelected}
        onCheckedChange={() => onToggle(code)}
        onClick={(e) => e.stopPropagation()}
      />

      <div className="flex items-center gap-2 min-w-0 flex-1">
        <Store
          className={cn(
            "h-4 w-4 shrink-0",
            isSelected ? "text-primary" : "text-muted-foreground"
          )}
        />

        <div className="flex flex-col min-w-0">
          <span
            className={cn(
              "text-sm truncate",
              isSelected ? "font-medium text-primary" : "text-foreground"
            )}
          >
            {label}
          </span>
          <span className="text-[10px] text-muted-foreground uppercase leading-none">
            {code}
          </span>
        </div>
      </div>
    </div>
  );

}