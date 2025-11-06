

import { Routes, Route } from "react-router-dom";
import LoginPage from "./pages/LoginPage";
import OrdersPage from "./pages/OrdersPage";
import OrderDetailPage from "./pages/OrderDetailPage";
import Layout from "./components/Layout";
import Protected from "./components/Protected";
import './App.css'
import './index.css'
import {PAGE} from "@/api/constants.ts";

export default function App() {
  return (
    <Routes>
      <Route path={PAGE.LOGIN} element={<LoginPage />} />
      <Route
        element={
          <Protected>
            <Layout />
          </Protected>
        }
      >
        <Route path={PAGE.ORDERS} element={<OrdersPage />} />
        <Route path={PAGE.ORDER_ID} element={<OrderDetailPage />} />
        <Route path="/" element={<OrdersPage />} />
      </Route>
    </Routes>
  );
}

