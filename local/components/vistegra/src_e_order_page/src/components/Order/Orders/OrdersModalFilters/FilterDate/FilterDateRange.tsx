import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

interface FilterDateRangeProps {
  label: string;
  fromValue: string;
  toValue: string;
  onFromChange: (val: string) => void;
  onToChange: (val: string) => void;
}

export function FilterDateRange({
                                  label,
                                  fromValue,
                                  toValue,
                                  onFromChange,
                                  onToChange
                                }: FilterDateRangeProps) {
  return (
    <div className="space-y-3">
      <Label className="text-muted-foreground text-[10px] uppercase tracking-wider font-bold">
        {label}
      </Label>
      <div className="grid grid-cols-2 gap-2">
        <div className="space-y-1">
          <span className="text-[10px] text-muted-foreground pl-1">От</span>
          <Input
            type="date"
            value={fromValue}
            onChange={(e) => onFromChange(e.target.value)}
            className="h-9 text-xs"
          />
        </div>
        <div className="space-y-1">
          <span className="text-[10px] text-muted-foreground pl-1">До</span>
          <Input
            type="date"
            value={toValue}
            onChange={(e) => onToChange(e.target.value)}
            className="h-9 text-xs"
          />
        </div>
      </div>
    </div>
  );
}