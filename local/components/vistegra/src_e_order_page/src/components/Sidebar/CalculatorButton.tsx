import {Calculator, Loader2} from "lucide-react";
import {
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";
import {ConfirmPopover} from "@/components/ui/popups/ConfirmPopover.tsx";
import {useCalculatorRedirect} from "@/hooks/auth/useCalculatorRedirect";

export function CalculatorButton() {
  const {isLoading, onConfirm} = useCalculatorRedirect();

  return (
    <SidebarMenuItem>
      <ConfirmPopover
        title="Перейти в калькулятор?"
        description="Вы будете перенаправлены во внешнее приложение калькулятора Лигрон. Текущая страница останется открытой."
        confirmText={isLoading ? "Открытие..." : "Перейти"}
        cancelText="Отмена"
        confirmVariant="default"
        icon={<Calculator className="h-8 w-8 text-primary"/>}
        onConfirm={onConfirm}
        side="bottom"
        align="start"
        sideOffset={4}
        alignOffset={0}
      >
        <SidebarMenuButton
          asChild
          tooltip="Перейти в калькулятор"
          className="w-full justify-start hover:bg-accent hover:text-accent-foreground cursor-pointer"
          disabled={isLoading}
        >
          <div className="flex items-center w-full">
            {isLoading ? (
              <Loader2 className="h-4 w-4 animate-spin mr-2"/>
            ) : (
              <Calculator className="h-4 w-4 mr-2"/>
            )}
            <span>Калькулятор</span>
          </div>
        </SidebarMenuButton>
      </ConfirmPopover>
    </SidebarMenuItem>
  );
}