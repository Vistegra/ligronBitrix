import {StrictMode} from 'react'
import ReactDOM from "react-dom/client";
import {HashRouter} from "react-router-dom";
import App from './App.tsx'

import './index.css'

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
      <HashRouter
        future={{
          v7_startTransition: true,
          v7_relativeSplatPath: true,
        }}
      >
        <App />
      </HashRouter>
    </StrictMode>
  );
}, 100)


