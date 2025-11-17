import {Routes, Route} from "react-router-dom";
import LoginPage from "./pages/LoginPage";
import OrdersPage from "./pages/OrdersPage";
import OrderDetailPage from "./pages/OrderDetailPage";
import Layout from "./components/Layout";
import Protected from "./components/Protected";

import './App.css'
import './index.css'
import {PAGE} from "@/api/constants.ts";
import {Toaster} from "sonner";
import {useCheckAuth} from "@/hooks/useCheckAuth.ts";
import ScreenProvider from "@/components/ScreenProvider.tsx";

export default function App() {

  useCheckAuth();

  return (
    <ScreenProvider>
      <div id="app-container-e-order">
        <Routes>
          <Route path={PAGE.LOGIN} element={<LoginPage/>}/>
          <Route
            element={
              <Protected>
                <Layout/>
              </Protected>
            }
          >
            <Route path={PAGE.ORDERS} element={<OrdersPage/>}/>
            <Route path={PAGE.ORDER_DETAIL} element={<OrderDetailPage/>}/>
            <Route path="/" element={<OrdersPage/>}/>

            <Route path="*" element={<div>404 — Страница не найдена</div>} />
          </Route>
        </Routes>
        <Toaster/>
      </div>
    </ScreenProvider>
  );
}

