import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Button } from "@/components/ui/button";
import {AlertCircle, Loader2} from "lucide-react";
import { useState } from "react";
import * as React from "react";

interface ConfirmPopoverProps {
  title: string;
  description?: string;
  confirmText?: string;
  cancelText?: string;
  confirmVariant?:
    | "default"
    | "destructive"
    | "outline"
    | "secondary"
    | "ghost"
    | "link";
  icon?: React.ReactNode;
  onConfirm: () => Promise<void> | void;
  children: React.ReactNode;
  side?: "top" | "bottom" | "left" | "right";
  align?: "start" | "center" | "end";
}

export function ConfirmPopover({
                                 title,
                                 description,
                                 confirmText = "Подтвердить",
                                 cancelText = "Отмена",
                                 confirmVariant = "destructive",
                                 icon = <AlertCircle className="h-8 w-8 text-destructive" />,
                                 onConfirm,
                                 children,
                                 side = "top",
                                 align = "center",
                               }: ConfirmPopoverProps) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleConfirm = async () => {
    setLoading(true);
    try {
      await onConfirm();
      setOpen(false);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>{children}</PopoverTrigger>
      <PopoverContent
        side={side}
        align={align}
        className="w-80 p-4 space-y-3"
        onClick={(e) => e.stopPropagation()}
        sideOffset={8}
      >
        {/* Заголовок с иконкой */}
        <div className="flex items-center gap-2">
          {icon}
          <span className="text-sm font-medium">{title}</span>
        </div>

        {/* Описание */}
        {description && (
          <p className="text-xs text-muted-foreground">{description}</p>
        )}

        {/* Кнопки */}
        <div className="flex gap-2 justify-end">
          <Button
            size="sm"
            variant="outline"
            onClick={() => setOpen(false)}
            disabled={loading}
          >
            {cancelText}
          </Button>
          <Button
            size="sm"
            variant={confirmVariant}
            onClick={handleConfirm}
            disabled={loading}
          >
            {loading && <Loader2 className="h-3 w-3 animate-spin mr-1.5" />}
            {confirmText}
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}