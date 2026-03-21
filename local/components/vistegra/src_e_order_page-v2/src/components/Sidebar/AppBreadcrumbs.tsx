"use client";

import {useLocation, useParams, Link, useSearchParams} from "react-router-dom";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import {PAGE} from "@/api/constants";
import {useAuthStore} from "@/store/authStore";
import {useMemo} from "react";
import {useBreadcrumbStore} from "@/store/breadcrumbStore.ts";

interface BreadcrumbCrumb {
  label: string;
  to?: string;
  isGroup?: boolean;
}

export function AppBreadcrumbs() {
  const {orderNumber} = useBreadcrumbStore();

  const location = useLocation();
  const params = useParams();
  const [searchParams] = useSearchParams();
  const {user} = useAuthStore();

  const breadcrumbs = useMemo<BreadcrumbCrumb[]>(() => {
    const path = location.pathname;
    const crumbs: BreadcrumbCrumb[] = [];

    // 1. Мой профиль
    if (path === PAGE.PROFILE) {
      return [{label: "Мой профиль"}];
    }

    // 2. Черновики
    if (path === PAGE.DRAFTS || path.startsWith(PAGE.DRAFTS + "/")) {
      crumbs.push({label: "Черновики", to: path !== PAGE.DRAFTS ? PAGE.DRAFTS : undefined});

      if (params.id) {
        crumbs.push({label: `Черновик №${params.id}`});
      }
      return crumbs;
    }

    // 3. Заказы (основная логика)
    const isOrdersPage = path === PAGE.ORDERS || path === "/" || path.startsWith(PAGE.ORDERS + "/");
    const isOrderDetail = !!params.id && isOrdersPage;

    if (isOrdersPage) {
      // Базовая точка входа
      crumbs.push({
        label: "Заказы",
        to: (searchParams.has("inn_dealer") || isOrderDetail) ? PAGE.ORDERS : undefined,
        isGroup: !searchParams.has("inn_dealer") && !isOrderDetail
      });

      const inn = searchParams.get("inn_dealer");
      const salonCode = searchParams.get("salon_code");
      const hierarchy = user?.detailed?.hierarchy || [];

      if (inn) {
        // Ищем дилера в иерархии для получения имени
        const dealer = hierarchy.find((d) => d.inn === inn);
        const dealerName = dealer ? dealer.name : `ИНН ${inn}`;

        const dealerLinkParams = new URLSearchParams();
        dealerLinkParams.set('inn_dealer', inn);

        crumbs.push({
          label: dealerName,
          to: (salonCode || isOrderDetail)
            ? `${PAGE.ORDERS}?${dealerLinkParams.toString()}`
            : undefined
        });

        if (salonCode) {
          // Ищем салон внутри найденного дилера
          const salon = dealer?.salons.find((s) => s.salon_code === salonCode);
          const salonName = salon ? salon.name : `Салон ${salonCode}`;

          const salonLinkParams = new URLSearchParams(dealerLinkParams);
          salonLinkParams.set('salon_code', salonCode);

          crumbs.push({
            label: salonName,
            to: isOrderDetail
              ? `${PAGE.ORDERS}?${salonLinkParams.toString()}`
              : undefined
          });
        }
      }

      // Номер заказа в конце
      if (isOrderDetail) {
        crumbs.push({
          label: orderNumber ? `Заказ №${orderNumber}` : `Заказ (ID: ${params.id})`
        });
      }

      return crumbs;
    }

    return [{label: "Главная"}];
  }, [location.pathname, params, searchParams, user, orderNumber]);

  return (
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
                <span className="text-muted-foreground font-medium">
                  {crumb.label}
                </span>
              ) : (
                <BreadcrumbPage>{crumb.label}</BreadcrumbPage>
              )}
            </BreadcrumbItem>
          </div>
        ))}
      </BreadcrumbList>
    </Breadcrumb>
  );
}