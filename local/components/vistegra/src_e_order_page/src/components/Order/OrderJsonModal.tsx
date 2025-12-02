import {useState} from "react";
import {useQuery} from "@tanstack/react-query";
import {orderApi} from "@/api/orderApi.ts";
import {Loader2, AlertCircle, Copy, Info} from "lucide-react";
import {Button} from "@/components/ui/button.tsx";
import {toast} from "sonner";
import {Alert, AlertDescription} from "@/components/ui/alert.tsx";
import {Modal} from "@/components/Modal.tsx";

interface OrderJsonModalProps {
  orderId: number;
  className?: string;
}

export function OrderJsonModal({orderId, className}: OrderJsonModalProps) {
  const [open, setOpen] = useState(false);

  const {data, isLoading, error} = useQuery({
    queryKey: ["order", "json-preview", orderId],
    queryFn: () => orderApi.getLigronRequestData(orderId),
    enabled: open,
    staleTime: 0,
  });

  const handleCopy = async () => {
    if (!data?.data) {
      toast.error("Нет данных для копирования");
      return;
    }

    try {
      const jsonString = JSON.stringify(data.data, null, 2);
      await navigator.clipboard.writeText(jsonString); // Ожидаем Promise
      toast.success("JSON скопирован");
    } catch (err) {
      toast.error("Не удалось скопировать данные");
    }
  };

  return (
    <Modal
      open={open}
      onOpenChange={setOpen}
      title="Данные для отправки в Лигрон"
      description="Предпросмотр JSON структуры."
      trigger={
        <Button
          variant="ghost"
          size="icon"
          type="button"
          className={className}
          title="Показать JSON"
        >
          <Info className="h-5 w-5 text-muted-foreground"/>
        </Button>
      }
    >
      <div className="flex flex-col gap-4">

        <div className="relative border rounded-md bg-muted/30 overflow-hidden">
          {isLoading ? (
            <div className="flex h-[300px] items-center justify-center flex-col gap-2 text-muted-foreground">
              <Loader2 className="h-8 w-8 animate-spin"/>
              <span>Загрузка данных...</span>
            </div>
          ) : error ? (
            <div className="p-4 h-[300px] flex items-center justify-center">
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4"/>
                <AlertDescription>
                  {(error as Error).message || "Ошибка при загрузке JSON"}
                </AlertDescription>
              </Alert>
            </div>
          ) : (
            <div className="h-[50vh] overflow-auto p-4 text-xs font-mono">
              <pre>{JSON.stringify(data?.data, null, 2)}</pre>
            </div>
          )}
        </div>


        <div className="flex justify-end gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={handleCopy}
            disabled={isLoading || !!error}
          >
            <Copy className="h-4 w-4 mr-2"/>
            Копировать
          </Button>
          <Button variant="secondary" size="sm" onClick={() => setOpen(false)}>
            Закрыть
          </Button>
        </div>

      </div>
    </Modal>
  );
}