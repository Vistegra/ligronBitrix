import {
  SidebarProvider,
  SidebarInset,
  SidebarTrigger,
} from "@/components/ui/sidebar"
import { Separator } from "@/components/ui/separator"
import { Outlet } from "react-router-dom"

import { AppSidebar } from "@/components/Sidebar/AppSidebar.tsx";
import { useAuth } from "@/hooks/useAuth.ts";
import FullscreenLoader from "@/components/ui/FullscreenLoader.tsx";
import {AppBreadcrumbs} from "@/components/Sidebar/AppBreadcrumbs.tsx";


export function ProtectedLayout() {
  const {isLoading} = useAuth()

  // Показываем лоадер во время инициализации
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

        <main className="p-6">
          <Outlet/>
        </main>
      </SidebarInset>
    </SidebarProvider>
  )
}