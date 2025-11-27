import {useEffect, useCallback, useState} from "react";
import {useQueryClient} from "@tanstack/react-query";
import {useAuthStore} from "@/store/authStore";
import {useAuthNavigation} from "./useAuthNavigation";
import {PAGE} from "@/api/constants";

interface UseAuthGuardProps {
  isTokenProcessing: boolean;
  isProfileLoading: boolean;
}

export function useAuthGuard({isTokenProcessing, isProfileLoading}: UseAuthGuardProps) {
  const {token, logout: storeLogout} = useAuthStore();
  const queryClient = useQueryClient();

  const {navigateToLogin, navigateToApp, location} = useAuthNavigation();

  const [isChecking, setIsChecking] = useState(true);

  useEffect(() => {
    if (isTokenProcessing) return;

    let mounted = true;

    const guard = () => {
      if (!token) {
        navigateToLogin();
        if (mounted) setIsChecking(false);
        return;
      }

      if (isProfileLoading) {
        return;
      }

      if (location.pathname === PAGE.LOGIN) {
        navigateToApp();
      }

      if (mounted) setIsChecking(false);
    };

    guard();

    return () => {
      mounted = false;
    };
  }, [
    token,
    isTokenProcessing,
    isProfileLoading,
    location.pathname,
    navigateToLogin,
    navigateToApp
  ]);

  const logout = useCallback(() => {
    storeLogout();
    queryClient.clear();
    navigateToLogin();
  }, [storeLogout, queryClient, navigateToLogin]);

  return {
    logout,
    isGuardLoading: isChecking
  };
}