import {
  SidebarProvider,
  SidebarInset,
  SidebarTrigger,
} from "@/components/ui/sidebar"
import {Separator} from "@/components/ui/separator"
import {Outlet} from "react-router-dom"

import {AppSidebar} from "@/components/Sidebar/AppSidebar";
import {useAuth} from "@/hooks/auth/useAuth.ts";
import FullscreenLoader from "@/components/ui/custom/FullscreenLoader";
import {AppBreadcrumbs} from "@/components/Sidebar/AppBreadcrumbs";
import {useIsMobile} from "@/hooks/use-mobile";
import {MobileBottomNav} from "@/components/MobileBottomNav";
import {cn} from "@/lib/utils.ts";
import {ActiveContextBadge} from "@/components/Order/Orders/OrdersTable/ActiveContextBadge.tsx";

export function ProtectedLayout() {
  const {isLoading} = useAuth()
  const isMobile = useIsMobile();

  if (isLoading) {
    return <FullscreenLoader title="Проверка авторизации..." description="Подождите немного"/>;
  }

  return (
    <SidebarProvider defaultOpen={true} className="min-w-0">
      <AppSidebar/>

      <SidebarInset>
        <header
          className="flex flex-col md:flex-row shrink-0 items-start md:items-center justify-between gap-3 border-b bg-background px-4 py-3 md:py-0 md:min-h-16">
          <div className="flex items-center gap-2 w-full md:w-auto min-w-0">
            <SidebarTrigger className="-ml-1 shrink-0"/>
            <Separator orientation="vertical" className="mr-2 h-4 shrink-0"/>
            <div className="flex-1 min-w-0">
              <AppBreadcrumbs/>
            </div>
          </div>

          <div className="flex items-center w-full md:w-auto min-w-0">
            <ActiveContextBadge/>
          </div>

        </header>

        {/* padding-bottom чтобы контент не перекрывался меню на мобиле */}
        <section className={cn(
          "p-4 h-full min-w-0 overflow-hidden flex flex-col",
          isMobile ? "pb-24" : ""
        )}>
          <Outlet/>
        </section>

        {isMobile && <MobileBottomNav/>}
      </SidebarInset>
    </SidebarProvider>
  )
}