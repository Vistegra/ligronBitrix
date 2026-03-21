"use client";

import { Building2, Store, ChevronRight, LayoutGrid } from "lucide-react";
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
import { useContextStore } from "@/store/contextStore";
import { useNavigate, useSearchParams } from "react-router-dom";
import { PAGE } from "@/api/constants";
import type {Dealer} from "@/hooks/sidebar/useSidebarFilter"; // Импортируем тип
// Импортируем тип

interface HierarchyTreeProps {
  data: Dealer[];       // Принимаем отфильтрованные данные
  isSearching: boolean; // Флаг, что сейчас идет поиск
}

export function HierarchyTree({ data, isSearching }: HierarchyTreeProps) {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { setContext, clearContext } = useContextStore();

  if (data.length === 0) {
    return isSearching ? (
      <div className="px-4 py-2 text-[10px] text-muted-foreground">Ничего не найдено</div>
    ) : null;
  }

  const handleSalonSelect = (inn: string, salonCode: string) => {
    setContext(inn, salonCode);
    const params = new URLSearchParams();
    params.set("inn_dealer", inn);
    params.set("salon_code", salonCode);
    params.set("offset", "0");
    navigate(`${PAGE.ORDERS}?${params.toString()}`);
  };

  const handleDealerSelect = (inn: string) => {
    setContext(inn, null);
    const params = new URLSearchParams();
    params.set("inn_dealer", inn);
    params.set("offset", "0");
    navigate(`${PAGE.ORDERS}?${params.toString()}`);
  };

  const handleReset = () => {
    clearContext();
    navigate(PAGE.ORDERS);
  };

  return (
    <>
      {/* Кнопка "Все заказы" показываем только когда нет поиска */}
      {!isSearching && (
        <SidebarMenuItem>
          <SidebarMenuButton
            onClick={handleReset}
            isActive={!searchParams.get("inn_dealer")}
            tooltip="Все доступные заказы"
          >
            <LayoutGrid className="h-4 w-4" />
            <span className="group-data-[collapsible=icon]:hidden">Все заказы</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      )}

      {data.map((dealer) => {
        const isDealerActive = searchParams.get("inn_dealer") === dealer.inn;

        // Если идет поиск, принудительно раскрываем список салонов
        const shouldBeOpen = isSearching || isDealerActive;

        return (
          <Collapsible
            key={dealer.inn}
            open={shouldBeOpen ? true : undefined} // Управляем раскрытием при поиске
            className="group/dealer"
          >
            <SidebarMenuItem>
              <CollapsibleTrigger asChild>
                <SidebarMenuButton
                  className="w-full justify-between"
                  onClick={() => handleDealerSelect(dealer.inn)}
                  tooltip={`${dealer.name} (${dealer.inn})`}
                  isActive={isDealerActive && !searchParams.get("salon_code")}
                >
                  <div className="flex items-center gap-2 truncate">
                    <Building2 className="h-4 w-4 shrink-0" />
                    <span className="group-data-[collapsible=icon]:hidden truncate font-medium">
                      {dealer.name}
                      <span className="ml-1.5 text-[10px] text-muted-foreground font-normal">
                        ({dealer.inn})
                      </span>
                    </span>
                  </div>
                  <ChevronRight
                    className="h-4 w-4 shrink-0 opacity-50 transition-transform group-data-[state=open]/dealer:rotate-90 group-data-[collapsible=icon]:hidden"
                  />
                </SidebarMenuButton>
              </CollapsibleTrigger>

              <CollapsibleContent>
                <SidebarMenuSub className="mr-0 border-l-sidebar-border">
                  {dealer.salons.map((salon) => {
                    const isSalonActive = searchParams.get("salon_code") === salon.salon_code;

                    return (
                      <SidebarMenuSubItem key={salon.salon_code}>
                        <SidebarMenuSubButton
                          onClick={() => handleSalonSelect(dealer.inn, salon.salon_code)}
                          isActive={isSalonActive}
                          className="cursor-pointer"
                        >
                          <Store className="h-3 w-3 shrink-0" />
                          <span className="truncate" title={`${salon.name} (${salon.salon_code})`}>
                            {salon.name}
                            <span className="ml-1 text-[9px] text-muted-foreground">
                              ({salon.salon_code})
                            </span>
                          </span>
                        </SidebarMenuSubButton>
                      </SidebarMenuSubItem>
                    );
                  })}
                </SidebarMenuSub>
              </CollapsibleContent>
            </SidebarMenuItem>
          </Collapsible>
        );
      })}
    </>
  );
}