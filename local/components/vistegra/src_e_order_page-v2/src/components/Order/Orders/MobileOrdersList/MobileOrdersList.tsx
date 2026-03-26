"use client";

import {useEffect} from "react";
import {useInView} from "react-intersection-observer";
import {Loader2Icon, PlusIcon, SearchIcon} from "lucide-react";
import {Button} from "@/components/ui/button";
import NewOrderForm from "@/components/Order/NewOrderForm";
import {MobileOrderCard} from "./MobileOrderCard";
import {OrdersModalFilters} from "@/components/Order/Orders/OrdersModalFilters/OrdersModalFilters";
import {useAuthStore} from "@/store/authStore";
import {useMobileOrders} from "@/hooks/order/useMobileOrders";
import {ResponsiveSheet} from "@/components/ResponsiveSheet";
import {OrdersSearch} from "@/components/Order/Orders/OrdersSearch";
import {checkCanCreateOrder} from "@/components/Order/Orders/utils.ts";
import {useContextStore} from "@/store/contextStore.ts";

export default function MobileOrdersList({isDraft}: { isDraft: boolean }) {
  const {
    orders,
    isLoading,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    total
  } = useMobileOrders(isDraft);

  const {user} = useAuthStore();
  const {inn, salonCode} = useContextStore();
  const {ref, inView} = useInView();

  const canCreateOrder = checkCanCreateOrder(user, inn, salonCode);

  useEffect(() => {
    if (inView && hasNextPage) {
      fetchNextPage();
    }
  }, [inView, hasNextPage, fetchNextPage]);

  return (
    <div className="pb-24">
      {/* Хеадер */}
      <div className="sticky top-0 z-20 bg-background/95 backdrop-blur pt-2 pb-4 border-b mb-4 px-1 space-y-3">
        <div className="flex justify-between items-end px-3">
          <h1 className="text-2xl font-bold tracking-tight">
            {isDraft ? "Черновики" : "Заказы"}
          </h1>
          <span className="text-xs text-muted-foreground mb-1 font-medium">{total} всего</span>
        </div>

        <div className="flex gap-2 px-3">
          <OrdersSearch className="flex-1" placeholder="Поиск по названию или номеру..."/>
          <OrdersModalFilters/>
        </div>
      </div>

      {/* Список */}
      <div className="space-y-3 px-3">
        {orders.map((order) => (
          order && <MobileOrderCard key={order.id} order={order} isDraft={isDraft}/>
        ))}

        {/* Скелетоны */}
        {(isLoading || isFetchingNextPage) && (
          <div className="flex flex-col items-center justify-center py-10 gap-2">
            <Loader2Icon className="h-8 w-8 animate-spin text-primary"/>
            {isLoading && <span className="text-xs text-muted-foreground">Загрузка данных...</span>}
          </div>
        )}

        {/* Триггер скролла */}
        <div ref={ref} className="h-4 w-full"/>

        {!isLoading && orders.length === 0 && (
          <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="bg-muted/50 p-5 rounded-full mb-4">
              <SearchIcon className="h-10 w-10 text-muted-foreground/30"/>
            </div>
            <h3 className="text-base font-semibold">Ничего не найдено</h3>
            <p className="text-sm text-muted-foreground mt-1 px-8">Попробуйте изменить параметры фильтрации</p>
          </div>
        )}
      </div>

      {/* Кнопка создания заказа */}
      {canCreateOrder && (
        <div className="fixed bottom-24 right-4 z-30">
          <ResponsiveSheet
            title="Новый заказ"
            trigger={
              <Button size="icon"
                      className="h-14 w-14 rounded-full shadow-2xl bg-primary hover:bg-primary/90 transition-all active:scale-90">
                <PlusIcon className="h-8 w-8"/>
              </Button>
            }
          >
            <NewOrderForm/>
          </ResponsiveSheet>
        </div>
      )}
    </div>
  );
}