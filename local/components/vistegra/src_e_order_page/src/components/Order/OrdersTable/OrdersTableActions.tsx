"use client";

import { useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreHorizontal, Eye } from "lucide-react";

interface OrdersTableActionsProps {
  orderId: number;
}

export function OrdersTableActions({ orderId }: OrdersTableActionsProps) {
  const navigate = useNavigate();

  const handleOpen = () => {
    navigate(`/orders/${orderId}`);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Открыть меню</span>
          <MoreHorizontal className="h-4 w-4" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem
          onClick={handleOpen}
          className="flex items-center gap-2 cursor-pointer"
        >
          <Eye className="h-4 w-4" />
          Открыть
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}