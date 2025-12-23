
import { useState } from "react";
import { toast } from "sonner";
import { authApi } from "@/api/authApi";

export function useCalculatorRedirect() {
  const [isLoading, setIsLoading] = useState(false);

  const openCalculatorWithConfirm = async (ligron_number: string | null = null) => {
    setIsLoading(true);
    try {
      const response = await authApi.getCalculatorLink(ligron_number);

      if (response.data?.url) {
        window.open(response.data.url, "_blank", "noopener,noreferrer");
      } else {
        toast.error("Не удалось получить ссылку для входа");
      }
    } catch (error: any) {
      console.error(error);
      toast.error("Ошибка при переходе в калькулятор");
    } finally {
      setIsLoading(false);
    }
  };

  return {
    isLoading,
    onConfirm: openCalculatorWithConfirm,
  };
}