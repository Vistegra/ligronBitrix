import {StrictMode} from 'react'
import ReactDOM from "react-dom/client";
import {BrowserRouter} from "react-router-dom";
import App from './App.tsx'
import {QueryClientProvider} from "@tanstack/react-query";
import {queryClient} from "@/lib/queryClient";

import './index.css'

// Импорт регистрации PWA
// @ts-ignore
import {registerSW} from 'virtual:pwa-register'

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

