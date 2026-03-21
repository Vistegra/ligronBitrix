import {useEffect, useState, type ChangeEvent} from "react";
import {SearchIcon, XIcon} from "lucide-react";
import {Input} from "@/components/ui/input";
import {useDebounce} from "@/hooks/useDebounce";
import {Button} from "@/components/ui/button";
import {cn} from "@/lib/utils";
import {useOrderUrlState} from "@/hooks/order/useOrderUrlState";

interface OrdersSearchProps {
  className?: string;
  placeholder?: string;
}

export function OrdersSearch({
                               className,
                               placeholder = "Поиск..."
                             }: OrdersSearchProps) {
  const {activeFilters, updateFilters} = useOrderUrlState();
  const [value, setValue] = useState(activeFilters.search);

  // Синхронизация, если URL изменился извне
  useEffect(() => {
    setValue(activeFilters.search);
  }, [activeFilters.search]);

  // Дебаунс обновления URL
  const updateUrl = useDebounce((newValue: string) => {
    updateFilters({search: newValue});
  }, 800);

  const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setValue(newValue);
    updateUrl(newValue);
  };

  const handleClear = () => {
    setValue("");
    updateFilters({search: ""});
  };

  return (
    <div className={cn("relative", className)}>
      <SearchIcon
        className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none"
      />
      <Input
        value={value}
        onChange={handleChange}
        placeholder={placeholder}
        className="pl-9 pr-8 bg-background h-9 text-sm"
      />
      {value && (
        <Button
          variant="ghost"
          size="icon"
          onClick={handleClear}
          className="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7 text-muted-foreground hover:text-foreground"
          type="button"
        >
          <XIcon className="h-4 w-4"/>
          <span className="sr-only">Очистить</span>
        </Button>
      )}
    </div>
  );
}