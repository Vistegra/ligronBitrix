"use client";

import {useState} from "react";
import {useForm} from "react-hook-form";
import {zodResolver} from "@hookform/resolvers/zod";
import {z} from "zod";
import {Loader2, Trash2, UploadIcon, AlertCircle, CheckCircle2} from "lucide-react";
import {useSearchParams} from "react-router-dom";

import {
  Form, FormControl, FormField, FormItem, FormLabel, FormMessage,
} from "@/components/ui/form";
import {Input} from "@/components/ui/input";
import {Textarea} from "@/components/ui/textarea";
import {Button} from "@/components/ui/button";
import {Card, CardContent} from "@/components/ui/card";
import {Item, ItemActions, ItemContent, ItemDescription, ItemTitle} from "@/components/ui/item";
import {Dropzone} from "@/components/ui/shadcn-io/dropzone";
import {Alert, AlertDescription, AlertTitle} from "@/components/ui/alert";

import {useCreateOrder} from "@/hooks/useCreateOrder";
import {useFileDropzone} from "@/hooks/useFileDropzone";

const MAX_FILES = 10;
const MAX_SIZE_MB = 20;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

const formSchema = z.object({
  name: z.string().min(1, "Название заказа обязательно"),
  comment: z.string().optional(),
});

type FormData = z.infer<typeof formSchema>;

export default function NewOrderForm() {
  const [isDraft, setIsDraft] = useState(false);
  const [files, setFiles] = useState<File[]>([]);

  // Используем TanStack хук
  const {create, isPending, error, isSuccess} = useCreateOrder();

  const {onDropRejected, onDropError} = useFileDropzone();
  const [searchParams] = useSearchParams();

  const form = useForm<FormData>({
    resolver: zodResolver(formSchema),
    defaultValues: {name: "", comment: ""},
  });

  const onDrop = (acceptedFiles: File[]) => {
    setFiles((prev) => [...prev, ...acceptedFiles]);
  };

  const removeFile = (index: number) => {
    setFiles((prev) => prev.filter((_, i) => i !== index));
  };

  const onSubmit = (data: FormData) => {
    create({
      name: data.name,
      comment: data.comment || undefined,
      files: files.length > 0 ? files : undefined,
      is_draft: Number(isDraft),
      dealer_prefix: searchParams.get('dealer_prefix') ?? '',
      dealer_user_id: searchParams.get('dealer_user_id') ?? '',
    });
  };

  return (
    <Card className="w-full max-w-2xl mx-auto p-0 m-0 border-none shadow-none">
      <CardContent className="p-0 m-0">
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="name"
              render={({field}) => (
                <FormItem>
                  <FormLabel>Название заказа</FormLabel>
                  <FormControl>
                    <Input placeholder="Введите название" {...field} disabled={isPending}/>
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
                      disabled={isPending}
                    />
                  </FormControl>
                  <FormMessage/>
                </FormItem>
              )}
            />

            <div className="space-y-4">
              <Dropzone
                maxSize={MAX_SIZE_BYTES}
                maxFiles={MAX_FILES}
                multiple
                onDropAccepted={onDrop}
                onDropRejected={onDropRejected}
                onError={onDropError}
                disabled={isPending}
                className="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors hover:border-primary"
              >
                <div className="flex flex-col items-center space-y-2">
                  <div className="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground">
                    <UploadIcon size={16}/>
                  </div>
                  <p className="font-medium">Перетащите файлы или кликните для выбора</p>
                  <p className="text-xs text-muted-foreground">До 20 МБ</p>
                </div>
              </Dropzone>

              {files.length > 0 && (
                <div className="space-y-2">
                  {files.map((file, index) => (
                    <Item key={index} variant="outline" size="sm">
                      <ItemContent>
                        <ItemTitle className="text-sm">{file.name}</ItemTitle>
                        <ItemDescription className="text-xs">{(file.size / 1024).toFixed(0)} КБ</ItemDescription>
                      </ItemContent>
                      <ItemActions>
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          onClick={() => removeFile(index)}
                          disabled={isPending}
                        >
                          <Trash2 className="h-4 w-4"/>
                        </Button>
                      </ItemActions>
                    </Item>
                  ))}
                </div>
              )}
            </div>

            {/* Ошибки из хука */}
            {error && (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4"/>
                <AlertTitle>Ошибка</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {/*  Успеха из хука (показывается перед редиректом) */}
            {isSuccess && (
              <Alert className="border-green-500 bg-green-50 text-green-900">
                <CheckCircle2 className="h-4 w-4 text-green-600"/>
                <AlertTitle>Успешно!</AlertTitle>
                <AlertDescription>Заказ создан. Перенаправление...</AlertDescription>
              </Alert>
            )}

            <div className="flex justify-end space-x-4">
              <Button
                type="submit"
                variant="outline"
                disabled={isPending || isSuccess}
                onClick={() => setIsDraft(true)}
              >
                {isPending && isDraft ? <Loader2 className="mr-2 h-4 w-4 animate-spin"/> : "Сохранить как черновик"}
              </Button>
              <Button
                type="submit"
                disabled={isPending || isSuccess}
                onClick={() => setIsDraft(false)}
              >
                {isPending && !isDraft ? <Loader2 className="mr-2 h-4 w-4 animate-spin"/> : "Отправить в Лигрон"}
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}