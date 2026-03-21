"use client";

import { useEffect } from "react";
import { useScreen } from "@/store/screenStore";
import type { ReactNode } from "react";
import { useDebounce } from "@/hooks/useDebounce";

export default function ScreenProvider({ children }: { children: ReactNode }) {
  const { update } = useScreen();

  const debouncedUpdate = useDebounce((width: number, height: number) => {
    update(width, height);
  }, 300);

  useEffect(() => {
    const handleResize = () => {
      debouncedUpdate(window.innerWidth, window.innerHeight);
    };

    handleResize();
    window.addEventListener("resize", handleResize);

    return () => {
      window.removeEventListener("resize", handleResize);
    };
  }, [debouncedUpdate]);

  return <>{children}</>;
}