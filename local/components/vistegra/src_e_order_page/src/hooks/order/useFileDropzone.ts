import { type FileRejection } from "react-dropzone";
import { toast } from "sonner";

const MAX_SIZE_MB = 20;
const MAX_FILES = 10;

export function useFileDropzone() {
  const onDropRejected = (rejectedFiles: FileRejection[]) => {
    if (rejectedFiles.length === 0) return;

    const firstRejection = rejectedFiles[0];
    const firstError = firstRejection.errors[0];
    let message = "";

    switch (firstError.code) {
      case "file-too-large":
        message = `Файл "${firstRejection.file.name}" слишком большой. Максимум: ${MAX_SIZE_MB} МБ`;
        break;
      case "too-many-files":
        message = `Слишком много файлов. Максимум: ${MAX_FILES}`;
        break;
      case "file-invalid-type":
        message = `Недопустимый тип файла "${firstRejection.file.name}"`;
        break;
      default:
        message = `Ошибка: ${firstError.message}`;
        break;
    }

    toast.error(message, { duration: 6000 });
  };

  const onDropError = (error: Error) => {
    toast.error("Ошибка при обработке файлов");
    console.error(error);
  };

  return {
    onDropRejected,
    onDropError,
  };
}