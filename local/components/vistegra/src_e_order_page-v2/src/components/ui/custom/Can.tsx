"use client";

import type {ReactNode} from "react";
import type {Order, OrderActionType} from "@/api/orderApi";
import {useOrderPermissions} from "@/hooks/order/useOrderPermissions";

interface CanProps {
  /** Действие из константы ORDER_ACTION */
  action: OrderActionType;
  /** Объект заказа */
  order?: Order | null;
  /** Что отрендерить, если доступ РАЗРЕШЕН */
  children: ReactNode;
  /** Что отрендерить, если доступ ЗАПРЕЩЕН */
  fallback?: ReactNode;
}

export function Can({action, order, children, fallback = null}: CanProps) {
  const permissions = useOrderPermissions(order);

  if (permissions[action]) {
    return <>{children}</>;
  }

  return <>{fallback}</>;
}