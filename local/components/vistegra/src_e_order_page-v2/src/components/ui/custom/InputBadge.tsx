import * as React from "react";
import {Input} from "@/components/ui/input";
import {cn} from "@/lib/utils";

export interface InputBadgeProps extends React.InputHTMLAttributes<HTMLInputElement> {
  badge: string;
}

const InputBadge = React.forwardRef<HTMLInputElement, InputBadgeProps>(
  ({className, badge, ...props}, ref) => {

    const hasValue = !!props.value;

    return (
      <div
        className={cn(
          "group flex flex-1 items-center h-8 overflow-hidden rounded-md border transition-all bg-background",
          "border-input",
          hasValue && "border-primary/60",
          "focus-within:border-primary focus-within:ring-1 focus-within:ring-primary/20",
          className
        )}
      >
        <div className={cn(
          "flex h-full w-7 items-center justify-center text-[10px] font-bold uppercase shrink-0 select-none transition-colors",
          // Базовые стили (когда пусто)
          "bg-muted text-muted-foreground",
          // Стили, когда значение введено (hasValue)
          hasValue && "bg-primary/80 text-primary-foreground",
          // Стили при активном фокусе (имеют приоритет)
          "group-focus-within:bg-primary group-focus-within:text-primary-foreground"
        )}>
          {badge}
        </div>

        <Input
          {...props}
          ref={ref}
          className="border-0 focus-visible:ring-0 focus-visible:ring-offset-0 h-full text-[11px] px-2 bg-transparent w-full"
        />
      </div>
    );
  }
);

InputBadge.displayName = "InputBadge";

export {InputBadge};