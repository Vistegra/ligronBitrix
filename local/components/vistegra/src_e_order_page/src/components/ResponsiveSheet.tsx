"use client";

import * as React from "react";
import {useIsMobile} from "@/hooks/use-mobile";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerHeader,
  DrawerTitle,
  DrawerTrigger,
} from "@/components/ui/drawer";
import {cn} from "@/lib/utils";

interface ResponsiveSheetProps {
  children: React.ReactNode;
  trigger?: React.ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  title?: string;
  description?: string;
  className?: string;
  headerAction?: React.ReactNode;
}

export function ResponsiveSheet({
                                  children,
                                  trigger,
                                  open,
                                  onOpenChange,
                                  title,
                                  description,
                                  className,
                                  headerAction,
                                }: ResponsiveSheetProps) {
  const isMobile = useIsMobile();

  // Для мобильной версии
  if (isMobile) {
    return (
      <Drawer open={open} onOpenChange={onOpenChange}>
        {trigger && <DrawerTrigger asChild>{trigger}</DrawerTrigger>}

        <DrawerContent className="max-h-[90vh] flex flex-col">
          <DrawerHeader className="px-6 border-b pb-4 text-left">

            <div className="flex items-center justify-between gap-2">
              {title && <DrawerTitle>{title}</DrawerTitle>}
              {headerAction}
            </div>

            <DrawerDescription className={cn(!description && "sr-only")}>
              {description || "Dialog content"}
            </DrawerDescription>
          </DrawerHeader>

          <div className={cn("flex-1 overflow-y-auto px-4 py-6", className)}>
            {children}
          </div>
        </DrawerContent>
      </Drawer>
    );
  }

  // Для десктопной версии
  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      {trigger && <SheetTrigger asChild>{trigger}</SheetTrigger>}

      <SheetContent side="right" className="sm:max-w-2xl w-full flex flex-col h-full p-0">
        <SheetHeader className="px-6 py-6 border-b space-y-0">

          <div className="flex items-center justify-between gap-2 mr-6">
            {title && <SheetTitle>{title}</SheetTitle>}
            {headerAction}
          </div>

          <SheetDescription className={cn(!description && "sr-only")}>
            {description || "Dialog content"}
          </SheetDescription>
        </SheetHeader>

        <div className="flex-1 overflow-y-auto px-6 py-6">
          <div className={className}>
            {children}
          </div>
        </div>
      </SheetContent>
    </Sheet>
  );
}