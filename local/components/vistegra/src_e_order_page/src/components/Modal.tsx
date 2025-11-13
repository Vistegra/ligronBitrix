"use client";

import {
  Dialog,
  DialogContent,
  DialogTrigger,
} from "@/components/ui/dialog";
import type {ReactNode} from "react";

interface ModalProps {
  trigger: ReactNode;
  children: ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
}

export function Modal({ trigger, children, open, onOpenChange }: ModalProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogTrigger asChild>
        {trigger}
      </DialogTrigger>
      <DialogContent className="max-w-2xl w-full max-h-[90vh] overflow-y-auto p-0">
        <div className="p-6">
          {children}
        </div>
      </DialogContent>
    </Dialog>
  );
}