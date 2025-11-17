import {Button} from "@/components/ui/button";
import {Dropzone} from "@/components/ui/shadcn-io/dropzone";
import {Item, ItemActions, ItemContent, ItemTitle, ItemDescription} from "@/components/ui/item";
import {Trash2, UploadIcon, Loader2, Download} from "lucide-react";
import {toast} from "sonner";

import {ConfirmPopover} from "@/components/ui/popups/ConfirmPopover.tsx";
import type {OrderFile} from "@/api/orderApi.ts";
import {URL_BASE} from "@/api/constants.ts";
import {useFileDropzone} from "@/hooks/useFileDropzone.ts";


const MAX_FILES = 10;
const MAX_SIZE_MB = 20;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;


type Props = {
  files: OrderFile[];
  uploading: boolean;
  onUpload: (files: File[]) => Promise<void>;
  onDelete: (id: number) => Promise<void>;
};

export function DocumentsTab({files, uploading, onUpload, onDelete}: Props) {

  const { onDropRejected, onDropError } = useFileDropzone();

  const handleDrop = async (accepted: File[]) => {
    if (accepted.length === 0) return;
    try {
      await onUpload(accepted);
      toast.success("Файлы добавлены в заказ");
    } catch {
      toast.error("Ошибка загрузки файлов");
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await onDelete(id);
      toast.success("Файл удалён");
    } catch {
      toast.error("Ошибка удаления файла");
    }
  };

  const handleDownload = async (file: OrderFile) => {
    const url = URL_BASE + file.path + file.name

    try {
      // Загружаем файл как Blob
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': '*/*',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const blob = await response.blob();
      const blobUrl = window.URL.createObjectURL(blob);

      // Создаём временную ссылку для скачивания
      const link = document.createElement('a');
      link.href = blobUrl;
      link.download = file.name || 'download';
      document.body.appendChild(link);
      link.click();

      // Очищаем
      document.body.removeChild(link);
      window.URL.revokeObjectURL(blobUrl);

      toast.success(`Скачивание "${file.name}" начато`);
    } catch (err) {
      toast.error("Ошибка при скачивании файла");
      console.error("Download error:", err);
    }
  };

  return (
    <div className="space-y-6">
      <Dropzone
        maxSize={MAX_SIZE_BYTES}
        multiple
        maxFiles={MAX_FILES}
        onDropAccepted={handleDrop}
        onDropRejected={onDropRejected}
        onError={onDropError}
        disabled={uploading}
        className="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors hover:border-primary"
      >
        <div className="flex flex-col items-center space-y-2">
          <div className="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground">
            <UploadIcon size={16}/>
          </div>
          <p className="font-medium">Перетащите файлы или кликните для выбора</p>
          <p className="text-xs text-muted-foreground">
            До 20 МБ, любые типы файлов
          </p>
        </div>
      </Dropzone>

      {uploading && (
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <Loader2 className="h-4 w-4 animate-spin"/>
          Загрузка файлов...
        </div>
      )}

      {files.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-sm font-medium">Прикреплённые файлы</h4>
          {files.map((file) => (
            <Item key={file.id} variant="outline" size="sm">
              <ItemContent>
                <ItemTitle className="text-sm">{file.name}</ItemTitle>
                {file.size !== undefined && (
                  <ItemDescription className="text-xs">
                    {(file.size / 1024).toFixed(0)} КБ
                  </ItemDescription>
                )}
              </ItemContent>
              <ItemActions>
                {/* Кнопка скачивания */}
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  onClick={() => handleDownload(file)}
                  disabled={uploading}
                  title="Скачать файл"
                >
                  <Download className="h-4 w-4"/>
                </Button>

                {/* Кнопка удаления */}
                <ConfirmPopover
                  title={`Удалить файл "${file.name}"?`}
                  description="Файл будет удалён навсегда."
                  confirmText="Удалить"
                  confirmVariant="destructive"
                  onConfirm={() => handleDelete(file.id)}
                >
                  <Button variant="ghost" size="icon" disabled={uploading}>
                    <Trash2 className="h-4 w-4"/>
                  </Button>
                </ConfirmPopover>
              </ItemActions>
            </Item>
          ))}
        </div>
      )}

      {!uploading && files.length === 0 && (
        <p className="text-sm text-muted-foreground text-center">
          Нет прикреплённых файлов
        </p>
      )}
    </div>
  );
}