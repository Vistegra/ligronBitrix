"use client";

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";

import {LogOut, User} from "lucide-react";
import {Link, useLocation} from "react-router-dom";
import {useAuthStore} from "@/store/authStore";
import {ROLE_NAMES} from "@/constants/constants.ts";
import {PAGE} from "@/api/constants.ts";
import {Button} from "@/components/ui/button";

import {DealerOrdersMenu} from "@/components/Sidebar/DealerOrdersMenu";
import {ManagerDealersTree} from "@/components/Sidebar/ManagerDealersTree";

export function AppSidebar() {
  const {user, logout} = useAuthStore();
  const location = useLocation();

  const isActive = (path: string) => location.pathname === path;

  return (
    <Sidebar collapsible="icon" className="group-data-[collapsible=icon]:w-14">
      {/* Логотип */}
      <SidebarHeader className="h-16 border-b border-sidebar-border flex items-center px-4 md:justify-center">
        <SidebarMenuButton asChild size="lg" className="p-0">
          <Link to="/" className="flex items-center gap-3">
            {/* SVG-логотип */}
            <div className="flex-shrink-0 w-10 h-10 rounded-md overflow-hidden md:w-8 md:h-8 md:rounded-sm">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 49 51"
                fill="none"
                className="w-full h-full object-contain"
              >
                <path d="M48.2023 0H0V50.8276H48.2023V0Z" fill="#229E35"/>
                <path
                  d="M31.4627 28.1771V34.6543H16V31.8412L18.0773 31.402V17.9399L16 17.5008V14.6738H25.4372V17.5008L22.906 17.9399V30.9628H27.6658L27.7759 28.1771H31.4627Z"
                  fill="white"/>
              </svg>
            </div>

            {/* Текстовая часть */}
            <div className="flex flex-col gap-0 data-[state=collapsed]:hidden">
              <span className="font-bold text-lg leading-none">LIGRON</span>
              <span className="text-xs text-muted-foreground">Электронный заказ</span>
            </div>
          </Link>
        </SidebarMenuButton>
      </SidebarHeader>

      <SidebarContent>
        <SidebarMenu className="px-2">

          {/* 1. Мой профиль (для всех) */}
          <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={isActive(PAGE.PROFILE)} tooltip="Профиль">
              <Link to={PAGE.PROFILE}>
                <User className="h-4 w-4"/>
                <span>Мой профиль</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>

          {/* 2. Меню Дилера (Заказы + Черновики) */}
          {user?.provider === "dealer" && <DealerOrdersMenu/>}

          {/* 3. Меню Менеджера (Дерево дилеров + Заявки) */}
          {user?.provider === "ligron" && <ManagerDealersTree/>}

        </SidebarMenu>
      </SidebarContent>

      {/* Футер */}
      {user && (
        <SidebarFooter className="border-t border-sidebar-border">
          <SidebarMenu>
            <SidebarMenuItem>
              <div className="flex items-center gap-3 px-3 py-3">
                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted">
                  <User className="h-5 w-5 text-muted-foreground"/>
                </div>
                <div className="flex flex-col flex-1 overflow-hidden group-data-[state=collapsed]:hidden">
                  <span className="text-sm font-medium truncate">{user.name}</span>
                  <span className="text-xs text-muted-foreground truncate">
                    {ROLE_NAMES[user.role] || user.role}
                  </span>
                </div>
              </div>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <Button variant="ghost" className="w-full justify-start" onClick={logout}>
                <LogOut className="h-4 w-4 mr-2"/>
                <span className="group-data-[state=collapsed]:hidden">Выйти</span>
              </Button>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarFooter>
      )}

    </Sidebar>
  );
}