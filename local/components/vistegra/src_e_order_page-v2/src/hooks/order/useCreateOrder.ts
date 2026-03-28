import {useMutation, useQueryClient} from "@tanstack/react-query";
import {useNavigate} from "react-router-dom";
import {orderApi, type CreateOrderData} from "@/api/orderApi.ts";
import {PAGE} from "@/api/constants.ts";
import {toast} from "sonner";
import {queries} from "@/lib/queryFactory";

export function useCreateOrder() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const mutation = useMutation({
    mutationFn: (data: CreateOrderData) => orderApi.createOrder(data),
    onSuccess: (response, variables) => {
      // Сброс всех данных по заказам через ключ фабрики
      queryClient.invalidateQueries({queryKey: queries.orders._root()});

      const isDraft = !!variables.is_draft;
      toast.success("Успешно создан!");

      const orderId = response?.data?.order?.id;
      if (!orderId) throw new Error('Не установлен идентификатор заказа');

      // Редирект в зависимости от типа (черновик или заказ)
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
    create: mutation.mutate,
    isPending: mutation.isPending,
    isSuccess: mutation.isSuccess,
    error: mutation.error ? (mutation.error as Error).message : null,
    data: mutation.data?.data
  };
}