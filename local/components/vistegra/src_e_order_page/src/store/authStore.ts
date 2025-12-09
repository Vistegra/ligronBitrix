import { create } from "zustand";
import { safeStorage } from "@/helpers/storage.ts";
import type { User } from "@/types/user";

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

    set({ user: userWithoutDetailed, token });
  },

  updateUserDetailed: (detailed) => {
    set((state) => {
      if (!state.user) return state;

      const updatedUser = { ...state.user, detailed } as User;

      //safeStorage.set("auth_user", updatedUser);

      return { user: updatedUser };
    });
  },

  logout: () => {
    safeStorage.remove("auth_user");
    safeStorage.remove("auth_token");
    set({ user: null, token: null });
  },
}));