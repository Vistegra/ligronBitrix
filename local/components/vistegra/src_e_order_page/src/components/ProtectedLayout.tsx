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

export function ProtectedLayout() {
  const {isLoading} = useAuth()
  const isMobile = useIsMobile();

  if (isLoading) {
    return <FullscreenLoader title="Проверка авторизации..." description="Подождите немного"/>;
  }

  return (
    <SidebarProvider defaultOpen={true}>
      <AppSidebar/>

      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center gap-2 border-b bg-background px-4">
          <SidebarTrigger className="-ml-1"/>
          <Separator orientation="vertical" className="mr-2 h-4"/>
          {/* Хлебные крошки */}
          <AppBreadcrumbs/>
        </header>

        {/* padding-bottom чтобы контент не перекрывался меню на мобиле */}
        <section className={`p-4 h-full ${isMobile ? "pb-24" : ""}`}>
          <Outlet/>
        </section>

        {isMobile && <MobileBottomNav/>}
      </SidebarInset>
    </SidebarProvider>
  )
}