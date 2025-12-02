import OrdersTable from "@/components/Order/OrdersTable/OrdersTable.tsx";
import {useOrderRedirect} from "@/hooks/order/useOrderRedirect.ts";
import FullscreenLoader from "@/components/ui/FullscreenLoader.tsx";

export default function OrdersPage() {
  const { isRedirecting } = useOrderRedirect();

  if (isRedirecting) {
    return <FullscreenLoader title="Поиск заказа..." description="Перенаправление на детальную страницу" />;
  }

  return (
    <>
     <OrdersTable isDraft={false} />
    </>
  )
}