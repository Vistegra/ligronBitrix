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

export default function MobileOrdersList({isDraft}: { isDraft: boolean }) {
  const {
    orders,
    isLoading,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
    total,
    activeFilters
  } = useMobileOrders(isDraft);

  const {user} = useAuthStore();
  const {ref, inView} = useInView();

  const canCreateOrder = checkCanCreateOrder(user, activeFilters.dealer_user_id)

  useEffect(() => {
    if (inView && hasNextPage) {
      fetchNextPage();
    }
  }, [inView, hasNextPage, fetchNextPage]);

  return (
    <div className="pb-24">

      {/* Хедер с поиском и фильтром */}
      <div className="sticky top-0 z-20 bg-background/95 backdrop-blur pt-2 pb-4 border-b mb-4 px-1 space-y-3">
        <div className="flex justify-between items-end px-1">
          <h1 className="text-2xl font-bold tracking-tight">
            {isDraft ? "Черновики" : "Заказы"}
          </h1>
          <span className="text-sm text-muted-foreground mb-1">{total} всего</span>
        </div>

        <div className="flex gap-3 px-1">
          <OrdersSearch placeholder="Поиск по названию ..."/>
          <OrdersModalFilters/>
        </div>
      </div>

      {/* Список */}
      <div className="space-y-3 px-1">
        {orders.map((order) => (
          order && <MobileOrderCard key={order.id} order={order} isDraft={isDraft}/>
        ))}

        {/* Состояния загрузки */}
        {isLoading && (
          <div className="flex flex-col items-center justify-center py-10 gap-2 text-muted-foreground">
            <Loader2Icon className="h-8 w-8 animate-spin text-primary"/>
            <span className="text-sm">Загрузка списка...</span>
          </div>
        )}

        {isFetchingNextPage && (
          <div className="flex justify-center py-4">
            <Loader2Icon className="h-6 w-6 animate-spin text-primary/60"/>
          </div>
        )}

        {/* Триггер бесконечного скролла */}
        <div ref={ref} className="h-2 w-full"/>

        {!isLoading && orders.length === 0 && (
          <div className="flex flex-col items-center justify-center py-12 text-center px-4">
            <div className="bg-muted/50 p-4 rounded-full mb-4">
              <SearchIcon className="h-8 w-8 text-muted-foreground/50"/>
            </div>
            <h3 className="text-lg font-medium">Ничего не найдено</h3>
            <p className="text-sm text-muted-foreground mt-1">Попробуйте изменить фильтры</p>
          </div>
        )}
      </div>

      {/* Кнопка создания заказа */}
      {canCreateOrder && (
        <div className="fixed bottom-20 right-4 z-30">
          <ResponsiveSheet
            title="Новый заказ"
            trigger={
              <Button size="icon"
                      className="h-14 w-14 rounded-full shadow-xl bg-primary hover:bg-primary/90 transition-transform active:scale-95">
                <PlusIcon className="h-7 w-7"/>
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