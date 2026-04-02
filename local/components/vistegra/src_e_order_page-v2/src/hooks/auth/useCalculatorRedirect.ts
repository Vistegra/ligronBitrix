import {useState} from "react";
import {toast} from "sonner";
import {authApi, type SsoLinkParams} from "@/api/authApi";
import {useContextStore} from "@/store/contextStore";

export function useCalculatorRedirect() {
  const [isLoading, setIsLoading] = useState(false);
  const {inn, salonCode} = useContextStore();

  const openCalculatorWithConfirm = async (ligron_number: string | null = null) => {
    setIsLoading(true);

    const newWindow = window.open('about:blank', '_blank');

    try {
      // Собираем объект параметров (передаем ligron_number, ИНН и салон)
      const params: SsoLinkParams = {
        ligron_number: ligron_number,
        inn_dealer: inn,
        salon_code: salonCode
      };

      const response = await authApi.getCalculatorLink(params);

      if (response.data?.url && newWindow) {
        newWindow.opener = null;
        newWindow.location.href = response.data.url;
      } else {
        newWindow?.close();
        toast.error("Не удалось получить ссылку для входа");
      }
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (error: any) {
      newWindow?.close();
      toast.error("Ошибка при переходе в калькулятор");
    } finally {
      setIsLoading(false);
    }
  };

  return {isLoading, onConfirm: openCalculatorWithConfirm};
}