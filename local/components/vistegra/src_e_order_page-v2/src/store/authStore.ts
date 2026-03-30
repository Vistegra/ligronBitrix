import { create } from "zustand";
import { safeStorage } from "@/helpers/storage.ts";
import type { User } from "@/types/user";
import { queryClient } from "@/lib/queryClient";
import { useContextStore } from "@/store/contextStore";

type AuthState = {
  user: User | null;
  token: string | null;

  login: (data: { user: User; token: string }) => void;
  updateUserDetailed: (detailed: User["detailed"]) => void;
  logout: () => void;
};

export const useAuthStore = create<AuthState>((set, get) => ({
  user: safeStorage.get("auth_user") || null,
  token: safeStorage.get("auth_token") || null,

  login: ({ user, token }) => {
    if (!user || !token) {
      get().logout();
      return;
    }

    const userWithoutDetailed = { ...user, detailed: undefined };

    safeStorage.set("auth_user", userWithoutDetailed);
    safeStorage.set("auth_token", token);

    queryClient.clear();
    set({ user: userWithoutDetailed, token });
  },

  updateUserDetailed: (detailed) => {
    set((state) => {
      if (!state.user) return state;

      const updatedUser = { ...state.user, detailed } as User;

      safeStorage.set("auth_user", updatedUser);

      return { user: updatedUser };
    });
  },

  logout: () => {
    safeStorage.remove("auth_user");
    safeStorage.remove("auth_token");

    // Очищаем контекст (ИНН и салон)
    useContextStore.getState()._clear();

    queryClient.clear();
    set({ user: null, token: null });
  },
}));

window.addEventListener("storage", (event) => {
  if (event.key === "auth_user" || event.key === "auth_token") {
    const newUser = safeStorage.get("auth_user") || null;
    const newToken = safeStorage.get("auth_token") || null;
    const oldToken = useAuthStore.getState().token;

    if (oldToken !== newToken) {
      queryClient.clear();
      useAuthStore.setState({ user: newUser, token: newToken });
    } else {
      useAuthStore.setState({ user: newUser });
    }
  }
});