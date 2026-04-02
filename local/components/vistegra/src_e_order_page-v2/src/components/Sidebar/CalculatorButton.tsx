"use client";

import { useEffect } from "react";
import { useSearchParams } from "react-router-dom";
import { Calculator, Loader2, Building2, Store, AlertCircle } from "lucide-react";
import { SidebarMenuButton, SidebarMenuItem } from "@/components/ui/sidebar";
import { ConfirmPopover } from "@/components/ui/popups/ConfirmPopover";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Button } from "@/components/ui/button";

import { useCalculatorRedirect } from "@/hooks/auth/useCalculatorRedirect";
import { useWorkspace } from "@/hooks/common/useWorkspace";
import { useAuthStore } from "@/store/authStore";
import { useContextStore } from "@/store/contextStore";
import type { DealerUser } from "@/types/user";

interface CalculatorButtonProps {
  variant?: "sidebar" | "button";
  orderNumber?: string | null;
  className?: string;
}

export function CalculatorButton({ variant = "sidebar", orderNumber = null, className }: CalculatorButtonProps) {
  const { isLoading, onConfirm } = useCalculatorRedirect();
  const { current, inn, salonCode } = useWorkspace();
  const { user } = useAuthStore();
  const [searchParams] = useSearchParams();

  const isDealerUser = user?.provider === 'dealer';
  const dealerUser = isDealerUser ? (user as DealerUser) : null;
  // @ts-ignore
  const isGlobalManager = user?.role === 'OML' || user?.role === 'GOD_LIGRON' || user?.role === 'GOD_DEALER';

  // 1. Автоматическая установка контекста из URL
  useEffect(() => {
    const urlInn = searchParams.get('inn_dealer');
    const urlSalon = searchParams.get('salon_code');

    if (urlInn && urlSalon && (urlInn !== inn || urlSalon !== salonCode)) {

      if (isGlobalManager) {
        // Глобальным ролям доверяем URL безоговорочно
        useContextStore.getState()._set(urlInn, urlSalon);
      } else {
        // Обычным пользователям сверяем URL с их облаком доступов
        const availableInns = user?.detailed?.available_inns || [];
        const availableSalons = user?.detailed?.available_salons || [];

        if (availableInns.includes(urlInn) && availableSalons.includes(urlSalon)) {
          useContextStore.getState()._set(urlInn, urlSalon);
        }
      }
    }
  }, [searchParams, isGlobalManager, inn, salonCode, user]);

  // 2. Проверка наличия контекста
  const hasContext =
    (!!inn && !!salonCode) ||
    (isDealerUser && !!dealerUser?.inn_dealer && !!dealerUser?.salon_code);

  const displayDealer = (inn && current?.dealerName)
    ? current.dealerName
    : dealerUser?.detailed?.dealer_name || (dealerUser?.inn_dealer ? `ИНН: ${dealerUser.inn_dealer}` : 'Не выбран');

  const displaySalon = (salonCode && current?.salonName)
    ? current.salonName
    : dealerUser?.detailed?.salon_name || (dealerUser?.salon_code ? `Код: ${dealerUser.salon_code}` : 'Не выбран');


  // 3. Рендер состояния "Нет контекста" (Только для сайдбара)
  if (!hasContext) {
    if (variant === "button") return null;

    return (
      <SidebarMenuItem>
        <Popover>
          <PopoverTrigger asChild>
            <SidebarMenuButton tooltip="Перейти в калькулятор" className="w-full justify-start cursor-pointer">
              <Calculator className="h-4 w-4 mr-2" />
              <span>Калькулятор</span>
            </SidebarMenuButton>
          </PopoverTrigger>
          <PopoverContent side="right" align="end" sideOffset={8} className="w-80 p-4 shadow-lg border-amber-200 bg-amber-50/50">
            <div className="flex gap-3">
              <AlertCircle className="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
              <div className="space-y-1.5">
                <h4 className="text-sm font-semibold text-amber-900 leading-none">Выберите организацию</h4>
                <p className="text-xs text-amber-800/80 leading-relaxed">
                  {isDealerUser
                    ? "Для перехода в калькулятор сначала выберите салон в левом меню."
                    : "Чтобы перейти в калькулятор, выберите дилера и салон в левом меню. Вы зайдете от их лица."}
                </p>
              </div>
            </div>
          </PopoverContent>
        </Popover>
      </SidebarMenuItem>
    );
  }

  // 4. Описание для окна подтверждения
  const popoverDescription = (
    <div className="flex flex-col gap-3 mt-1">
      <p className="text-xs text-muted-foreground leading-relaxed">
        Вы будете перенаправлены во внешнее приложение калькулятора Лигрон. Текущая страница останется открытой.
      </p>

      <div className="bg-muted/50 p-3 rounded-lg border border-border/50 space-y-2.5">
        <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
          Вы входите от лица:
        </p>
        <div className="flex items-center gap-2.5 text-xs font-medium text-foreground">
          <Building2 className="h-4 w-4 text-green-600 shrink-0" />
          <span className="truncate">{displayDealer}</span>
        </div>
        <div className="flex items-center gap-2.5 text-xs font-medium text-foreground">
          <Store className="h-4 w-4 text-green-600 shrink-0" />
          <span className="truncate">{displaySalon}</span>
        </div>
      </div>
    </div>
  );

  // 5. Рендер кнопки в зависимости от variant
  const buttonContent = isLoading ? <Loader2 className="h-4 w-4 animate-spin mr-2"/> : <Calculator className="h-4 w-4 mr-2"/>;
  const buttonText = variant === "sidebar" ? "Калькулятор" : "Открыть в калькуляторе";

  const triggerButton = variant === "sidebar" ? (
    <SidebarMenuButton asChild tooltip="Перейти в калькулятор" disabled={isLoading} className="w-full justify-start cursor-pointer">
      <div className="flex items-center w-full">{buttonContent}<span>{buttonText}</span></div>
    </SidebarMenuButton>
  ) : (
    <Button type="button" disabled={isLoading} className={`w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white shadow-sm ${className}`}>
      {buttonContent}<span className="font-medium">{buttonText}</span>
    </Button>
  );

  const popover = (
    <ConfirmPopover
      title="Перейти в калькулятор?"
      description={popoverDescription as any}
      confirmText={isLoading ? "Открытие..." : "Перейти"}
      cancelText="Отмена"
      confirmVariant="default"
      icon={<Calculator className="h-8 w-8 text-primary"/>}
      onConfirm={() => onConfirm(orderNumber)}
      side={variant === "sidebar" ? "right" : "top"}
      align="end"
      sideOffset={8}
    >
      {triggerButton}
    </ConfirmPopover>
  );

  return variant === "sidebar" ? <SidebarMenuItem>{popover}</SidebarMenuItem> : popover;
}