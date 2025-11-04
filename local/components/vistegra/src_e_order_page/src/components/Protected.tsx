import { Navigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import React from "react";

export default function Protected({ children }: { children: React.ReactNode }) {
  const { user } = useAuthStore();
  return user ? <>{children}</> : <Navigate to="/login" replace />;
}