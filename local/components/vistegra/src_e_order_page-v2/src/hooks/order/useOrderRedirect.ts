import { useEffect } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { PAGE } from "@/api/constants";
import { toast } from "sonner";
import { queries } from "@/lib/queryFactory";

export function useOrderRedirect() {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();

  const orderNumber = searchParams.get("order_number");

  // Конфигурация из фабрики
  const { data, isLoading, isError } = useQuery({
    ...queries.orders.byNumber(orderNumber!),
    enabled: !!orderNumber,
  });

  useEffect(() => {
    // Редирект при нахождении заказа
    if (data?.data?.order?.id) {
      navigate(PAGE.orderDetail(data.data.order.id), { replace: true });
    }
  }, [data, navigate]);

  useEffect(() => {
    // Ошибка: уведомление и чистка URL
    if (isError) {
      toast.error(`Заказ № ${orderNumber} не найден`);

      setSearchParams((prev) => {
        const next = new URLSearchParams(prev);
        next.delete("order_number");
        return next;
      }, { replace: true });
    }
  }, [isError, orderNumber, setSearchParams]);

  return {
    // Статусы
    isRedirecting: !!orderNumber && (isLoading || !!data),
  };

}