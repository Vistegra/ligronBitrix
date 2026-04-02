import {useState} from "react";
import {toast} from "sonner";
import {authApi} from "@/api/authApi";
import {useContextStore} from "@/store/contextStore";

export function useCalculatorRedirect() {
  const [isLoading, setIsLoading] = useState(false);
  const {inn, salonCode} = useContextStore();

  const openCalculatorWithConfirm = async (ligron_number: string | null = null) => {
    setIsLoading(true);

    // Сразу открываем пустую вкладку ДО асинхронного запроса
    const newWindow = window.open('about:blank', '_blank', 'noopener,noreferrer');

    try {
      const params = new URLSearchParams();
      if (ligron_number) {
        params.set('ligron_number', ligron_number);
      } else {
        if (inn) params.set('inn_dealer', inn);
        if (salonCode) params.set('salon_code', salonCode);
      }

      const response = await authApi.getCalculatorLink(params.toString());

      if (response.data?.url && newWindow) {
        // Подменяем URL в открытой вкладке
        newWindow.location.href = response.data.url;
      } else {
        newWindow?.close();
        toast.error("Не удалось получить ссылку для входа");
      }
    } catch (error: any) {
      newWindow?.close();
      toast.error("Ошибка при переходе в калькулятор");
    } finally {
      setIsLoading(false);
    }
  };

  return {isLoading, onConfirm: openCalculatorWithConfirm};
}