import { create } from "zustand";
import { persist } from "zustand/middleware";

interface ContextState {
  inn: string | null;
  salonCode: string | null;
  _set: (inn: string | null, salonCode: string | null) => void;
  _clear: () => void;
}

export const useContextStore = create<ContextState>()(
  persist(
    (set) => ({
      inn: null,
      salonCode: null,
      _set: (inn, salonCode) => set({ inn, salonCode }),
      _clear: () => set({ inn: null, salonCode: null }),
    }),
    { name: "working-context" } // Сохраняем в localStorage
  )
);