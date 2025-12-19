import {cn} from "@/lib/utils.ts";

interface RadioIndicatorProps {
  selected: boolean;
  className?: string;
}

export function RadioIndicator({selected, className}: RadioIndicatorProps) {
  return (
    <div
      className={cn(
        "w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-colors",
        selected
          ? "border-primary bg-primary"
          : "border-muted-foreground",
        className
      )}
    >
      {selected && <div className="w-1.5 h-1.5 bg-white rounded-full"/>}
    </div>
  );
}