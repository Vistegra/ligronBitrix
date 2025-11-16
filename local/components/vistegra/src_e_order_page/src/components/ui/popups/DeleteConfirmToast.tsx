import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Trash2 } from "lucide-react";

interface DeleteConfirmToastProps {
  title?: string;
  description?: string;
  onConfirm: () => Promise<void> | void;
  onCancel?: () => void;
}

export function showDeleteConfirmToast({
                                         title = "Удалить?",
                                         description = "Это действие нельзя отменить.",
                                         onConfirm,
                                         onCancel,
                                       }: DeleteConfirmToastProps) {
  const toastId = toast(
    <div className="flex flex-col gap-3 p-1 w-80">
      <div className="flex items-center gap-2">
        <Trash2 className="h-4 w-4 text-destructive" />
        <span className="font-medium">{title}</span>
      </div>
      <p className="text-sm text-muted-foreground">{description}</p>
      <div className="flex gap-2 justify-end">
        <Button
          size="sm"
          variant="outline"
          onClick={() => {
            toast.dismiss(toastId);
            onCancel?.();
          }}
        >
          Отмена
        </Button>
        <Button
          size="sm"
          variant="destructive"
          onClick={async () => {
            toast.dismiss(toastId);
            try {
              await onConfirm();
            } catch (err) {
              // Ошибка уже обработана в onConfirm
            }
          }}
        >
          Удалить
        </Button>
      </div>
    </div>,
    {
      duration: 15000,
      position: "bottom-right",
    }
  );

  return toastId;
}