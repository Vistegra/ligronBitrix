import { useState, useCallback, useEffect } from "react";
import { orderApi, type Order, type OrderFile } from "@/api/orderApi";
import { toast } from "sonner";

export function useOrder(id: number, isDraft: boolean) {
  const [order, setOrder] = useState<Order | null>(null);
  const [files, setFiles] = useState<OrderFile[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchOrder = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const res = await orderApi.getOrder(id);
      if (res.status === "success") {
        setOrder(res.data.order);
        setFiles(res.data.files || []);
      } else {
        setError(res.message);
      }
    } catch (err: any) {
      setError(err.response?.data?.message || err.message || "Ошибка загрузки заказа");
    } finally {
      setLoading(false);
    }
  }, [id]);

  const updateComment = async (comment?: string) => {
    try {

      if (!isDraft) {
        throw new Error('Функционал временно недоступен')
      }

      const res = await orderApi.updateOrder(id, { comment });
      if (res.status === "success") {
        setOrder(res.data.order);
        toast.success("Описание успешно обновлено");
      }
    } catch (err: any) {
      toast.error(err.response?.data?.message || err.message || 'Ошибка обвновления комментария');
    }

  };

  const uploadFiles = async (filesToUpload: File[]) => {
    if (!isDraft) {
      throw new Error('Функционал временно недоступен')
    }

    const res = await orderApi.uploadFiles(id, filesToUpload);

    if (res.status === "success" || res.status === "partial") {
      const newFiles = res.data.files || [];
      setFiles(prev => [...prev, ...newFiles]);

      if (res.status === "partial") {
        toast.warning(res.message); //ToDO подумать как выводить в компоненте
      }

      return newFiles;
    }

    toast.error(res.message || "Ошибка загрузки файлов");
    throw new Error(res.message);
  };

  const deleteFile = async (fileId: number) => {
    try {
      if (!isDraft) {
        throw new Error('Функционал временно недоступен')
      }

      await orderApi.deleteFile(id, fileId);
      setFiles(prev => prev.filter(f => f.id !== fileId));
      toast.success("Файл удалён");
    } catch (err: any) {
      toast.error(err.response?.data?.message || err.message || 'Ошибка удаления файла')
    }

  };

  const sendToLigron = async (): Promise<Order | null> => {
    if (!isDraft) {
      toast.error("Можно отправлять только черновики");
      return null;
    }

    try {
      const res = await orderApi.sendToLigron(id);

      if (res.status === "success") {
        toast.success("Заказ успешно отправлен в Лигрон");

        // Обновляем локальное состояние — заказ теперь не черновик
        if (res.data?.order) {
          setOrder(res.data.order);
        }

        return res.data?.order || order;
      } else {
        toast.error(res.message || "Не удалось отправить заказ");
        return null;
      }
    } catch (err: any) {
      const message =
        err.response?.data?.message ||
        err.message ||
        "Ошибка при отправке заказа в Лигрон";

      toast.error(message);
      console.error("[sendToLigron] error:", err);
      return null;
    }
  };

  useEffect(() => {
    if (id > 0) fetchOrder();
  }, [id, fetchOrder]);

  return {
    order,
    files,
    loading,
    error,
    fetchOrder,
    updateComment,
    uploadFiles,
    deleteFile,
    sendToLigron
  };
}