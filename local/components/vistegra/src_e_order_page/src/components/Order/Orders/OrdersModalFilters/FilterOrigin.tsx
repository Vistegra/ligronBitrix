import {Label} from "@/components/ui/label";
import {Checkbox} from "@/components/ui/checkbox";
import {getOriginLabel} from "@/components/Order/Orders/utils";

interface FilterOriginProps {
  selectedOrigins: number[];
  onToggle: (id: number) => void;
}

export function FilterOrigin({selectedOrigins, onToggle}: FilterOriginProps) {
  const origins = [0, 1, 2]; // 0=Site, 1=1C, 2=Calc

  return (
    <div className="space-y-4">
      <Label className="text-base font-semibold">Источник заказа</Label>
      <div className="grid grid-cols-1 gap-3">
        {origins.map((id) => {
          const {label} = getOriginLabel(id);
          return (
            <div key={id} className="flex items-center space-x-3 py-1">
              <Checkbox
                id={`origin-${id}`}
                checked={selectedOrigins.includes(id)}
                onCheckedChange={() => onToggle(id)}
                className="h-5 w-5"
              />
              <label htmlFor={`origin-${id}`} className="text-sm font-medium leading-none w-full py-1 cursor-pointer">
                {label}
              </label>
            </div>
          );
        })}
      </div>
    </div>
  );
}