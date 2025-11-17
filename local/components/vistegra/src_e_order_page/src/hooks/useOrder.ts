import { useState, useCallback, useEffect } from "react";
import { orderApi, type Order, type OrderFile } from "@/api/orderApi";
import { toast } from "sonner";

export function useOrder(id: number) {
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
      setError(err.response?.data?.message || "Ошибка загрузки заказа");
    } finally {
      setLoading(false);
    }
  }, [id]);

  const updateComment = async (comment?: string) => {
    const res = await orderApi.updateOrder(id, { comment });
    if (res.status === "success") {
      setOrder(res.data.order);
      return true;
    }
    return false;
  };

  const uploadFiles = async (filesToUpload: File[]) => {
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
    await orderApi.deleteFile(id, fileId);
    setFiles(prev => prev.filter(f => f.id !== fileId));
    toast.success("Файл удалён");
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
  };
}