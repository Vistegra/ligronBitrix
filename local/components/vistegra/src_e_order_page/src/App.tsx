import { Routes, Route } from "react-router-dom"
import LoginPage from "./pages/LoginPage"
import OrdersPage from "./pages/OrdersPage"
import OrderDetailPage from "./pages/OrderDetailPage"
import ProtectedLayout from "./components/ProtectedLayout"
import { PAGE } from "@/api/constants.ts"
import { Toaster } from "sonner"
import { useCheckAuth } from "@/hooks/useCheckAuth.ts"
import ScreenProvider from "@/components/ScreenProvider.tsx"
import ProfilePage from "@/pages/ProfilePage.tsx";

export default function App() {
  useCheckAuth()

  return (
    <ScreenProvider>
      <div id="app-container-e-order">
        <Routes>
          <Route path={PAGE.LOGIN} element={<LoginPage />} />

          <Route element={<ProtectedLayout />}>
            <Route path={PAGE.ORDERS} element={<OrdersPage />} />
            <Route path={PAGE.ORDERS} element={<OrdersPage />} />
            <Route path={PAGE.ORDERS_CANCELED} element={<OrdersPage />} />
            <Route path={PAGE.DRAFTS} element={<div>Черновики (страница в разработке)</div>} />
            <Route path={PAGE.PROFILE} element={<ProfilePage />} />
            <Route path={PAGE.ORDER_DETAIL} element={<OrderDetailPage />} />
            <Route path="/" element={<OrdersPage />} />
          </Route>

          <Route path="*" element={<div className="p-8 text-center text-2xl">404 — Страница не найдена</div>} />
        </Routes>
        <Toaster />
      </div>
    </ScreenProvider>
  )
}