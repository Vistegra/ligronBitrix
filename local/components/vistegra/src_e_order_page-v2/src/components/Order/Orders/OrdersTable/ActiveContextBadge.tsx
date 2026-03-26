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
        "flex items-center gap-2 px-3 py-1 rounded-full border shadow-sm",
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
          className={cn("h-5 w-5 ml-1 rounded-full", buttonClassName)}
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
      <Icon className="h-3.5 w-3.5 text-slate-400"/>
      <span className="text-[11px] font-medium">
        <span className="font-bold text-slate-700">{name}:</span> {prompt}
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
        <div className="flex items-center gap-3 text-[11px]">
          <div className="flex items-center gap-1.5 text-green-900 font-bold">
            <Building2 className="h-3.5 w-3.5 text-green-600"/>
            <span className="truncate max-w-[150px]">{current.dealerName}</span>
          </div>
          <div className="flex items-center gap-1.5 pl-3 border-l border-green-200 text-green-800 font-medium">
            <Store className="h-3.5 w-3.5 text-green-600"/>
            <span className="truncate max-w-[150px]">{current.salonName}</span>
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
      <Info className="h-3.5 w-3.5"/>
      <span className="text-[11px]">Выберите одного дилера и один салон для создания заказа</span>
    </BaseBadge>
  );
}