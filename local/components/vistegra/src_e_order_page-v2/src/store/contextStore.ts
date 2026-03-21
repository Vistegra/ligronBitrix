import { create } from "zustand";
import { persist } from "zustand/middleware";

interface ContextState {
  // Выбранный ИНН дилера
  inn: string | null;
  // Выбранный код салона
  salonCode: string | null;

  // Методы управления
  setContext: (inn: string | null, salonCode: string | null) => void;
  clearContext: () => void;
}

export const useContextStore = create<ContextState>()(
  persist(
    (set) => ({
      inn: null,
      salonCode: null,

      setContext: (inn, salonCode) => set({ inn, salonCode }),

      clearContext: () => set({ inn: null, salonCode: null }),
    }),
    {
      name: "order-v2-working-context", // Ключ в localStorage
    }
  )
);