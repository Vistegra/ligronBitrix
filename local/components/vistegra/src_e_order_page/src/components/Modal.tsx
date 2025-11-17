"use client";

import {
  Dialog,
  DialogContent,
  DialogTrigger,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import type {ReactNode} from "react";

interface ModalProps {
  trigger: ReactNode;
  children: ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  title?: string;
  description?: string;
}

export function Modal({
                        trigger,
                        children,
                        open,
                        onOpenChange,
                        title = "",
                        description = ""
                      }: ModalProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogTrigger asChild>
        {trigger}
      </DialogTrigger>
      <DialogContent
        className="max-w-2xl w-full max-h-[90vh] overflow-y-auto bg-white p-5 border-none"
      >
          <DialogTitle className="">{title}</DialogTitle>

          <DialogDescription className="">
            {description}
          </DialogDescription>

        <div className="bg-white rounded-lg">
          {children}
        </div>
      </DialogContent>
    </Dialog>
  );
}