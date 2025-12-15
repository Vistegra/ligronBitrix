import {StrictMode} from 'react'
import ReactDOM from "react-dom/client";
import {BrowserRouter} from "react-router-dom";
import App from './App.tsx'
import {QueryClient, QueryClientProvider} from "@tanstack/react-query";

import './index.css'

// Импорт регистрации PWA
// @ts-ignore
import { registerSW } from 'virtual:pwa-register'

// Интервал проверки обновлений (например, каждый час)
const updateSW = registerSW({
  onNeedRefresh() {
    if (confirm('Доступна новая версия приложения. Обновить?')) {
      updateSW(true);
    }
  },
  onOfflineReady() {
    console.log('Приложение готово к работе офлайн');
  },
})

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error) => {
        // Не повторять ошибках
        // @ts-expect-error
        if ([404, 403, 401].includes(error?.status)) return false;
        // 3 попытки для других ошибок
        return failureCount < 3;
      },
      refetchOnWindowFocus: true,
    },
  },
});

// Ждём, пока Bitrix создаст #root
const timerId = setInterval(() => {
  const rootElem = document.getElementById("root");

  if (!rootElem) {
    console.error("Элемент #root не найден!");
    return;
  }

  clearInterval(timerId)

  ReactDOM.createRoot(rootElem as HTMLElement).render(
    <StrictMode>
      <QueryClientProvider client={queryClient}>
        <BrowserRouter
          basename={import.meta.env.BASE_URL || '/'}
          future={{
            v7_startTransition: true,
            v7_relativeSplatPath: true,
          }}
        >
          <App/>
        </BrowserRouter>
      </QueryClientProvider>
    </StrictMode>
  );
}, 100)

