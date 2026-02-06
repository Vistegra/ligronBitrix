"use client";

import {Badge} from "@/components/ui/badge";
import {TableCell, TableRow} from "@/components/ui/table";
import type {Order} from "@/api/orderApi.ts";
import {OrdersTableActions} from "./OrdersTableActions";
import {useAuthStore} from "@/store/authStore.ts";

import {Link} from "react-router-dom";

import {COLUMN_DEFINITIONS, type ColumnKey, type Pagination, type PartVisibleColumns} from "../types.ts";

import StatusBadge from "@/components/Order/StatusBage";
import {
  buildOrderLink,
  formatDateTime,
  getDealerNameByOrder,
  getOrderType,
  getOrderTypeLabel, getOriginLabel, getUserNameByOrder
} from "@/components/Order/Orders/utils.ts";


interface OrdersTableBodyProps {
  orders: Order[];
  pagination: Pagination;
  visibleColumns: PartVisibleColumns;
  basePage: string;
}

export function OrdersTableBody({orders, pagination, visibleColumns, basePage}: OrdersTableBodyProps) {
  const {user} = useAuthStore();

  const columnRenderers: Record<keyof PartVisibleColumns, (order: Order) => React.ReactNode> = {
    /*id: (order) => <span className="font-medium">{order.id}</span>,*/

    number: (order) => order.number ? <span className="font-medium">{order.number}</span> :
      <span className="text-muted-foreground">—</span>,

    status: (order) => (
      <StatusBadge name={order.status_name} color={order.status_color}/>
    ),

    name: (order) => (
      <Link
        to={buildOrderLink(basePage, order)}
        className="font-medium hover:text-primary hover:underline transition-colors block truncate"
      >
        {order.name}
      </Link>
    ),

    type: (order) => (
      <Badge variant="outline" className="gap-2">
        {getOrderTypeLabel(getOrderType(order))}
      </Badge>
    ),

    origin: (order) => {
      const { label, color } = getOriginLabel(order.origin_type);
      return (
        <Badge variant="outline" className={`font-normal ${color}`}>
          {label}
        </Badge>
      );
    },

    dealer: (order) => (
      <div className="text-sm">{getDealerNameByOrder(user, order) || "—"}</div>
    ),

    user: (order) => (
      <div className="text-sm text-muted-foreground">{getUserNameByOrder(user, order)}</div>
    ),

    production_time: (order) => (order.production_time ? `${order.production_time} дн.` : "—"),

    ready_date: (order) =>
      order.ready_date ?? "—",

    percent_payment: (order) => (order.percent_payment !== null ? `${order.percent_payment}%` : "—"),

    created_at: (order) => formatDateTime(order.created_at),

    updated_at: (order) => formatDateTime(order.updated_at),
  };

  return (
    <>
      {orders.map((order, index) => (
        <TableRow key={order.id}>
          <TableCell className="w-12 text-center text-muted-foreground">
            {pagination.offset + index + 1}
          </TableCell>

          {(Object.keys(COLUMN_DEFINITIONS) as ColumnKey[]).map((key) => {
            // Если ключа нет в visibleColumns (который отфильтрован хуком) — не рисуем
            if (!visibleColumns[key]) return null;

            const column = COLUMN_DEFINITIONS[key];
            return (
              <TableCell key={key} className={column.width}>
                {columnRenderers[key](order)}
              </TableCell>
            );
          })}

          <TableCell className="w-8 p-1">
            <OrdersTableActions order={order} basePage={basePage}/>
          </TableCell>
        </TableRow>
      ))}
    </>
  );
}