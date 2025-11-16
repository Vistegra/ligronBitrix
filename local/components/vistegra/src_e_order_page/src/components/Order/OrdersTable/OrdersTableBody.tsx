"use client";

import {format, fromUnixTime} from "date-fns";
import {ru} from "date-fns/locale";
import {Badge} from "@/components/ui/badge";
import {TableCell, TableRow} from "@/components/ui/table";
import type {Order} from "@/api/orderApi.ts";
import {OrdersTableActions} from "./OrdersTableActions.tsx";

import type {Pagination, VisibleColumns} from "./types";
import {COLUMNS_CONFIG} from "./types";
import StatusBadge from "@/components/Order/StatusBage.tsx";
const getOrderType = (order: Order) => {
  if (order.parent_id !== null) return "individual";
  if (order.children_count && order.children_count > 0) return "complex";
  return "standard";
};

const getOrderTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    individual: "Индивидуальный",
    standard: "Стандартный",
    complex: "Комплексный",
  };
  return labels[type] || type;
};

interface OrdersTableBodyProps {
  orders: Order[];
  pagination: Pagination;
  visibleColumns: VisibleColumns;
}

const columnRenderers: Record<keyof VisibleColumns, (order: Order) => React.ReactNode> = {
  status: (order) => (
    <StatusBadge name={order.status_name} color={order.status_color}/>
  ),
  name: (order) => (
    <div>
      <div className="font-medium">{order.name}</div>
      {order.number && <div className="text-sm text-muted-foreground">№ {order.number}</div>}
    </div>
  ),
  type: (order) => (
    <Badge variant="outline" className="gap-2">
      {getOrderTypeLabel(getOrderType(order))}
    </Badge>
  ),
  fabrication: (order) => order.fabrication ? `${order.fabrication} дн.` : "—",
  ready_date: (order) => order.ready_date ? format(new Date(order.ready_date), "dd.MM.yyyy") : "—",
  created_at: (order) => order.created_at
    ? format(fromUnixTime(order.created_at), "dd.MM.yyyy HH:mm", { locale: ru })
    : "—",
};

export function OrdersTableBody({ orders, pagination, visibleColumns }: OrdersTableBodyProps) {
  return (
    <>
      {orders.map((order, index) => (
        <TableRow key={order.id}>
          <TableCell className="font-medium">
            {pagination.offset + index + 1}
          </TableCell>

          {COLUMNS_CONFIG.map((column) =>
              visibleColumns[column.key] && (
                <TableCell key={column.key}>
                  {columnRenderers[column.key](order)}
                </TableCell>
              )
          )}
          <TableCell className="w-8 p-1">
            <OrdersTableActions orderId={order.id} />
          </TableCell>
        </TableRow>
      ))}
    </>
  );
}