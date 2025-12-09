"use client";

import {User} from "lucide-react";
import {RadioIndicator} from "@/components/ui/custom/RadioIndicator";
import {cn} from "@/lib/utils.ts";

interface UserSelectionRowProps {
  label: string;          // Текст для отображения (имя пользователя или "Все пользователи")
  isSelected: boolean;    // Флаг выбранного состояния
  onClick: () => void;    // Обработчик клика на строку для выбора пользователя
  withIcon?: boolean;     // Флаг отображения иконки пользователя (иконка User слева от текста)
}

export function UserSelectionRow({
                                   label,
                                   isSelected,
                                   onClick,
                                   withIcon = false
                                 }: UserSelectionRowProps) {
  return (
    <div
      className={cn(
        "flex flex-row items-center w-full gap-3 px-4 py-3 border-b border-border/50 last:border-0 active:bg-muted/50 cursor-pointer",
        isSelected && "bg-primary/5"
      )}
      onClick={onClick}
    >
      <RadioIndicator selected={isSelected}/>

      <div className="flex items-center gap-2 min-w-0 flex-1">
        {withIcon && (
          <User
            className={cn(
              "h-4 w-4 shrink-0",
              isSelected ? "text-primary" : "text-muted-foreground"
            )}
          />
        )}

        <span
          className={cn(
            "text-sm truncate",
            isSelected ? "font-medium text-primary" : "text-foreground"
          )}
        >
          {label}
        </span>
      </div>
    </div>
  );
}