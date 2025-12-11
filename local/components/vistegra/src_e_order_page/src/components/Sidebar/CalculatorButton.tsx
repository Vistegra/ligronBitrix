import {Calculator, Loader2} from "lucide-react";
import {SidebarMenuButton, SidebarMenuItem} from "@/components/ui/sidebar";
import {useCalculatorRedirect} from "@/hooks/auth/useCalculatorRedirect";

export function CalculatorButton() {
  const {openCalculator, isLoading} = useCalculatorRedirect();

  return (
    <SidebarMenuItem>
      <SidebarMenuButton
        onClick={openCalculator}
        disabled={isLoading}
        tooltip="Перейти в калькулятор"
        className="text-green-700 hover:text-green-800 hover:bg-green-50"
      >
        {isLoading ? (
          <Loader2 className="h-4 w-4 animate-spin"/>
        ) : (
          <Calculator className="h-4 w-4"/>
        )}
        <span>Калькулятор</span>
      </SidebarMenuButton>
    </SidebarMenuItem>
  );
}