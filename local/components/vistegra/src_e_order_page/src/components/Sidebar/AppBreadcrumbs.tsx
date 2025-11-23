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

interface BreadcrumbCrumb {
  label: string;
  to?: string;
  isGroup?: boolean;
}

export function AppBreadcrumbs() {
  const location = useLocation();
  const params = useParams();
  const [searchParams] = useSearchParams();
  const {user} = useAuthStore();

  const breadcrumbs = useMemo<BreadcrumbCrumb[]>(() => {
    const path = location.pathname;
    const crumbs: BreadcrumbCrumb[] = [];

    // Мой профиль
    if (path === PAGE.PROFILE) {
      return [{label: "Мой профиль"}];
    }

    // Черновики
    if (path === PAGE.DRAFTS) {
      return [{label: "Черновики"}];
    }

    // Используем константу для проверки пути черновика
    if (path.startsWith(PAGE.DRAFTS + "/")) {
      const id = params.id || path.split("/").pop();
      return [
        {label: "Черновики", to: PAGE.DRAFTS},
        {label: `Черновик №${id}`},
      ];
    }

    // Заказы
    // Находимся ли мы в разделе заказов
    const isOrdersPage =
      path === PAGE.ORDERS ||
      path === "/" ||
      path.startsWith(PAGE.ORDERS + "/");

    // Находимся ли мы на детальной странице
    const isOrderDetail = isOrdersPage && !!params.id;

    if (isOrdersPage) {
      // Логика для менеджера Лигрон
      if (user?.provider === "ligron") {
        crumbs.push({
          label: "Дилеры",
          isGroup: true,
          to: PAGE.ORDERS
        });

        const dealerPrefix = searchParams.get("dealer_prefix");
        const dealerUserId = searchParams.get("dealer_user_id");

        if (!dealerPrefix) {
          crumbs.push({
            label: "Все дилеры",
            to: isOrderDetail ? PAGE.ORDERS : undefined
          });
        } else {
          // Ищем дилера
          const dealer = user.detailed?.managed_dealers?.find(
            (d) => d.dealer_prefix === dealerPrefix
          );

          if (dealer) {
            // Формируем ссылку возврата к дилеру
            const dealerLinkParams = new URLSearchParams();
            dealerLinkParams.set('dealer_prefix', dealerPrefix);
            dealerLinkParams.set('offset', '0');

            crumbs.push({
              label: dealer.name,
              to: (dealerUserId || isOrderDetail)
                ? `${PAGE.ORDERS}?${dealerLinkParams.toString()}`
                : undefined
            });

            // Если выбран пользователь
            if (dealerUserId) {
              const selectedUser = dealer.users.find((u) => u.id === Number(dealerUserId));
              if (selectedUser) {
                const userLinkParams = new URLSearchParams(dealerLinkParams);
                userLinkParams.set('dealer_user_id', dealerUserId);

                crumbs.push({
                  label: selectedUser.name,
                  to: isOrderDetail
                    ? `${PAGE.ORDERS}?${userLinkParams.toString()}`
                    : undefined
                });
              }
            }
          } else {
            crumbs.push({label: dealerPrefix, isGroup: true});
          }
        }
      }

      // Логика для дилера
      else {
        crumbs.push({label: "Заказы", isGroup: true});

        crumbs.push({
          label: "История заказов",
          to: isOrderDetail ? PAGE.ORDERS : undefined
        });
      }

      // Если мы на детальной странице
      if (isOrderDetail) {
        crumbs.push({label: `Заказ (ID: ${params.id})`});
      }

      return crumbs;
    }

    return [{label: "Главная"}];
  }, [location.pathname, params, searchParams, user]);

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