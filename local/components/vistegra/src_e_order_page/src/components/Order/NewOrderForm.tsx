"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Loader2, Trash2, UploadIcon } from "lucide-react";
import { Alert, AlertDescription } from "@/components/ui/alert";

import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemTitle,
} from "@/components/ui/item";
import { Dropzone } from "@/components/ui/shadcn-io/dropzone";

import api from "@/api/client";

// Валидация
const formSchema = z.object({
  name: z.string().min(1, "Название заказа обязательно"),
  comment: z.string().optional(),
});

type FormData = z.infer<typeof formSchema>;

export default function NewOrderForm() {
  const [files, setFiles] = useState<File[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitType, setSubmitType] = useState<"draft" | "new" | null>(null);
  const [error, setError] = useState<string | null>(null);

  const form = useForm<FormData>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      comment: "",
    },
  });

  const onDrop = (acceptedFiles: File[]) => {
    setFiles((prev) => [...prev, ...acceptedFiles]);
  };

  const removeFile = (index: number) => {
    setFiles((prev) => prev.filter((_, i) => i !== index));
  };

  const onSubmit = async (data: FormData) => {
    if (!submitType) return;
    setIsSubmitting(true);
    setError(null); // Очищаем ошибку перед отправкой

    const formData = new FormData();
    formData.append("name", data.name);
    if (data.comment) formData.append("comment", data.comment);
    files.forEach((file) => formData.append("file[]", file));

    try {
      const endpoint = "/orders";
      const response = await api.post(endpoint, formData);

      const result = response.data?.data;

      if (!result?.order) {
        throw new Error(result?.message || "Не удалось создать заказ");
      }

      const orderId = result.order.id;
      const fileResults = result.files || [];

      // Успешно загруженные файлы
      const uploadedFileIds = fileResults
        .filter((f: any) => f.file_id)
        .map((f: any) => f.file_id);

      // Ошибки по файлам
      const fileErrors = fileResults
        .filter((f: any) => f.error)
        .map((f: any) => `${f.original_name}: ${f.error}`)
        .join("; ");

      // Устанавливаем статус
      const statusCode = submitType === "draft" ? "draft" : "new";
      await api.post(`/orders/${orderId}/status`, { status: statusCode });

      // Успех
      console.log("Заказ создан:", orderId, "Файлы:", uploadedFileIds, fileErrors || "без ошибок");
      form.reset();
      setFiles([]);
      // toast.success("Заказ создан!");
    } catch (error: any) {
      console.error("Ошибка:", error);
      const msg =
        error.response?.data?.message ||
        error.message ||
        "Неизвестная ошибка";
      setError(msg); // Устанавливаем ошибку
      // toast.error(msg);
    } finally {
      setIsSubmitting(false);
      setSubmitType(null);
    }
  };

  return (
    <Card className="w-full max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>Создать новый заказ</CardTitle>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Название заказа</FormLabel>
                  <FormControl>
                    <Input placeholder="Введите название" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="comment"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Описание заказа</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="Введите описание"
                      className="min-h-[150px]"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="space-y-4">
              <Dropzone
                maxSize={20 * 1024 * 1024}
                multiple
                onDrop={onDrop}
                className="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer"
              >
                <div className="flex flex-col items-center space-y-2 text-center">
                  <div className="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground">
                    <UploadIcon size={16} />
                  </div>
                  <p className="font-medium">Перетащите файлы или кликните для выбора</p>
                  <p className="w-full text-wrap text-muted-foreground text-xs">
                    Поддерживаются любые типы файлов до 20Mb
                  </p>
                </div>
              </Dropzone>

              {files.length > 0 && (
                <div className="space-y-2">
                  {files.map((file, index) => (
                    <Item key={index} variant="outline" size="sm">
                      <ItemContent>
                        <ItemTitle>{file.name}</ItemTitle>
                        <ItemDescription className="text-xs">
                          {Math.round(file.size / 1024)} KB
                        </ItemDescription>
                      </ItemContent>
                      <ItemActions>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => removeFile(index)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </ItemActions>
                    </Item>
                  ))}
                </div>
              )}
            </div>

            {error && ( // Отображение ошибки с сервера
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            <div className="flex justify-end space-x-4">
              <Button
                type="submit"
                variant="outline"
                disabled={isSubmitting}
                onClick={() => setSubmitType("draft")}
              >
                {isSubmitting && submitType === "draft" ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Сохранение...
                  </>
                ) : (
                  "Сохранить как черновик"
                )}
              </Button>
              <Button
                type="submit"
                disabled={isSubmitting}
                onClick={() => setSubmitType("new")}
              >
                {isSubmitting && submitType === "new" ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Сохранение...
                  </>
                ) : (
                  "Сохранить новый заказ"
                )}
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}