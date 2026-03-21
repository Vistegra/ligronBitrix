import { create } from "zustand";

interface BreadcrumbState {
  orderNumber: string | null;
  setOrderNumber: (number: string | null) => void;
}

export const useBreadcrumbStore = create<BreadcrumbState>((set) => ({
  orderNumber: null,
  setOrderNumber: (number) => set({ orderNumber: number }),
}));