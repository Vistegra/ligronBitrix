import { Navigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import {type JSX} from "react";
import {PAGE} from "@/api/constants.ts";

export default function Protected({ children }: { children: JSX.Element }) {
  const { token, user } = useAuthStore();

  if (token && user) {
    return children;
  }

  return <Navigate to={PAGE.LOGIN} replace />;
}