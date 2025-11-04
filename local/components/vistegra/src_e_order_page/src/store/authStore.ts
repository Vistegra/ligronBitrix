import { create } from "zustand";
import {safeStorage} from "@/helpers/storage.ts";

type BaseUser = {
  id: number;
  name: string;
  login: string;
  email?: string;
  phone?: string;
};

type LigronUser = BaseUser & {
  provider: "ligron";
  role: "manager" | "office_manager";
};

type DealerUser = BaseUser & {
  dealer_id: number;
  dealer_prefix: string;
  provider: "dealer";
  role: "dealer"
};

type User = LigronUser | DealerUser;

type AuthState = {
  user: User | null;
  token: string | null;
  login: (data: { user: User; token: string }) => void;
  logout: () => void;
};

export const useAuthStore = create<AuthState>((set) => ({
  user: safeStorage.get('auth_user'),
  token: safeStorage.get('auth_token'),

  // Сеттеры
  login: ({ user, token }) => {
    if (!user || !token) {
      console.warn({user, token})

      set((state) => { state.logout();
        return { user: null, token: null };
      });

      return;
    }

    safeStorage.set("auth_user", user);
    safeStorage.set("auth_token", token);

    set({ user, token });
  },

  logout: () => {
    safeStorage.remove("auth_user");
    safeStorage.remove("auth_token");
    set({ user: null, token: null });
  },
}));