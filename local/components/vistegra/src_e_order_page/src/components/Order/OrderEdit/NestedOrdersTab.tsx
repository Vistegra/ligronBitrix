"use client";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

import { Skeleton } from "@/components/ui/skeleton";
import { format, fromUnixTime } from "date-fns";
import { ru } from "date-fns/locale";
import { Link } from "react-router-dom";

import type { Order } from "@/api/orderApi";
import StatusBadge from "@/components/Order/StatusBage.tsx";

type Props = {
  orders: Order[];
  loading: boolean;
};

export function NestedOrdersTab({ orders, loading }: Props) {
  if (loading) {
    return (
      <div className="space-y-2">
        {Array.from({ length: 3 }).map((_, i) => (
          <div key={i} className="flex items-center gap-4 p-2">
            <Skeleton className="h-4 w-8" />
            <Skeleton className="h-4 flex-1" />
            <Skeleton className="h-6 w-20 rounded-full" />
            <Skeleton className="h-4 w-32" />
          </div>
        ))}
      </div>
    );
  }

  if (orders.length === 0) {
    return (
      <p className="text-center text-muted-foreground py-4">
        Нет вложенных заказов
      </p>
    );
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead className="w-12">№</TableHead>
          <TableHead>Название</TableHead>
          <TableHead className="w-32">Статус</TableHead>
          <TableHead className="w-40">Создан</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {orders.map((order, i) => (
          <TableRow key={order.id} className="hover:bg-muted/50 cursor-pointer">
            <TableCell>{i + 1}</TableCell>
            <TableCell>
              <Link
                to={`/orders/${order.id}`}
                className="font-medium hover:underline"
              >
                {order.name}
              </Link>
              {order.number && (
                <div className="text-sm text-muted-foreground">
                  № {order.number}
                </div>
              )}
            </TableCell>
            <TableCell>
              {order.status_name ? (
                <StatusBadge name={order.status_name} color={order.status_color} />
              ) : (
                "—"
              )}
            </TableCell>
            <TableCell>
              {format(fromUnixTime(order.created_at), "dd.MM.yyyy HH:mm", {
                locale: ru,
              })}
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}