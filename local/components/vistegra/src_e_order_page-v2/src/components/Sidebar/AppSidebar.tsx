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
import {ROLE_NAMES} from "@/config/roles.ts";
import {PAGE} from "@/api/constants.ts";
import {Button} from "@/components/ui/button";
import {cn} from "@/lib/utils";

import {HierarchyTree} from "@/components/Sidebar/HierarchyTree";
import {CalculatorButton} from "@/components/Sidebar/CalculatorButton";
import {useSidebarResizer} from "@/hooks/sidebar/useSidebarResizer";
import {SidebarResizeHandle} from "./SidebarResizeHandle";
import {SidebarSearch} from "@/components/Sidebar/SidebarSearch.tsx";
import {useSidebarFilter} from "@/hooks/sidebar/useSidebarFilter.ts";
import {useWorkspace} from "@/hooks/common/useWorkspace.ts";

export function AppSidebar() {
  const {user, logout} = useAuthStore();
  const location = useLocation();
  const {state} = useSidebar();
  const {getContextLink} = useWorkspace();
  const {width, isResizing, startResizing} = useSidebarResizer();

  const [searchTerm, setSearchTerm] = useState("");
  const filteredHierarchy = useSidebarFilter(user?.detailed?.hierarchy, searchTerm);

  const isActive = (path: string) => {
    if (path === PAGE.PROFILE) return location.pathname === PAGE.PROFILE;
    return location.pathname.startsWith(path);
  };

  return (
    <Sidebar
      collapsible="icon"
      style={{"--sidebar-width": `${width}px`} as React.CSSProperties}
      className={cn(
        "group-data-[collapsible=icon]:w-14 relative",
        isResizing ? "transition-none" : "transition-[width,margin] duration-200"
      )}
    >
      {state === "expanded" && (
        <SidebarResizeHandle onMouseDown={startResizing} isResizing={isResizing}/>
      )}

      {/* Хеадер */}
      <SidebarHeader
        className="h-16 border-b border-sidebar-border flex flex-row items-center justify-start px-4 shrink-0">
        <Link to="/" className="flex items-center gap-3 w-full">
          <div
            className="flex-shrink-0 w-8 h-8 rounded bg-green-600 flex items-center justify-center text-white font-bold">L
          </div>
          <div className="flex flex-col group-data-[collapsible=icon]:hidden text-left min-w-0">
            <span className="font-bold text-sm leading-none truncate">LIGRON</span>
            <span className="text-[10px] uppercase tracking-tighter text-muted-foreground truncate mt-0.5">Электронный заказ</span>
          </div>
        </Link>
      </SidebarHeader>

      {/* Поиск и иерархия */}
      <SidebarContent className="flex flex-col overflow-hidden">
        <div className="p-2 shrink-0">
          <SidebarSearch value={searchTerm} onChange={setSearchTerm}/>
        </div>

        <div className="flex-1 overflow-y-auto custom-scrollbar px-2">
          <HierarchyTree data={filteredHierarchy} isSearching={searchTerm.length > 0}/>
        </div>
      </SidebarContent>

      {/* Футер */}
      <SidebarFooter className="border-t border-sidebar-primary/10 p-2 space-y-2 shrink-0 bg-sidebar-accent/20">

        {/* Навигация */}
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              asChild
              isActive={isActive(PAGE.PROFILE)}
              tooltip="Мой профиль"
            >
              <Link to={PAGE.PROFILE}><User className="h-4 w-4"/><span>Мой профиль</span></Link>
            </SidebarMenuButton>
          </SidebarMenuItem>

          {user?.provider === "dealer" && (
            <SidebarMenuItem>
              <SidebarMenuButton
                asChild
                isActive={location.pathname.startsWith(PAGE.DRAFTS)}
                tooltip="Черновики"
              >
                <Link to={getContextLink(PAGE.DRAFTS)}>
                  <UserPenIcon className="h-4 w-4"/>
                  <span>Черновики</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )}

          {user?.provider === "ligron" && (
            <SidebarMenuItem>
              <SidebarMenuButton
                asChild
                isActive={location.pathname.startsWith(PAGE.REQUESTS)}
                tooltip="Заявки"
              >
                <Link to={getContextLink(PAGE.REQUESTS)}>
                  <FileText className="h-4 w-4"/>
                  <span>Заявки</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )}

          <CalculatorButton/>
        </SidebarMenu>

        <SidebarSeparator className="mx-0"/>

        {/* Блок аккаунта и выхода */}
        <SidebarMenu>
          <SidebarMenuItem>
            <div className="flex items-center gap-3 px-2 py-2 overflow-hidden">
              <div
                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <User className="h-4 w-4"/>
              </div>
              <div className="flex flex-col flex-1 overflow-hidden group-data-[collapsible=icon]:hidden text-left">
                <span className="text-[12px] font-bold truncate leading-tight text-foreground">{user?.name}</span>
                <span className="text-[10px] text-muted-foreground truncate leading-tight mt-0.5">
                  {ROLE_NAMES[user?.role as keyof typeof ROLE_NAMES] || user?.role}
                </span>
              </div>
            </div>
          </SidebarMenuItem>

          <SidebarMenuItem>
            <Button
              variant="ghost"
              size="sm"
              className="w-full justify-start text-muted-foreground hover:text-destructive hover:bg-destructive/10 h-8 px-2"
              onClick={logout}
            >
              <LogOut className="h-4 w-4 mr-2"/>
              <span className="group-data-[collapsible=icon]:hidden text-sm">Выйти</span>
            </Button>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
    </Sidebar>
  );
}