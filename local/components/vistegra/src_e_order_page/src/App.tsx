import {Navigate, Route, Routes} from "react-router-dom"
import LoginPage from "@/pages/LoginPage"
import OrdersPage from "@/pages/OrdersPage"
import OrderDetailPage from "@/pages/OrderDetailPage"
import {ProtectedLayout} from "@/components/ProtectedLayout"
import {PAGE} from "@/api/constants.ts"
import {Toaster} from "sonner"
import ProfilePage from "@/pages/ProfilePage";
import DraftOrdersPage from "@/pages/DraftOrdersPage";
import DraftOrderDetailPage from "@/pages/DraftOrderDetailPage";

export default function App() {

  return (

    <div id="app-container-e-order">
      <Routes>
        <Route path={PAGE.LOGIN} element={<LoginPage/>}/>

        <Route element={<ProtectedLayout/>}>
          <Route path={PAGE.ORDERS} element={<OrdersPage/>}/>
          <Route path={PAGE.DRAFTS} element={<DraftOrdersPage/>}/>
          <Route path={PAGE.REQUESTS} element={<div className="p-8">Заявки (страница в разработке)</div>}/>
          <Route path={PAGE.PROFILE} element={<ProfilePage/>}/>
          <Route path={PAGE.ORDER_DETAIL} element={<OrderDetailPage/>}/>
          <Route path={PAGE.DRAFT_DETAIL} element={<DraftOrderDetailPage/>}/>
          <Route path="/" element={<Navigate to={PAGE.ORDERS} replace/>}/>
        </Route>

        <Route path="*" element={<div className="p-8 text-center text-2xl">404 — Страница не найдена</div>}/>
      </Routes>
      <Toaster/>
    </div>

  )
}