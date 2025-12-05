"use client";

import {format, fromUnixTime} from "date-fns";
import {ru} from "date-fns/locale";
import {Badge} from "@/components/ui/badge";
import {TableCell, TableRow} from "@/components/ui/table";
import type {Order} from "@/api/orderApi.ts";
import {OrdersTableActions} from "./OrdersTableActions.tsx";
import {useAuthStore} from "@/store/authStore";

import {Link, useSearchParams} from "react-router-dom";

import {COLUMN_DEFINITIONS, type ColumnKey, type Pagination, type PartVisibleColumns} from "./types";

import StatusBadge from "@/components/Order/StatusBage.tsx";
import type {ManagerDetailed} from "@/types/user";

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

const formatDate = (timestamp?: number) =>
  timestamp
    ? format(fromUnixTime(timestamp), "dd.MM.yyyy HH:mm", {locale: ru})
    : "—";

interface OrdersTableBodyProps {
  orders: Order[];
  pagination: Pagination;
  visibleColumns: PartVisibleColumns;
  basePage: string;
}

export function OrdersTableBody({orders, pagination, visibleColumns, basePage}: OrdersTableBodyProps) {
  const {user} = useAuthStore();
  // Получаем текущие параметры URL
  const [searchParams] = useSearchParams();

  const managedDealers = (user?.detailed as ManagerDetailed)?.managed_dealers || [];

  const getDealerName = (prefix: string | null) => {
    if (!prefix) return "—";
    const dealer = managedDealers.find((d: { dealer_prefix: string; }) => d.dealer_prefix === prefix);
    return dealer ? dealer.name : prefix;
  };

  const getUserName = (prefix: string | null, userId: number | null) => {
    if (!prefix || !userId) return "—";
    const dealer = managedDealers.find((d) => d.dealer_prefix === prefix);
    if (!dealer) return userId;
    const appUser = dealer.users.find((u) => u.id === userId);
    return appUser ? appUser.name : userId;
  };

  // Хелпер для создания ссылки на заказ с сохранением контекста
  const createOrderLink = (order: Order) => {
    const newParams = new URLSearchParams(searchParams);

    // Добавляем контекст конкретного заказа для хлебных крошек
    if (order.dealer_prefix) {
      newParams.set("dealer_prefix", order.dealer_prefix);
    }
    if (order.dealer_user_id) {
      newParams.set("dealer_user_id", String(order.dealer_user_id));
    }

    return `${basePage}/${order.id}?${newParams.toString()}`;
  };

  const columnRenderers: Record<keyof PartVisibleColumns, (order: Order) => React.ReactNode> = {
    id: (order) => <span className="font-medium">{order.id}</span>,

    number: (order) => order.number ? <span className="font-medium">{order.number}</span> :
      <span className="text-muted-foreground">—</span>,

    status: (order) => (
      <StatusBadge name={order.status_name} color={order.status_color}/>
    ),

    name: (order) => (
      <Link
        to={createOrderLink(order)}
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

    dealer: (order) => (
      <div className="text-sm">{getDealerName(order.dealer_prefix)}</div>
    ),

    user: (order) => (
      <div className="text-sm text-muted-foreground">{getUserName(order.dealer_prefix, order.dealer_user_id)}</div>
    ),

    production_time: (order) => (order.production_time ? `${order.production_time} дн.` : "—"),
    ready_date: (order) =>
      order.ready_date ? format(new Date(order.ready_date), "dd.MM.yyyy") : "—",
    percent_payment: (order) => (order.percent_payment !== null ? `${order.percent_payment}%` : "—"),
    created_at: (order) => formatDate(order.created_at),
    updated_at: (order) => formatDate(order.updated_at),
  };

  return (
    <>
      {orders.map((order, index) => (
        <TableRow key={order.id}>
          <TableCell className="font-medium text-muted-foreground w-12 text-center">
            {pagination.offset + index + 1}
          </TableCell>

          {Object.entries(visibleColumns).map(([key, isVisible]) => {
            if (!isVisible) return null;

            const column = COLUMN_DEFINITIONS[key as ColumnKey];
            return (
              <TableCell key={key} className={column.width}>
                {columnRenderers[key as ColumnKey](order)}
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