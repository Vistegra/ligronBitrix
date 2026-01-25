import { FilterDateRange } from "./FilterDateRange";

interface FilterDateSectionProps {
  values: {
    created_at_from: string;
    created_at_to: string;
    updated_at_from: string;
    updated_at_to: string;
  };
  onChange: (patch: Partial<FilterDateSectionProps["values"]>) => void;
}

export function FilterDateSection({ values, onChange }: FilterDateSectionProps) {
  return (
    <div className="space-y-4 py-1">
      <FilterDateRange
        label="Создание заказа"
        fromValue={values.created_at_from}
        toValue={values.created_at_to}
        onFromChange={(val) => onChange({ created_at_from: val })}
        onToChange={(val) => onChange({ created_at_to: val })}
      />

      <FilterDateRange
        label="Обновление заказа"
        fromValue={values.updated_at_from}
        toValue={values.updated_at_to}
        onFromChange={(val) => onChange({ updated_at_from: val })}
        onToChange={(val) => onChange({ updated_at_to: val })}
      />
    </div>
  );
}