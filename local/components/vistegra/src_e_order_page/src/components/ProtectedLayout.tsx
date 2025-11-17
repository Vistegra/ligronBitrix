import {
  SidebarProvider,
  SidebarInset,
  SidebarTrigger,
  SidebarRail,
} from "@/components/ui/sidebar"

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import {Separator} from "@/components/ui/separator"
import {Link, Outlet, useLocation, useParams} from "react-router-dom"
import {PAGE} from "@/api/constants.ts"
import {AppSidebar} from "@/components/AppSidebar.tsx";

interface BreadcrumbCrumb {
  label: string
  to?: string
  isGroup?: boolean
}

export default function ProtectedLayout() {
  const location = useLocation()
  const params = useParams()

  const getBreadcrumbs = (): BreadcrumbCrumb[] => {
    const path = location.pathname

    if (path === PAGE.PROFILE) return [{label: "Мой профиль"}]
    if (path === PAGE.DRAFTS) return [{label: "Черновики"}]
    if (path.startsWith("/drafts/")) {
      const id = params.id || path.split("/").pop()
      return [
        {label: "Черновики", to: PAGE.DRAFTS},
        {label: `Черновик №${id}`}
      ]
    }

    if (path === PAGE.ORDERS || path === "/" || (path.startsWith("/orders/") && !path.includes("canceled"))) {
      const items: BreadcrumbCrumb[] = [{label: "Заказы", isGroup: true}]
      if (path === PAGE.ORDERS || path === "/") {
        items.push({label: "История заказов"})
      } else {
        const id = params.id
        items.push({label: "История заказов", to: PAGE.ORDERS})
        items.push({label: `Заказ №${id}`})
      }
      return items
    }

    if (path === PAGE.ORDERS_CANCELED) {
      return [
        {label: "Заказы", isGroup: true},
        {label: "Отменённые заказы"}
      ]
    }

    return [{label: "Главная"}]
  }

  const breadcrumbs = getBreadcrumbs()

  return (
    <SidebarProvider defaultOpen={true}>
      <AppSidebar/>

      {/* ToDo */}
      <SidebarRail/>

      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center gap-2 border-b bg-background px-4">
          <SidebarTrigger className="-ml-1"/>
          <Separator orientation="vertical" className="mr-2 h-4"/>
          <Breadcrumb>
            <BreadcrumbList>
              {breadcrumbs.map((crumb, index) => (
                <div key={index} className="flex items-center gap-1.5">
                  {index > 0 && <BreadcrumbSeparator/>}
                  <BreadcrumbItem>
                    {crumb.to ? (
                      <BreadcrumbLink asChild>
                        <Link to={crumb.to}>{crumb.label}</Link>
                      </BreadcrumbLink>
                    ) : crumb.isGroup ? (
                      <span className="text-muted-foreground font-medium">{crumb.label}</span>
                    ) : (
                      <BreadcrumbPage>{crumb.label}</BreadcrumbPage>
                    )}
                  </BreadcrumbItem>
                </div>
              ))}
            </BreadcrumbList>
          </Breadcrumb>
        </header>

        <main className="p-6">
          <Outlet/>
        </main>
      </SidebarInset>
    </SidebarProvider>
  )
}