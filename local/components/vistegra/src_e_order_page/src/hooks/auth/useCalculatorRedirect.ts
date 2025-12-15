import { useState } from "react";
import { toast } from "sonner";
import { authApi } from "@/api/authApi";

export function useCalculatorRedirect() {
  const [isLoading, setIsLoading] = useState(false);

  const openCalculator = async () => {
    try {
      setIsLoading(true);
      const response = await authApi.getCalculatorLink();

      if (response.data?.url) {
        window.open(response.data.url, "_blank");
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

  return { openCalculator, isLoading };
}