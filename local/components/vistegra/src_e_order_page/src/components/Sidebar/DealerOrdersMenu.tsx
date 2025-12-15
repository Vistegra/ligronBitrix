"use client";

import {SidebarMenuButton, SidebarMenuItem} from "@/components/ui/sidebar";
import {MonitorDotIcon, UserPenIcon} from "lucide-react";
import {Link, useLocation} from "react-router-dom";
import {PAGE} from "@/api/constants";
import {CalculatorButton} from "@/components/Sidebar/CalculatorButton.tsx";

export function DealerOrdersMenu() {
  const location = useLocation();

  // Активен, если мы находимся в любом подразделе /orders
  const isInOrdersSection = location.pathname.startsWith("/orders");
  const isInDraftsSection = location.pathname.startsWith("/drafts");

  return (
    <>
      {/* Пункт "Заказы" */}
      <SidebarMenuItem>
        <SidebarMenuButton
          asChild
          isActive={isInOrdersSection}
          tooltip="Заказы"
        >
          <Link to={PAGE.ORDERS}>
            <MonitorDotIcon className="h-4 w-4"/>
            <span className="group-data-[collapsible=icon]:hidden">Заказы</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>

      {/* Пункт "Черновики" */}
      <SidebarMenuItem>
        <SidebarMenuButton
          asChild
          isActive={isInDraftsSection}
          tooltip="Черновики"
        >
          <Link to={PAGE.DRAFTS}>
            <UserPenIcon className="h-4 w-4"/>
            <span className="group-data-[collapsible=icon]:hidden">Черновики</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>

      <CalculatorButton />
    </>
  );
}