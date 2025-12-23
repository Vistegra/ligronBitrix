"use client";

import {Calculator, Loader2} from "lucide-react";
import {Button} from "@/components/ui/button";
import {ConfirmPopover} from "@/components/ui/popups/ConfirmPopover";
import {useCalculatorRedirect} from "@/hooks/auth/useCalculatorRedirect";

interface OpenInCalculatorButtonProps {
  orderNumber: string | null;
  className?: string;
}

export function OpenInCalculatorButton({orderNumber, className}: OpenInCalculatorButtonProps) {
  const {isLoading, onConfirm} = useCalculatorRedirect();

  if (!orderNumber) return null;

  return (
    <ConfirmPopover
      title="Открыть заказ в калькуляторе?"
      description="Вы будете перенаправлены во внешнее приложение калькулятора Лигрон. Текущая страница останется открытой."
      confirmText={isLoading ? "Открытие..." : "Перейти"}
      cancelText="Отмена"
      confirmVariant="default"
      icon={<Calculator className="h-8 w-8 text-primary"/>}
      onConfirm={() => onConfirm(orderNumber)}
      side="top"
      align="end"
    >
      <Button
        type="button"
        disabled={isLoading}
        className={`w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white shadow-sm ${className}`}
      >
        {isLoading ? (
          <Loader2 className="mr-2 h-4 w-4 animate-spin"/>
        ) : (
          <Calculator className="mr-2 h-4 w-4"/>
        )}
        <span className="font-medium">Открыть в калькуляторе</span>
      </Button>
    </ConfirmPopover>
  );
}