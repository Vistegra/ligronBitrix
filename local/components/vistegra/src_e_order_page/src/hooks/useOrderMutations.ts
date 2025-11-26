import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { orderApi, type CreateOrderData } from "@/api/orderApi";
import { PAGE } from "@/api/constants";

export function useOrderMutations(orderId: number, isDraft: boolean) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const invalidateOrder = () => {
    queryClient.invalidateQueries({ queryKey: ['orders', 'detail', orderId] });
    queryClient.invalidateQueries({ queryKey: ['orders', 'list'] });
  };

  // Функция guard
  const requireDraft = async () => {
    if (!isDraft) {
      // Этот текст попадет в onError и выведется в toast
      throw new Error("Функционал временно недоступен (редактирование запрещено)");
    }
  };

  // Обновление комментария/имени
  const updateMutation = useMutation({
    mutationFn: async (data: Partial<CreateOrderData>) => {
      await requireDraft(); //Временная блокировка функционала
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
      await requireDraft(); //Временная блокировка функционала
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
      await requireDraft(); //Временная блокировка функционала
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
      // Отправлять можно только черновики
      await requireDraft();
      return orderApi.sendToLigron(orderId);
    },
    onSuccess: (data) => {
      toast.success("Заказ успешно отправлен в Лигрон");
      if (data.data?.order?.id) {
        navigate(PAGE.orderDetail(data.data.order.id));
        queryClient.invalidateQueries({ queryKey: ['orders'] });
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
      queryClient.invalidateQueries({ queryKey: ['orders'] });
      navigate(PAGE.ORDERS);
    },
    onError: (err) => toast.error(err.message)
  });

  return {
    update: updateMutation,
    uploadFiles: uploadFilesMutation,
    deleteFile: deleteFileMutation,
    sendToLigron: sendToLigronMutation,
    deleteOrder: deleteOrderMutation,

    isWorking:
      updateMutation.isPending ||
      uploadFilesMutation.isPending ||
      deleteFileMutation.isPending ||
      sendToLigronMutation.isPending ||
      deleteOrderMutation.isPending
  };
}