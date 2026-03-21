import OrdersTable from "@/components/Order/Orders/OrdersTable/OrdersTable";
import {useOrderRedirect} from "@/hooks/order/useOrderRedirect.ts";
import FullscreenLoader from "@/components/ui/custom/FullscreenLoader";
import {useIsMobile} from "@/hooks/use-mobile";
import MobileOrdersList from "@/components/Order/Orders/MobileOrdersList/MobileOrdersList";

export default function OrdersPage() {
  const {isRedirecting} = useOrderRedirect();
  const isMobile = useIsMobile();

  if (isRedirecting) {
    return <FullscreenLoader title="Поиск заказа..." description="Перенаправление на детальную страницу"/>;
  }

  if (isMobile) {
    return <MobileOrdersList isDraft={false}/>;
  }

  return (
    <>
      <OrdersTable isDraft={false}/>
    </>
  )
}