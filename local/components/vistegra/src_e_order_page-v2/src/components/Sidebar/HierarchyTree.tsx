"use client";

import {Building2, Store, ChevronRight, LayoutDashboard} from "lucide-react";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem
} from "@/components/ui/sidebar";

import {useSearchParams, useLocation} from "react-router-dom";
import {PAGE} from "@/api/constants";
import type {Dealer} from "@/hooks/sidebar/useSidebarFilter";
import {useWorkspace} from "@/hooks/common/useWorkspace.ts";
import {cn} from "@/lib/utils";

interface HierarchyTreeProps {
  data: Dealer[];
  isSearching: boolean;
}

export function HierarchyTree({data, isSearching}: HierarchyTreeProps) {
  const [searchParams] = useSearchParams();
  const location = useLocation();
  const {setWorkspace, resetWorkspace} = useWorkspace();

  if (data.length === 0) {
    return isSearching ? (
      <div className="px-4 py-2 text-[10px] text-muted-foreground">Ничего не найдено</div>
    ) : null;
  }

  return (
    <>
      {!isSearching && (
        <SidebarMenuItem>
          <SidebarMenuButton
            onClick={resetWorkspace}
            isActive={location.pathname === PAGE.ORDERS && !searchParams.get("inn_dealer")}
            tooltip="Все заказы (общий список)"
          >
            <LayoutDashboard className="h-4 w-4"/>
            <span className="group-data-[collapsible=icon]:hidden font-semibold">Все заказы</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      )}

      {data.map((dealer) => {
        const isDealerActive = searchParams.get("inn_dealer") === dealer.inn;
        const isOnlyDealerSelected = isDealerActive && !searchParams.get("salon_code");
        const shouldBeOpen = isSearching || isDealerActive;

        return (
          <Collapsible
            key={`${dealer.inn}-${isSearching}`}
            defaultOpen={shouldBeOpen}
            className="group/dealer"
          >
            <SidebarMenuItem>
              <div className="flex items-center w-full gap-0">

                {/* Зона выбора дилера */}
                <SidebarMenuButton
                  className="flex-1 justify-start h-auto py-2"
                  onClick={() => setWorkspace(dealer.inn, null)}
                  isActive={isOnlyDealerSelected}
                  tooltip={`${dealer.name} (${dealer.inn})`}
                >
                  <Building2 className="h-4 w-4 shrink-0"/>
                  <div className="flex flex-col min-w-0 overflow-hidden group-data-[collapsible=icon]:hidden">
                    <span className="truncate font-medium text-[13px] leading-tight">
                      {dealer.name}
                    </span>
                    <span className="text-[10px] text-muted-foreground font-normal leading-none mt-0.5">
                      {dealer.inn}
                    </span>
                  </div>
                </SidebarMenuButton>

                {/* Триггер стрелочка */}
                <CollapsibleTrigger asChild>
                  <button
                    className={cn(
                      "h-9 w-8 flex items-center justify-center hover:bg-sidebar-accent rounded-md transition-all group-data-[collapsible=icon]:hidden",
                      "text-muted-foreground/50 hover:text-foreground"
                    )}
                    onClick={(e) => e.stopPropagation()} // На всякий случай блокируем всплытие
                  >
                    <ChevronRight
                      className="h-4 w-4 transition-transform duration-200 group-data-[state=open]/dealer:rotate-90"
                    />
                  </button>
                </CollapsibleTrigger>
              </div>

              <CollapsibleContent>
                <SidebarMenuSub className="mr-0 border-l-sidebar-border ml-4">
                  {dealer.salons.map((salon) => (
                    <SidebarMenuSubItem key={salon.salon_code}>
                      <SidebarMenuSubButton
                        onClick={() => setWorkspace(dealer.inn, salon.salon_code)}
                        isActive={searchParams.get("salon_code") === salon.salon_code}
                        className="py-2 h-auto cursor-pointer"
                      >
                        <Store className="h-3.5 w-3.5 shrink-0 opacity-70"/>
                        <span className="truncate text-xs">{salon.name}</span>
                      </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                  ))}
                </SidebarMenuSub>
              </CollapsibleContent>
            </SidebarMenuItem>
          </Collapsible>
        );
      })}
    </>
  );
}