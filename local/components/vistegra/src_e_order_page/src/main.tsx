import {StrictMode} from 'react'
import ReactDOM from "react-dom/client";
import {HashRouter} from "react-router-dom";
import App from './App.tsx'
import {QueryClient, QueryClientProvider} from "@tanstack/react-query";

import './index.css'

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
        <HashRouter
          future={{
            v7_startTransition: true,
            v7_relativeSplatPath: true,
          }}
        >
          <App/>
        </HashRouter>
      </QueryClientProvider>
    </StrictMode>
  );
}, 100)


