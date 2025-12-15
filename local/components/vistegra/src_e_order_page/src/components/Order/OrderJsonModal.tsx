import {useState} from "react";
import {useQuery} from "@tanstack/react-query";
import {orderApi} from "@/api/orderApi.ts";
import {AlertCircle, Copy, DownloadIcon, Info, Loader2} from "lucide-react";
import {Button} from "@/components/ui/button";
import {toast} from "sonner";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {ResponsiveSheet} from "../ResponsiveSheet";

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

  const handleDownload = () => {
    if (!data?.data) {
      toast.error("Нет данных для скачивания");
      return;
    }

    try {

      const jsonString = JSON.stringify(data.data, null, 2);
      const blob = new Blob([jsonString], {type: "application/json"});
      const url = URL.createObjectURL(blob);

      const fileName = `${data.data.order_number || `order_${orderId}`}.json`;

      const link = document.createElement("a");
      link.href = url;
      link.download = fileName;
      document.body.appendChild(link);
      link.click();

      document.body.removeChild(link);
      URL.revokeObjectURL(url);

      toast.success(`Файл ${fileName} скачан`);
    } catch (err) {
      console.error(err);
      toast.error("Ошибка при скачивании файла");
    }
  };

  return (

    <ResponsiveSheet
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
      <div className="flex flex-col h-full gap-4 pb-safe"> {/* pb-safe для iPhone */}

        {/* Контент: JSON превью */}
        <div
          className="relative border rounded-md bg-muted/30 overflow-hidden flex-1 min-h-0">
          {isLoading ? (
            <div className="flex h-full items-center justify-center flex-col gap-2 text-muted-foreground p-8">
              <Loader2 className="h-8 w-8 animate-spin"/>
              <span>Загрузка данных...</span>
            </div>
          ) : error ? (
            <div className="p-4 h-full flex items-center justify-center">
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4"/>
                <AlertDescription>
                  {(error as Error).message || "Ошибка при загрузке JSON"}
                </AlertDescription>
              </Alert>
            </div>
          ) : (
            <div className="h-full overflow-auto p-4 text-xs font-mono">
              <pre>{JSON.stringify(data?.data, null, 2)}</pre>
            </div>
          )}
        </div>

        {/* Футер с кнопками */}
        <div className="flex justify-end gap-2 pt-2 border-t mt-auto">
          <Button
            variant="default"
            size="sm"
            onClick={handleDownload}
            disabled={isLoading || !!error}
          >
            <DownloadIcon className="h-4 w-4 mr-2"/>
            Скачать
          </Button>

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

    </ResponsiveSheet>
  );
}