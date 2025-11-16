import {useState, useCallback, useEffect} from "react";
import { orderApi, type Order } from "@/api/orderApi";


export function useOrder(id: number) {
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchOrder = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await orderApi.getOrder(id);
      if (res.status === "success") {
        setOrder(res.data.order);
      } else {
        setError(res.message);
      }
    } catch (err: any) {
      const msg = err.response?.data?.message || err.message || "Ошибка загрузки заказа";
      setError(msg);
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

  const uploadFiles = async (files: File[]) => {
    const res = await orderApi.uploadFiles(id, files);
    if (res.status === "success" || res.status === "partial") {
      await fetchOrder();

      return res.data.files;
    }
    throw new Error(res.message);
  };

  const deleteFile = async (fileId: number) => {
    await orderApi.deleteFile(id, fileId);
    // Локально удаляем файл из состояния без перезагрузки всего заказа
    setOrder(prev => {
      if (!prev) return prev;
      return {
        ...prev,
        files: prev.files?.filter(file => file.id !== fileId) || []
      };
    });
  };

  useEffect(() => {
    if (id > 0) {
      fetchOrder();
    }
  }, [id, fetchOrder]);

  return {
    order,
    loading,
    error,
    fetchOrder,
    updateComment,
    uploadFiles,
    deleteFile,
  };
}