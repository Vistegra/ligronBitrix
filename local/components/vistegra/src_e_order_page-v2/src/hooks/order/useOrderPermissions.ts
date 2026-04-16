import { useMemo } from "react";
import {type Order, ORDER_ACTION, type OrderPermissions} from "@/api/orderApi";

// если бэкенд не прислал права
const DEFAULT_PERMISSIONS: OrderPermissions = {
  [ORDER_ACTION.OPEN_CALC]: false,
  [ORDER_ACTION.UPDATE]: false,
  [ORDER_ACTION.DELETE]: false,
  [ORDER_ACTION.CHANGE_STATUS]: false,
  [ORDER_ACTION.SEND_TO_1C]: false,
};

export function useOrderPermissions(order?: Order | null) {
  return useMemo(() => {
    if (!order || !order._permissions) {
      return DEFAULT_PERMISSIONS;
    }
    return order._permissions;
  }, [order]);
}