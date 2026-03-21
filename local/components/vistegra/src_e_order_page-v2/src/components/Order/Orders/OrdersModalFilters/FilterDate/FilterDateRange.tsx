import {Label} from "@/components/ui/label";
import {InputBadge} from "@/components/ui/custom/InputBadge.tsx";


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
                                  onToChange,
                                }: FilterDateRangeProps) {

  const handleFromChange = (val: string) => {
    onFromChange(val);
    if (toValue && val > toValue) onToChange("");
  };

  const handleToChange = (val: string) => {
    if (fromValue && val < fromValue) {
      onToChange(fromValue);
    } else {
      onToChange(val);
    }
  };

  return (
    <div className="flex flex-col gap-1.5 max-w-[340px]">
      <Label className="text-[11px] font-semibold text-muted-foreground/80 uppercase tracking-wider ml-1">
        {label}
      </Label>

      <div className="flex items-center gap-1.5">
        <InputBadge
          badge="С"
          type="date"
          value={fromValue}
          max={toValue || undefined}
          onChange={(e) => handleFromChange(e.target.value)}
        />

        <div className="text-muted-foreground/30 font-light"> - </div>

        <InputBadge
          badge="По"
          type="date"
          value={toValue}
          min={fromValue || undefined}
          onChange={(e) => handleToChange(e.target.value)}
        />
      </div>
    </div>
  );
}