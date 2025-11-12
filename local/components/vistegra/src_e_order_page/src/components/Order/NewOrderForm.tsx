"use client";

import {useEffect, useState} from "react";
import {useForm} from "react-hook-form";
import {zodResolver} from "@hookform/resolvers/zod";
import {z} from "zod";
import {Loader2, Trash2, UploadIcon, AlertCircle} from "lucide-react";

import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {Input} from "@/components/ui/input";
import {Textarea} from "@/components/ui/textarea";
import {Button} from "@/components/ui/button";
import {Card, CardContent, CardHeader, CardTitle} from "@/components/ui/card";
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemTitle,
} from "@/components/ui/item";
import {Dropzone} from "@/components/ui/shadcn-io/dropzone";
import {Alert, AlertDescription, AlertTitle} from "@/components/ui/alert";

import {useCreateOrder} from "@/hooks/useCreateOrder";
import {toast} from "sonner";

// Валидация
const formSchema = z.object({
  name: z.string().min(1, "Название заказа обязательно"),
  comment: z.string().optional(),
});

type FormData = z.infer<typeof formSchema>;

export default function NewOrderForm() {
  const [files, setFiles] = useState<File[]>([]);
  const {createOrder, isSubmitting, error, success, createdOrder, reset, clearError} = useCreateOrder();

  const form = useForm<FormData>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      comment: "",
    },
  });

  useEffect(() => {
    if (success && createdOrder) {
      form.reset();
      setFiles([]);
      // Автосброс состояния хука через 4 сек
      const timer = setTimeout(() => reset(), 4000);
      return () => clearTimeout(timer);
    }
  }, [success, createdOrder]);

  const onDrop = (acceptedFiles: File[]) => {
    setFiles((prev) => [...prev, ...acceptedFiles]);
  };

  const removeFile = (index: number) => {
    setFiles((prev) => prev.filter((_, i) => i !== index));
  };

  const onSubmit = async (data: FormData) => {
    clearError();

    try {
      const result = await createOrder({
        name: data.name,
        comment: data.comment || undefined,
        files: files.length > 0 ? files : undefined,
      });

      // Успех или частичный успех
      if (result.order) {
        const fileErrors = result.files
          ?.filter((f) => f.error)
          .map((f) => `${f.original_name}: ${f.error}`)
          .join("; ");

        if (fileErrors) {
           toast.warning(`Заказ создан, но есть ошибки с файлами: ${fileErrors}`);
        } else {
           toast.success("Заказ успешно создан!");
        }
      }
    } catch (err) {
      // @ts-ignore
      toast.error(err.message);
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
              render={({field}) => (
                <FormItem>
                  <FormLabel>Название заказа</FormLabel>
                  <FormControl>
                    <Input placeholder="Введите название" {...field} disabled={isSubmitting}/>
                  </FormControl>
                  <FormMessage/>
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="comment"
              render={({field}) => (
                <FormItem>
                  <FormLabel>Описание заказа</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="Введите описание"
                      className="min-h-[150px]"
                      {...field}
                      disabled={isSubmitting}
                    />
                  </FormControl>
                  <FormMessage/>
                </FormItem>
              )}
            />

            <div className="space-y-4">
              <Dropzone
                maxSize={20 * 1024 * 1024}
                multiple
                onDrop={onDrop}
                disabled={isSubmitting}
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

              {files.length > 0 && (
                <div className="space-y-2">
                  {files.map((file, index) => (
                    <Item key={index} variant="outline" size="sm">
                      <ItemContent>
                        <ItemTitle className="text-sm">{file.name}</ItemTitle>
                        <ItemDescription className="text-xs">
                          {(file.size / 1024).toFixed(0)} КБ
                        </ItemDescription>
                      </ItemContent>
                      <ItemActions>
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          onClick={() => removeFile(index)}
                          disabled={isSubmitting}
                        >
                          <Trash2 className="h-4 w-4"/>
                        </Button>
                      </ItemActions>
                    </Item>
                  ))}
                </div>
              )}
            </div>

            {/* Глобальная ошибка */}
            {error && (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4"/>
                <AlertTitle>Ошибка</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {/* Ошибки по файлам при partial success */}
            {success && createdOrder?.files && createdOrder.files.some(f => f.error) && (
              <Alert variant="default" className="border-orange-500 bg-orange-50">
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>Частичный успех</AlertTitle>
                <AlertDescription>
                  Заказ создан, но не все файлы загружены:
                  <ul className="mt-2 list-disc list-inside text-sm">
                    {createdOrder.files
                      .filter(f => f.error)
                      .map((f, i) => (
                        <li key={i}>
                          <strong>{f.original_name}</strong>: {f.error}
                        </li>
                      ))}
                  </ul>
                </AlertDescription>
              </Alert>
            )}

            {/* Успешные файлы */}
            {success && createdOrder?.files && createdOrder.files.some(f => f.file_id) && (
              <Alert variant="default" className="border-green-500 bg-green-50">
                <AlertTitle>Файлы загружены</AlertTitle>
                <AlertDescription>
                  Успешно загружено файлов: {createdOrder.files.filter(f => f.file_id).length}
                </AlertDescription>
              </Alert>
            )}
            <div className="flex justify-end space-x-4">
              <Button
                type="submit"
                variant="outline"
                disabled={isSubmitting || success}
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin"/>
                    Сохранение...
                  </>
                ) : (
                  "Сохранить как черновик"
                )}
              </Button>
              <Button
                type="submit"
                disabled={isSubmitting || success}
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin"/>
                    Сохранение...
                  </>
                ) : (
                  "Создать заказ"
                )}
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}