import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import {cn} from "@/lib/utils";

interface FilterSectionProps {
  title: string;
  value: string;
  children: React.ReactNode;
  isActive?: boolean;    // Группа содержит активные фильтры
  defaultOpen?: boolean; // Раскрыть по умолчанию (даже если пусто)
  className?: string;
}

export function FilterSection({
                                title,
                                value,
                                children,
                                isActive = false,
                                defaultOpen = false,
                                className
                              }: FilterSectionProps) {

  // Определяем состояние раскрытия: если активно ИЛИ задано по умолчанию
  const initialValue = (isActive || defaultOpen) ? value : undefined;

  return (
    <Accordion
      type="multiple" // Меняем на multiple, чтобы несколько активных групп могли быть открыты одновременно
      defaultValue={initialValue ? [value] : []}
      className={cn("border-b border-border/60 last:border-none", className)}
    >
      <AccordionItem value={value} className="border-none">
        <AccordionTrigger
          className={cn(
            "py-2 hover:no-underline font-semibold text-base transition-colors",
            isActive ? "text-green-700" : "text-foreground"
          )}
        >
          <div className="flex items-center gap-2">
            <span>{title}</span>
            {isActive && (
              <span className="h-2 w-2 rounded-full bg-green-600 animate-in fade-in zoom-in"/>
            )}
          </div>
        </AccordionTrigger>
        <AccordionContent className="pt-2 pb-4">
          {children}
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  );
}