import {useMemo} from "react";
import {useLocation, useParams, useSearchParams} from "react-router-dom";
import {useAuthStore} from "@/store/authStore";
import {useBreadcrumbStore} from "@/store/breadcrumbStore";
import {PAGE} from "@/api/constants";

export interface BreadcrumbCrumb {
  label: string;
  to?: string;
  isGroup?: boolean;
}

export function useBreadcrumbs() {
  const location = useLocation();
  const params = useParams();
  const [searchParams] = useSearchParams();

  const {user} = useAuthStore();
  const {orderNumber} = useBreadcrumbStore();

  return useMemo<BreadcrumbCrumb[]>(() => {
    const path = location.pathname;
    const crumbs: BreadcrumbCrumb[] = [];

    // Профиль
    if (path === PAGE.PROFILE) {
      return [{label: "Мой профиль"}];
    }

    // Черновики
    if (path === PAGE.DRAFTS || path.startsWith(PAGE.DRAFTS + "/")) {
      crumbs.push({
        label: "Черновики",
        to: path !== PAGE.DRAFTS ? PAGE.DRAFTS : undefined
      });

      if (params.id) {
        crumbs.push({label: `Черновик №${params.id}`});
      }
      return crumbs;
    }

    // Заказы
    const isOrdersPage = path === PAGE.ORDERS || path === "/" || path.startsWith(PAGE.ORDERS + "/");
    const isOrderDetail = !!params.id && isOrdersPage;

    if (isOrdersPage) {
      const innParam = searchParams.get("inn_dealer");
      const salonCodeParam = searchParams.get("salon_code");

      const inns = innParam ? innParam.split(",").filter(Boolean) : [];
      const salonCodes = salonCodeParam ? salonCodeParam.split(",").filter(Boolean) : [];
      const hierarchy = user?.detailed?.hierarchy || [];

      crumbs.push({
        label: "Заказы",
        to: (inns.length > 0 || isOrderDetail) ? PAGE.ORDERS : undefined,
        isGroup: inns.length === 0 && !isOrderDetail
      });

      // Дилеры
      if (inns.length > 0) {
        let dealerLabel = "";
        if (inns.length === 1) {
          const dealer = hierarchy.find((d) => d.inn === inns[0]);
          dealerLabel = dealer ? dealer.name : `ИНН ${inns[0]}`;
        } else {
          dealerLabel = `Дилеры (${inns.length})`;
        }

        const dealerLinkParams = new URLSearchParams();
        dealerLinkParams.set('inn_dealer', innParam!);

        crumbs.push({
          label: dealerLabel,
          to: (salonCodes.length > 0 || isOrderDetail)
            ? `${PAGE.ORDERS}?${dealerLinkParams.toString()}`
            : undefined
        });

        // Салоны
        if (salonCodes.length > 0) {
          let salonLabel = "";
          if (salonCodes.length === 1) {
            let foundName = `Салон ${salonCodes[0]}`;
            // Ищем салон по всем дилерам в иерархии
            for (const d of hierarchy) {
              const s = d.salons.find(item => item.salon_code === salonCodes[0]);
              if (s) {
                foundName = s.name;
                break;
              }
            }
            salonLabel = foundName;
          } else {
            salonLabel = `Салоны (${salonCodes.length})`;
          }

          const salonLinkParams = new URLSearchParams(dealerLinkParams);
          salonLinkParams.set('salon_code', salonCodeParam!);

          crumbs.push({
            label: salonLabel,
            to: isOrderDetail
              ? `${PAGE.ORDERS}?${salonLinkParams.toString()}`
              : undefined
          });
        }
      }

      // Текущий заказ
      if (isOrderDetail) {
        crumbs.push({
          label: orderNumber ? `Заказ №${orderNumber}` : `Заказ (ID: ${params.id})`
        });
      }

      return crumbs;
    }

    return [{label: "Главная"}];
  }, [location.pathname, params, searchParams, user, orderNumber]);
}