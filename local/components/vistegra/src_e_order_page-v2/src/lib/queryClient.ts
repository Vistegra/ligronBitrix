import { QueryClient } from "@tanstack/react-query";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error: any) => {
        // Не повторять при ошибках авторизации/доступа
        if ([404, 403, 401].includes(error?.status || error?.response?.status)) return false;
        return failureCount < 3;
      },
      refetchOnWindowFocus: true,
    },
  },
});