"use client";

import {Building2, User, ChevronRight, FileText} from "lucide-react";
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
import {useAuthStore} from "@/store/authStore";
import {useNavigate, useSearchParams, useLocation, Link} from "react-router-dom";
import {PAGE} from "@/api/constants";

export function ManagerDealersTree() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const {user} = useAuthStore();

  // Если не менеджер Лигрон — ничего не рендерим
  if (user?.provider !== "ligron") {
    return null;
  }

  // Дерево дилеров
  const dealers = user.detailed?.managed_dealers || [];
  const currentPrefix = searchParams.get("dealer_prefix");
  const currentUserId = searchParams.get("dealer_user_id")
    ? Number(searchParams.get("dealer_user_id"))
    : null;

  const handleSelect = (prefix: string | null, userId: number | null = null) => {
    const params = new URLSearchParams();
    params.set("offset", "0");
    if (prefix) params.set("dealer_prefix", prefix);
    if (userId) params.set("dealer_user_id", String(userId));
    navigate(`${PAGE.ORDERS}?${params.toString()}`);
  };

  const isAnySelectionActive = !!currentPrefix;

  return (
    <>
      {/* Дерево Дилеров (показываем только если есть дилеры) */}
      {dealers.length > 0 && (
        <Collapsible defaultOpen={isAnySelectionActive} className="group/root">
          <SidebarMenuItem>
            <CollapsibleTrigger asChild>
              <SidebarMenuButton
                className="w-full justify-between"
                onClick={() => handleSelect(null)}
                tooltip="Дилеры"
              >
                <div className="flex items-center gap-2">
                  <Building2 className="h-4 w-4"/>
                  <span className="group-data-[collapsible=icon]:hidden">
                    Дилеры ({dealers.length})
                  </span>
                </div>

                <ChevronRight
                  className="h-4 w-4 transition-transform group-data-[state=open]/root:rotate-90 group-data-[collapsible=icon]:hidden"/>

              </SidebarMenuButton>
            </CollapsibleTrigger>

            <CollapsibleContent>
              <SidebarMenuSub>
                {dealers.map((dealer) => {
                  const isDealerActive = currentPrefix === dealer.dealer_prefix;
                  const isDealerButtonActive = isDealerActive && !currentUserId;

                  return (
                    <Collapsible
                      key={dealer.dealer_prefix}
                      defaultOpen={isDealerActive}
                      className="group/dealer"
                    >
                      <SidebarMenuSubItem>
                        <CollapsibleTrigger asChild>
                          <SidebarMenuSubButton
                            className="justify-between cursor-pointer"
                            isActive={isDealerButtonActive}
                            onClick={() => handleSelect(dealer.dealer_prefix, null)}
                          >
                            <div className="flex items-center gap-2 truncate">
                              <span>{dealer.name}</span>
                              <span className="text-[10px] text-muted-foreground">
                                ({dealer.dealer_prefix})
                              </span>
                            </div>
                            <ChevronRight
                              className="h-4 w-4 opacity-50 transition-transform group-data-[state=open]/dealer:rotate-90"/>
                          </SidebarMenuSubButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                          <SidebarMenuSub className="mr-0 border-l-sidebar-border">
                            {dealer.users.map((u) => (
                              <SidebarMenuSubItem key={u.id}>
                                <SidebarMenuSubButton
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    handleSelect(dealer.dealer_prefix, u.id);
                                  }}
                                  isActive={isDealerActive && currentUserId === u.id}
                                  className="cursor-pointer"
                                >
                                  <User className="h-3 w-3"/>
                                  <span className="truncate">{u.name}</span>
                                </SidebarMenuSubButton>
                              </SidebarMenuSubItem>
                            ))}
                          </SidebarMenuSub>
                        </CollapsibleContent>
                      </SidebarMenuSubItem>
                    </Collapsible>
                  );
                })}
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>
      )}

      {/* Пункт "Заявки" */}
      <SidebarMenuItem>
        <SidebarMenuButton
          asChild
          isActive={location.pathname.startsWith(PAGE.REQUESTS)}
          tooltip="Заявки"
        >
          <Link to={PAGE.REQUESTS}>
            <FileText className="h-4 w-4"/>
            <span>Заявки</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </>
  );
}