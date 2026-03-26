import { useMemo } from "react";
import { useLocation, useParams, useSearchParams } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import { useBreadcrumbStore } from "@/store/breadcrumbStore";
import { PAGE } from "@/api/constants";

export interface BreadcrumbCrumb {
  label: string;
  to?: string;
  isGroup?: boolean;
}

export function useBreadcrumbs() {
  const location = useLocation();
  const params = useParams();
  const [searchParams] = useSearchParams();

  const { user } = useAuthStore();
  const { orderNumber } = useBreadcrumbStore();

  return useMemo<BreadcrumbCrumb[]>(() => {
    const path = location.pathname;
    const crumbs: BreadcrumbCrumb[] = [];

    // 1. Профиль
    if (path === PAGE.PROFILE) {
      return [{ label: "Мой профиль" }];
    }

    // 2. Черновики
    if (path === PAGE.DRAFTS || path.startsWith(PAGE.DRAFTS + "/")) {
      crumbs.push({
        label: "Черновики",
        to: path !== PAGE.DRAFTS ? PAGE.DRAFTS : undefined
      });

      if (params.id) {
        crumbs.push({ label: `Черновик №${params.id}` });
      }
      return crumbs;
    }

    // 3. Заказы (основная логика)
    const isOrdersPage = path === PAGE.ORDERS || path === "/" || path.startsWith(PAGE.ORDERS + "/");
    const isOrderDetail = !!params.id && isOrdersPage;

    if (isOrdersPage) {
      const inn = searchParams.get("inn_dealer");
      const salonCode = searchParams.get("salon_code");
      const hierarchy = user?.detailed?.hierarchy || [];

      // Базовая точка "Заказы"
      // Ссылку даем, только если мы "глубже" (выбран дилер или это детали заказа)
      crumbs.push({
        label: "Заказы",
        to: (inn || isOrderDetail) ? PAGE.ORDERS : undefined,
        isGroup: !inn && !isOrderDetail
      });

      if (inn) {
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

      // Текущий заказ (конец цепочки)
      if (isOrderDetail) {
        crumbs.push({
          label: orderNumber ? `Заказ №${orderNumber}` : `Заказ (ID: ${params.id})`
        });
      }

      return crumbs;
    }

    return [{ label: "Главная" }];
  }, [location.pathname, params, searchParams, user, orderNumber]);
}