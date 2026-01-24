import { Checkbox } from "@/components/ui/checkbox";
import { getOriginLabel } from "@/components/Order/Orders/utils";

interface FilterOriginProps {
  values: number[];
  onChange: (val: number[]) => void;
}

export function FilterOrigin({ values, onChange }: FilterOriginProps) {
  const origins = [0, 1, 2]; // 0=Site, 1=1C, 2=Calc

  const handleToggle = (id: number) => {
    const next = values.includes(id)
      ? values.filter((v) => v !== id)
      : [...values, id];
    onChange(next);
  };

  return (
    <div className="grid grid-cols-1 gap-3">
      {origins.map((id) => {
        const { label } = getOriginLabel(id);
        return (
          <div key={id} className="flex items-center space-x-3 py-1">
            <Checkbox
              id={`origin-${id}`}
              checked={values.includes(id)}
              onCheckedChange={() => handleToggle(id)}
              className="h-5 w-5"
            />
            <label
              htmlFor={`origin-${id}`}
              className="text-sm font-medium leading-none w-full py-1 cursor-pointer select-none"
            >
              {label}
            </label>
          </div>
        );
      })}
    </div>
  );
}