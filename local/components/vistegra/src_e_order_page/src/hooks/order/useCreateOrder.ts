import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import { orderApi, type CreateOrderData } from "@/api/orderApi.ts";
import { PAGE } from "@/api/constants.ts";
import {toast} from "sonner";


export function useCreateOrder() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const mutation = useMutation({
    mutationFn: (data: CreateOrderData) => orderApi.createOrder(data),
    onSuccess: (response, variables) => {
      // Чистим списки, чтобы новый заказ появился там
      queryClient.invalidateQueries({ queryKey: ['orders'] });

      const isDraft = !!variables.is_draft;

      toast.success("Успешно создан!");

      const orderId = response?.data?.order?.id;

      if (!orderId) throw new Error('Не установлен идентификатор заказа')

      // Редирект с задержкой, чтобы юзер увидел Alert
      const targetPage = isDraft
        ? PAGE.draftDetail(orderId)
        : PAGE.orderDetail(orderId);

      setTimeout(() => {
        navigate(targetPage);
      }, 1500);
    },

    onError: (err) => toast.error(err.message)
  });

  return {
    create: mutation.mutate, // Функция вызова
    isPending: mutation.isPending,
    isSuccess: mutation.isSuccess,
    error: mutation.error ? (mutation.error as Error).message : null,
    data: mutation.data?.data // Данные ответа (OrderResponse)
  };
}