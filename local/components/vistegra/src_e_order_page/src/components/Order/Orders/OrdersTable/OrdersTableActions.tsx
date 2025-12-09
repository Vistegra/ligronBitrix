"use client";

import {useNavigate, useSearchParams} from "react-router-dom";
import {Button} from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {MoreHorizontal, Eye} from "lucide-react";
import type {Order} from "@/api/orderApi.ts";

interface OrdersTableActionsProps {
  order: Order;
  basePage: string;
}

export function OrdersTableActions({order, basePage}: OrdersTableActionsProps) {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

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

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Открыть меню</span>
          <MoreHorizontal className="h-4 w-4"/>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem
          onClick={handleOpen}
          className="flex items-center gap-2 cursor-pointer"
        >
          <Eye className="h-4 w-4"/>
          Открыть
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}