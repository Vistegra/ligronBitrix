import { useTokenAuth } from "./useTokenAuth";
import { useAuthLogin } from "./useAuthLogin";
import { useProfileSync } from "./useProfileSync";
import { useAuthGuard } from "./useAuthGuard";
import { useAuthStore } from "@/store/authStore";

export function useAuth() {
  // 1. Обработка URL ?ut=...
  const { isTokenProcessing } = useTokenAuth();

  // 2. Синхронизация данных профиля (зависит от токена)
  const { isProfileLoading } = useProfileSync({ isTokenProcessing });

  // 3. Защита роутов и редиректы
  const { logout, isGuardLoading } = useAuthGuard({
    isTokenProcessing,
    isProfileLoading
  });

  // 4. Логика ручного входа
  const { login, loginError, resetError } = useAuthLogin();


  const { user } = useAuthStore();

  const isLoading = isTokenProcessing || isGuardLoading || isProfileLoading;

  return {
    login,
    logout,
    user,
    isLoading,
    error: loginError,
    clearError: resetError,
  };
}