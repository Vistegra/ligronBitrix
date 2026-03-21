import {toast} from "sonner";
import {Button} from "@/components/ui/button";
import {Trash2} from "lucide-react";

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
    <div className="flex flex-col gap-3 w-full sm:w-80">
      <div className="flex items-center gap-2">

        <Trash2 className="h-4 w-4 text-destructive shrink-0"/>
        <span className="font-medium">{title}</span>
      </div>
      <p className="text-sm text-muted-foreground">{description}</p>

      {/* Кнопки */}
      <div className="flex gap-2 justify-end mt-1">
        <Button
          size="sm"
          variant="outline"
          onClick={() => {
            toast.dismiss(toastId);
            onCancel?.();
          }}
          className="flex-1 sm:flex-none"
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
              // Ошибка уже обработана
            }
          }}
          className="flex-1 sm:flex-none"
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