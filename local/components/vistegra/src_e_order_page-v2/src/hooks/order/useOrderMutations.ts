import {useMutation, useQueryClient} from "@tanstack/react-query";
import {useNavigate, useSearchParams} from "react-router-dom";
import {toast} from "sonner";
import {orderApi, type CreateOrderData} from "@/api/orderApi.ts";
import {PAGE} from "@/api/constants.ts";
import {queries} from "@/lib/queryFactory";

export function useOrderMutations(orderId: number, isDraft: boolean) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  // Инвалидация данных
  const invalidateOrder = () => {
    queryClient.invalidateQueries({queryKey: queries.orders.detail(orderId).queryKey});
    queryClient.invalidateQueries({queryKey: queries.orders._lists()});
  };

  // Проверка прав (guard)
  const requireDraft = async () => {
    if (searchParams.get('god') === 'true') return;
    if (!isDraft) {
      throw new Error("Функционал временно недоступен (редактирование запрещено)");
    }
  };

  // Обновление
  const updateMutation = useMutation({
    mutationFn: async (data: Partial<CreateOrderData>) => {
      await requireDraft();
      return orderApi.updateOrder(orderId, data);
    },
    onSuccess: () => {
      toast.success("Изменения сохранены");
      invalidateOrder();
    },
    onError: (err) => toast.error(err.message)
  });

  // Загрузка файлов
  const uploadFilesMutation = useMutation({
    mutationFn: async (files: File[]) => {
      await requireDraft();
      return orderApi.uploadFiles(orderId, files);
    },
    onSuccess: (data) => {
      if (data.status === 'partial') toast.warning(data.message);
      else toast.success("Файлы загружены");
      invalidateOrder();
    },
    onError: (err) => toast.error(err.message)
  });

  // Удаление файла
  const deleteFileMutation = useMutation({
    mutationFn: async (fileId: number) => {
      await requireDraft();
      return orderApi.deleteFile(orderId, fileId);
    },
    onSuccess: () => {
      toast.success("Файл удалён");
      invalidateOrder();
    },
    onError: (err) => toast.error(err.message)
  });

  // Отправка в Лигрон
  const sendToLigronMutation = useMutation({
    mutationFn: async () => {
      await requireDraft();
      return orderApi.sendToLigron(orderId);
    },
    onSuccess: (data) => {
      toast.success("Заказ успешно отправлен в Лигрон");
      if (data.data?.order?.id) {
        navigate(PAGE.orderDetail(data.data.order.id));
        queryClient.invalidateQueries({queryKey: queries.orders._root()});
      }
    },
    onError: (err) => toast.error(err.message)
  });

  // Удаление заказа
  const deleteOrderMutation = useMutation({
    mutationFn: async () => {
      await requireDraft();
      return orderApi.deleteOrder(orderId);
    },
    onSuccess: () => {
      toast.success("Заказ удалён");
      queryClient.invalidateQueries({queryKey: queries.orders._root()});
      navigate(isDraft ? PAGE.DRAFTS : PAGE.ORDERS);
    },
    onError: (err) => toast.error(err.message)
  });

  return {
    // Мутации
    update: updateMutation,
    uploadFiles: uploadFilesMutation,
    deleteFile: deleteFileMutation,
    sendToLigron: sendToLigronMutation,
    deleteOrder: deleteOrderMutation,

    // Статусы
    isWorking:
      updateMutation.isPending ||
      uploadFilesMutation.isPending ||
      deleteFileMutation.isPending ||
      sendToLigronMutation.isPending ||
      deleteOrderMutation.isPending
  };
}