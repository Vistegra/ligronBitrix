"use client";

import React from "react";
import {Building2, Info, type LucideIcon, Store, X} from "lucide-react";
import {Button} from "@/components/ui/button";
import {useWorkspace} from "@/hooks/common/useWorkspace.ts";
import {cn} from "@/lib/utils";

interface BaseBadgeProps {
  children: React.ReactNode;
  onReset?: () => void;
  className?: string;
  buttonClassName?: string;
}

function BaseBadge({children, onReset, className, buttonClassName}: BaseBadgeProps) {
  return (
    <div
      className={cn(
        "flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 rounded-full border shadow-sm max-w-full overflow-hidden",
        "animate-in fade-in slide-in-from-top-1 duration-300",
        className
      )}
    >
      {children}

      {onReset && (
        <Button
          variant="ghost"
          size="icon"
          onClick={onReset}
          className={cn("h-5 w-5 ml-1 shrink-0 rounded-full", buttonClassName)}
        >
          <X className="h-3 w-3"/>
        </Button>
      )}
    </div>
  );
}

interface PartialBadgeProps {
  icon: LucideIcon;
  name: string;
  prompt: string;
  onReset: () => void;
}

function PartialBadge({icon: Icon, name, prompt, onReset}: PartialBadgeProps) {
  return (
    <BaseBadge
      onReset={onReset}
      className="bg-slate-50 border-slate-200 text-slate-500 opacity-90"
      buttonClassName="hover:bg-slate-200 text-slate-700"
    >
      <Icon className="h-3.5 w-3.5 text-slate-400 shrink-0"/>
      <span className="text-[11px] font-medium truncate min-w-0">
        <span className="font-bold text-slate-700">{name}</span>
        <span className="hidden sm:inline">: {prompt}</span>
      </span>
    </BaseBadge>
  );
}

// Основной компонент
export function ActiveContextBadge() {
  const {current, inn, salonCode, resetWorkspace} = useWorkspace();

  // Выбран и Дилер, и Салон
  if (inn && salonCode && current) {
    return (
      <BaseBadge
        key="full"
        onReset={resetWorkspace}
        className="bg-green-50 border-green-100"
        buttonClassName="hover:bg-green-200 text-green-700"
      >
        <div className="flex items-center gap-2 sm:gap-3 text-[11px] min-w-0">
          <div className="flex items-center gap-1.5 text-green-900 font-bold min-w-0">
            <Building2 className="h-3.5 w-3.5 text-green-600 shrink-0"/>
            <span className="truncate">{current.dealerName}</span>
          </div>
          <div
            className="flex items-center gap-1.5 pl-2 sm:pl-3 border-l border-green-200 text-green-800 font-medium min-w-0">
            <Store className="h-3.5 w-3.5 text-green-600 shrink-0"/>
            <span className="truncate">{current.salonName}</span>
          </div>
        </div>
      </BaseBadge>
    );
  }

  // Выбран только Салон
  if (!inn && salonCode && current) {
    return (
      <PartialBadge
        key="salon-only"
        icon={Store}
        name={current.salonName || `Салон ${salonCode}`}
        prompt="выберите дилера"
        onReset={resetWorkspace}
      />
    );
  }

  // Выбран только Дилер
  if (inn && !salonCode && current) {
    return (
      <PartialBadge
        key="dealer-only"
        icon={Building2}
        name={current.dealerName || `ИНН ${inn}`}
        prompt="выберите салон"
        onReset={resetWorkspace}
      />
    );
  }

  // Ничего не выбрано
  return (
    <BaseBadge
      key="empty"
      className="bg-slate-50/30 border-slate-100 text-slate-400 opacity-60 hover:opacity-100 transition-opacity"
    >
      <Info className="h-3.5 w-3.5 shrink-0"/>
      <span className="text-[11px] truncate min-w-0">
        <span className="hidden sm:inline">Выберите одного дилера и один салон для создания заказа</span>
        <span className="sm:hidden">Контекст не выбран</span>
      </span>
    </BaseBadge>
  );
}