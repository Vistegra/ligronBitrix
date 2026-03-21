import { useEffect } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { orderApi } from "@/api/orderApi";
import { PAGE } from "@/api/constants";
import { toast } from "sonner";

export function useOrderRedirect() {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();

  const orderNumber = searchParams.get("order_number");

  const { data, isLoading, isError } = useQuery({
    queryKey: ["order", "by-number", orderNumber],
    queryFn: () => orderApi.getByNumber(orderNumber!),
    enabled: !!orderNumber, // Запрос только если есть параметр
    retry: false, // Не повторять, если 404
    staleTime: 0,
  });

  useEffect(() => {
    // Если заказ найден — редирект
    if (data?.data?.order?.id) {
      navigate(PAGE.orderDetail(data.data.order.id), { replace: true });
    }
  }, [data, navigate]);

  useEffect(() => {
    // Если ошибка (404 или др) — уведомление и очистка URL
    if (isError) {
      toast.error(`Заказ № ${orderNumber} не найден`);

      setSearchParams((prev) => {
        const newParams = new URLSearchParams(prev);
        newParams.delete("order_number");
        return newParams;
      }, { replace: true });
    }
  }, [isError, orderNumber, setSearchParams]);

  return {
    isRedirecting: !!orderNumber && (isLoading || !!data),
  };
}