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
  defaultOpen?: boolean;
  className?: string;
}

export function FilterSection({title, value, children, defaultOpen = true, className}: FilterSectionProps) {
  return (
    <Accordion
      type="single"
      collapsible
      defaultValue={defaultOpen ? value : undefined}
      className={cn("border-none", className)}
    >
      <AccordionItem value={value} className="border-none">
        <AccordionTrigger className="py-2 hover:no-underline font-semibold text-base">
          {title}
        </AccordionTrigger>
        <AccordionContent className="pt-2 pb-4">
          {children}
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  );
}