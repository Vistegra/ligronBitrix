"use client";

import {useNavigate, useSearchParams} from "react-router-dom";
import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {MoreHorizontal, Eye, Trash2, SendIcon, Loader2} from "lucide-react";
import {type Order, ORDER_ACTION} from "@/api/orderApi.ts";
import {Can} from "@/components/ui/custom/Can.tsx";
import {showDeleteConfirmToast} from "@/components/ui/popups/DeleteConfirmToast";
import {useOrderMutations} from "@/hooks/order/useOrderMutations";

interface OrdersTableActionsProps {
  order: Order;
  basePage: string;
}

export function OrdersTableActions({order, basePage}: OrdersTableActionsProps) {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  const isDraftPage = basePage.includes('drafts');

  // Подключаем мутации для конкретного заказа
  const {deleteOrder, sendToLigron, isWorking} = useOrderMutations(order.id, isDraftPage);

  const handleOpen = () => {
    const newParams = new URLSearchParams(searchParams);

    if (order.dealer_prefix) {
      newParams.set("dealer_prefix", order.dealer_prefix);
    }
    if (order.dealer_user_id) {
      newParams.set("dealer_user_id", String(order.dealer_user_id));
    }

    navigate({
      pathname: `${basePage}/${order.id}`,
      search: newParams.toString(),
    });
  };

  const handleDelete = (e: React.MouseEvent) => {
    e.stopPropagation(); // Чтобы не срабатывал клик по строке таблицы, если он есть
    showDeleteConfirmToast({
      title: `Удалить заказ ${order.number || 'Черновик'}?`,
      description: "Заказ и все прикрепленные файлы будут удалены навсегда.",
      onConfirm: () => deleteOrder.mutate(),
    });
  };

  const handleSendToLigron = (e: React.MouseEvent) => {
    e.stopPropagation();
    sendToLigron.mutate();
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0" disabled={isWorking}>
          <span className="sr-only">Открыть меню</span>
          {isWorking ? <Loader2 className="h-4 w-4 animate-spin text-muted-foreground"/> :
            <MoreHorizontal className="h-4 w-4"/>}
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="end" className="w-48">

        {/* Открыть (Доступно всегда) */}
        <DropdownMenuItem onClick={handleOpen} className="cursor-pointer">
          <Eye className="h-4 w-4 mr-2 text-muted-foreground"/>
          Открыть детали
        </DropdownMenuItem>

        {/* Отправить в лигрон (отображается, если есть права) */}
        <Can action={ORDER_ACTION.SEND_TO_1C} order={order}>
          <DropdownMenuItem onClick={handleSendToLigron}
                            className="cursor-pointer text-green-700 focus:text-green-700 focus:bg-green-50">
            <SendIcon className="h-4 w-4 mr-2"/>
            В Лигрон
          </DropdownMenuItem>
        </Can>

        {/* Удалить (отображается, если есть права) */}
        <Can action={ORDER_ACTION.DELETE} order={order}>
          <DropdownMenuSeparator/>
          <DropdownMenuItem onClick={handleDelete}
                            className="cursor-pointer text-destructive focus:text-destructive focus:bg-destructive/10">
            <Trash2 className="h-4 w-4 mr-2"/>
            Удалить
          </DropdownMenuItem>
        </Can>

      </DropdownMenuContent>
    </DropdownMenu>
  );
}