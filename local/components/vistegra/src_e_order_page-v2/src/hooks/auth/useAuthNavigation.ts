import { useCallback } from "react";
import { useNavigate, useLocation } from "react-router-dom";
import { PAGE } from "@/api/constants";

export function useAuthNavigation() {
  const navigate = useNavigate();
  const location = useLocation();

  /**
   * Перенаправление на страницу входа с сохранением текущего пути (from)
   */
  const navigateToLogin = useCallback(() => {

    if (location.pathname === PAGE.LOGIN) return;

    const fullPath = location.pathname + location.search;
    navigate(PAGE.LOGIN, {
      replace: true,
      state: { from: fullPath }
    });
  }, [navigate, location.pathname, location.search]);

  /**
   * Перенаправление внутрь приложения (после успешного входа)
   */
  const navigateToApp = useCallback(() => {

    const from = location.state?.from;
    const redirectTo = from && from !== PAGE.LOGIN ? from : PAGE.ORDERS;

    navigate(redirectTo, { replace: true });
  }, [navigate, location.state?.from]);

  /**
   * Очистка конкретного параметра из URL без перезагрузки страницы
   */
  const clearUrlParam = useCallback((param: string) => {
    const newParams = new URLSearchParams(location.search);
    if (newParams.has(param)) {
      newParams.delete(param);
      navigate(
        { pathname: location.pathname, search: newParams.toString() },
        { replace: true }
      );
    }
  }, [navigate, location.pathname, location.search]);

  return {
    navigateToLogin,
    navigateToApp,
    clearUrlParam,

    location
  };
}