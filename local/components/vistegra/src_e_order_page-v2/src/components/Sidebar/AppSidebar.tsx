"use client";

import React, {useState} from "react";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarSeparator,
  useSidebar,
} from "@/components/ui/sidebar";

import {FileText, LogOut, User, UserPenIcon} from "lucide-react";
import {Link, useLocation} from "react-router-dom";
import {useAuthStore} from "@/store/authStore";
import {ROLE_NAMES} from "@/constants/constants.ts";
import {PAGE} from "@/api/constants.ts";
import {Button} from "@/components/ui/button";
import {cn} from "@/lib/utils";

import {HierarchyTree} from "@/components/Sidebar/HierarchyTree";
import {CalculatorButton} from "@/components/Sidebar/CalculatorButton";
import {useSidebarResizer} from "@/hooks/sidebar/useSidebarResizer";
import {SidebarResizeHandle} from "./SidebarResizeHandle";
import {SidebarSearch} from "@/components/Sidebar/SidebarSearch.tsx";
import {useSidebarFilter} from "@/hooks/sidebar/useSidebarFilter.ts";

export function AppSidebar() {
  const { user, logout } = useAuthStore();
  const location = useLocation();
  const { state } = useSidebar();

  const { width, isResizing, startResizing } = useSidebarResizer();
  const [searchTerm, setSearchTerm] = useState("");
  const filteredHierarchy = useSidebarFilter(user?.detailed?.hierarchy, searchTerm);

  const isActive = (path: string) => location.pathname === path;

  return (
    <Sidebar
      collapsible="icon"
      style={{ "--sidebar-width": `${width}px` } as React.CSSProperties}
      className={cn(
        "group-data-[collapsible=icon]:w-14 relative",
        isResizing ? "transition-none" : "transition-[width,margin] duration-200"
      )}
    >
      {/* Ручка ресайза */}
      {state === "expanded" && (
        <SidebarResizeHandle onMouseDown={startResizing} isResizing={isResizing} />
      )}

      <SidebarHeader className="h-16 border-b border-sidebar-border flex items-center px-4 md:justify-center">
        <SidebarMenuButton asChild size="lg" className="p-0">
          <Link to="/" className="flex items-center gap-3">
            <div className="flex-shrink-0 w-10 h-10 rounded-md overflow-hidden md:w-8 md:h-8 md:rounded-sm">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49 51" fill="none" className="w-full h-full object-contain">
                <path d="M48.2023 0H0V50.8276H48.2023V0Z" fill="#229E35" />
                <path d="M31.4627 28.1771V34.6543H16V31.8412L18.0773 31.402V17.9399L16 17.5008V14.6738H25.4372V17.5008L22.906 17.9399V30.9628H27.6658L27.7759 28.1771H31.4627Z" fill="white" />
              </svg>
            </div>
            <div className="flex flex-col gap-0 group-data-[collapsible=icon]:hidden">
              <span className="font-bold text-lg leading-none">LIGRON</span>
              <span className="text-[10px] uppercase tracking-tighter text-muted-foreground">Электронный заказ</span>
            </div>
          </Link>
        </SidebarMenuButton>
      </SidebarHeader>

      <SidebarContent className="no-scrollbar">
        <SidebarMenu className="px-2 pt-2">
          <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={isActive(PAGE.PROFILE)} tooltip="Мой профиль">
              <Link to={PAGE.PROFILE}><User className="h-4 w-4" /><span>Мой профиль</span></Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarSeparator className="mx-0 my-2" />

          {/* Поиск */}
          <SidebarSearch
            value={searchTerm}
            onChange={setSearchTerm}
            placeholder="Поиск дилера или салона..."
          />

          {/* Дерево дилеров и их салонов */}
          <HierarchyTree
            data={filteredHierarchy}
            isSearching={searchTerm.length > 0}
          />

          <SidebarSeparator className="mx-0 my-2" />
          {user?.provider === "dealer" && (
            <SidebarMenuItem>
              <SidebarMenuButton asChild isActive={location.pathname.startsWith(PAGE.DRAFTS)} tooltip="Мои черновики">
                <Link to={PAGE.DRAFTS}><UserPenIcon className="h-4 w-4" /><span>Черновики</span></Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )}
          {user?.provider === "ligron" && (
            <SidebarMenuItem>
              <SidebarMenuButton asChild isActive={location.pathname.startsWith(PAGE.REQUESTS)} tooltip="Заявки на замер/монтаж">
                <Link to={PAGE.REQUESTS}><FileText className="h-4 w-4" /><span>Заявки</span></Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )}
          <CalculatorButton />
        </SidebarMenu>
      </SidebarContent>

      <SidebarFooter className="border-t border-sidebar-border p-2">
        <SidebarMenu>
          <SidebarMenuItem>
            <div className="flex items-center gap-3 px-2 py-3 overflow-hidden">
              <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <User className="h-4 w-4" />
              </div>
              <div className="flex flex-col flex-1 overflow-hidden group-data-[collapsible=icon]:hidden">
                <span className="text-xs font-bold truncate leading-none mb-1">{user?.name}</span>
                <span className="text-[10px] text-muted-foreground truncate leading-none">
                  {ROLE_NAMES[user?.role as keyof typeof ROLE_NAMES] || user?.role}
                </span>
              </div>
            </div>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <Button variant="ghost" size="sm" className="w-full justify-start text-muted-foreground hover:text-destructive px-2" onClick={logout}>
              <LogOut className="h-4 w-4 mr-2" /><span className="group-data-[collapsible=icon]:hidden">Выйти</span>
            </Button>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
    </Sidebar>
  );
}