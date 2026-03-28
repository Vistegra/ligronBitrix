import {useMutation} from "@tanstack/react-query";
import {toast} from "sonner";
import {queries} from "@/lib/queryFactory";

export function useCalculatorRedirect() {
  const mutation = useMutation({
    ...queries.auth.calculatorLink(),
    onSuccess: (url) => {
      window.open(url, "_blank", "noopener,noreferrer");
    },
    onError: (err) => {
      toast.error(err.message || "Ошибка при переходе в калькулятор");
    }
  });

  return {
    // Методы
    onConfirm: (ligronNumber?: string | null) => mutation.mutate(ligronNumber ?? null),

    // Статусы
    isLoading: mutation.isPending,
  };
}