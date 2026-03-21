import { create } from "zustand";

type Breakpoint = "xs" | "sm" | "md" | "lg" | "xl" | "2xl";

interface ScreenState {
  width: number;
  height: number;
  breakpoint: Breakpoint;
  isMobile: boolean;
  isTablet: boolean;
  isDesktop: boolean;
  update: (width: number, height: number) => void;
}

const breakpoints: Record<Breakpoint, number> = {
  xs: 0,
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
  "2xl": 1536,
};

const getBreakpoint = (width: number): Breakpoint => {
  if (width >= breakpoints["2xl"]) return "2xl";
  if (width >= breakpoints.xl) return "xl";
  if (width >= breakpoints.lg) return "lg";
  if (width >= breakpoints.md) return "md";
  if (width >= breakpoints.sm) return "sm";
  return "xs";
};

export const useScreen = create<ScreenState>((set) => ({
  width: typeof window !== "undefined" ? window.innerWidth : 0,
  height: typeof window !== "undefined" ? window.innerHeight : 0,
  breakpoint: "md",
  isMobile: false,
  isTablet: false,
  isDesktop: true,

  update: (width, height) => {
    console.log(width, height)

    const bp = getBreakpoint(width);
    set({
      width,
      height,
      breakpoint: bp,
      isMobile: bp === "xs" || bp === "sm",
      isTablet: bp === "md",
      isDesktop: bp === "lg" || bp === "xl" || bp === "2xl",
    });
  },
}));