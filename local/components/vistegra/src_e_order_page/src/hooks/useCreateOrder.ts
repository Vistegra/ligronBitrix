import { useState } from "react";
import { orderApi, type CreateOrderData, type OrderResponse } from "@/api/orderApi";
import type { ApiResponse, SuccessApiResponse, PartialSuccessApiResponse } from "@/api/client";

type UseCreateOrderReturn = {
  createOrder: (data: CreateOrderData) => Promise<OrderResponse>;
  isSubmitting: boolean;
  error: string | null;
  clearError: () => void;
  success: boolean;
  reset: () => void;
  createdOrder: OrderResponse | null;
};

export function useCreateOrder(): UseCreateOrderReturn {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [createdOrder, setCreatedOrder] = useState<OrderResponse | null>(null);

  const createOrder = async (data: CreateOrderData): Promise<OrderResponse> => {
    setIsSubmitting(true);
    setError(null);
    setSuccess(false);
    setCreatedOrder(null);

    try {
      const response: ApiResponse<OrderResponse> = await orderApi.createOrder(data);

      if (response.status === "success" || response.status === "partial") {
        const successResponse = response as SuccessApiResponse<OrderResponse> | PartialSuccessApiResponse<OrderResponse>;

        const result = successResponse.data;
        setCreatedOrder(result);
        setSuccess(true);

        if (response.status === "partial") {
          console.warn("Частичный успех:", response.message);
        }

        return result;
      }

      if (response.status === "error") {
        const errorMessage = response.message || "Ошибка при создании заказа";
        setError(errorMessage);
        throw new Error(errorMessage);
      }

      throw new Error("Неизвестный статус ответа сервера");
    } catch (err: any) {
      const message =
        err.response?.data?.message ||
        err.message ||
        "Неизвестная ошибка при создании заказа";

      setError(message);
      throw err;
    } finally {
      setIsSubmitting(false);
    }
  };

  const clearError = () => setError(null);

  const reset = () => {
    setError(null);
    setSuccess(false);
    setIsSubmitting(false);
    setCreatedOrder(null);
  };

  return {
    createOrder,
    isSubmitting,
    error,
    clearError,
    success,
    reset,
    createdOrder,
  };
}