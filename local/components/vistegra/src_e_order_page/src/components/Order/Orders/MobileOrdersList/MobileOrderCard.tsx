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
import type {ManagerDetailed} from "@/types/user";
import {formatDate, getOrderType, getOrderTypeLabel} from "@/components/Order/Orders/utils";

interface MobileOrderCardProps {
  order: Order;
  isDraft: boolean;
}

export function MobileOrderCard({order, isDraft}: MobileOrderCardProps) {
  const {user} = useAuthStore();

  // Форматирование дат
  const dateCreated = formatDate(order.created_at);
  const dateUpdated = formatDate(order.updated_at);

  const linkTo = isDraft
    ? PAGE.draftDetail(order.id)
    : PAGE.orderDetail(order.id);


  const orderType = getOrderTypeLabel(getOrderType(order));

  // Логика получения имени дилера (Только для менеджеров)
  const dealerInfo = useMemo(() => {
    if (user?.provider !== 'ligron' || !order.dealer_prefix) return null;

    const dealers = (user.detailed as ManagerDetailed)?.managed_dealers || [];
    const found = dealers.find(d => d.dealer_prefix === order.dealer_prefix);

    return found ? found.name : order.dealer_prefix;
  }, [user, order.dealer_prefix]);

  return (
    <Link to={linkTo} className="block group">
      <Card className="rounded-lg border shadow-sm p-3.5 active:bg-muted/30 transition-all active:scale-[0.99]">

        {/* Верхняя часть: Номер + Дилер + Дата создания */}
        <div className="flex justify-between items-start mb-2">
          <div className="flex flex-row gap-2">
            {/* Номер заказа */}
            <span className="font-semibold text-base text-foreground">
              {order.number || `ID ${order.id}`}
            </span>

            {/* Имя дилера (только для менеджера) */}
            {dealerInfo && (
              <div
                className="flex items-center gap-1 rounded-sm text-xs text-muted-foreground font-medium bg-muted px-1.5 py-0.5">
                <span className="truncate max-w-[200px]">{dealerInfo}</span>
              </div>
            )}
          </div>

          {/* Дата создания */}
          <span className="text-xs text-muted-foreground/70 font-medium">
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
            {/* Статус */}
            {!isDraft ? (
              <StatusBadge name={order.status_name} color={order.status_color}/>
            ) : (
              <Badge variant="secondary" className="h-5 px-1.5 text-[10px]">Черновик</Badge>
            )}

            {/* Тип */}
            <Badge variant="outline"
                   className="h-5 px-1.5 text-[10px] font-normal text-muted-foreground border-border bg-background/50">
              {orderType}
            </Badge>
          </div>

          {/* Дата обновления */}
          <div className="flex items-center gap-1 text-[10px] text-muted-foreground/60 shrink-0 pl-2"
               title="Дата обновления">
            <RefreshCcw className="h-3 w-3"/>
            <span>{dateUpdated}</span>
          </div>

          <ChevronRightIcon className="h-5 w-5 text-muted-foreground/40"/>

        </div>
      </Card>
    </Link>
  );
}