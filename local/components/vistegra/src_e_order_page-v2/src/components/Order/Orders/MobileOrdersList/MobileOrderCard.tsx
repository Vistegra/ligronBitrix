"use client";

import {useMemo} from "react";
import {Link} from "react-router-dom";
import {ChevronRightIcon, RefreshCcw} from "lucide-react";
import {Badge} from "@/components/ui/badge";
import {Card} from "@/components/ui/card";
import StatusBadge from "@/components/Order/StatusBage";
import type {Order} from "@/api/orderApi";
import {PAGE} from "@/api/constants";
import {useAuthStore} from "@/store/authStore";
import {
  formatDate,
  getOrderType,
  getOrderTypeLabel,
  getDealerNameByOrder
} from "@/components/Order/Orders/utils";

interface MobileOrderCardProps {
  order: Order;
  isDraft: boolean;
}

export function MobileOrderCard({order, isDraft}: MobileOrderCardProps) {
  const {user} = useAuthStore();

  const dateCreated = formatDate(order.created_at);
  const dateUpdated = formatDate(order.updated_at);

  const linkTo = isDraft
    ? PAGE.draftDetail(order.id)
    : PAGE.orderDetail(order.id);

  const orderType = getOrderTypeLabel(getOrderType(order));

  const dealerName = useMemo(() => {
    if (user?.provider !== 'ligron') return null;
    const name = getDealerNameByOrder(user, order);
    return name === "—" ? null : name;
  }, [user, order]);

  return (
    <Link to={linkTo} className="block group">
      <Card className="rounded-lg border shadow-sm p-3.5 active:bg-muted/30 transition-all active:scale-[0.99]">

        {/* Шапка: Номер и Дилер */}
        <div className="flex justify-between items-start mb-2">
          <div className="flex flex-row gap-2 items-center overflow-hidden">
            <span className="font-semibold text-base text-foreground shrink-0">
              {order.number || `ID ${order.id}`}
            </span>

            {dealerName && (
              <div
                className="flex items-center rounded-sm text-[10px] text-muted-foreground font-medium bg-muted px-1.5 py-0.5 truncate max-w-[140px]">
                <span className="truncate">{dealerName}</span>
              </div>
            )}
          </div>

          <span className="text-[11px] text-muted-foreground/70 font-medium shrink-0">
            {dateCreated}
          </span>
        </div>

        {/* Название заказа */}
        <h3 className="text-sm font-medium leading-snug mb-3 line-clamp-2 text-foreground/90">
          {order.name}
        </h3>

        {/* Статус, Тип, Дата обновления */}
        <div className="flex items-center justify-between mt-auto pt-2 border-t border-dashed border-border/60">
          <div className="flex items-center gap-2 overflow-hidden">
            {!isDraft ? (
              <StatusBadge name={order.status_name} color={order.status_color}/>
            ) : (
              <Badge variant="secondary" className="h-5 px-1.5 text-[9px] uppercase tracking-wider">Черновик</Badge>
            )}

            <Badge variant="outline"
                   className="h-5 px-1.5 text-[9px] font-normal text-muted-foreground border-border bg-background/50">
              {orderType}
            </Badge>
          </div>

          <div className="flex items-center gap-3 shrink-0 pl-2">
            <div className="flex items-center gap-1 text-[10px] text-muted-foreground/60" title="Дата обновления">
              <RefreshCcw className="h-3 w-3"/>
              <span>{dateUpdated}</span>
            </div>
            <ChevronRightIcon className="h-4 w-4 text-muted-foreground/40"/>
          </div>
        </div>
      </Card>
    </Link>
  );
}