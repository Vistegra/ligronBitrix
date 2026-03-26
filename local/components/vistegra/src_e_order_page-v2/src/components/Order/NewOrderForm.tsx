"use client";

import {useState} from "react";
import {useForm} from "react-hook-form";
import {zodResolver} from "@hookform/resolvers/zod";
import {z} from "zod";
import {
  Loader2, Trash2, UploadIcon, AlertCircle,
  CheckCircle2, SendIcon, SaveIcon, Info, Building2, Store
} from "lucide-react";

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

import {useCreateOrder} from "@/hooks/order/useCreateOrder.ts";
import {useFileDropzone} from "@/hooks/order/useFileDropzone.ts";
import {useAuthStore} from "@/store/authStore.ts";
import {useWorkspace} from "@/hooks/common/useWorkspace.ts";
import {toast} from "sonner";

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
  const {user} = useAuthStore();

  // Достаем данные из глобального Workspace
  const {isSet, current} = useWorkspace();

  const isDealer = user?.provider === "dealer";
  const {create, isPending, error, isSuccess} = useCreateOrder();
  const {onDropRejected, onDropError} = useFileDropzone();

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
    if (!isSet || !current || !current.inn) {
      toast.error("Пожалуйста, выберите дилера для оформления заказа");
      return;
    }

    if (!current.salonCode) {
      toast.error("Пожалуйста, выберите салон для оформления заказа");
      return;
    }

    create({
      name: data.name,
      comment: data.comment || undefined,
      files: files.length > 0 ? files : undefined,
      is_draft: Number(isDraft),

      inn_dealer: current.inn,
      salon_code: current.salonCode,
    });
  };

  return (
    <Card className="w-full max-w-2xl mx-auto p-0 m-0 border-none shadow-none">
      <CardContent className="p-0 m-0">

        {/* Блок контекста */}
        {isSet && current ? (
          <div className="mb-6 p-4 rounded-xl border border-dashed bg-slate-50/50 space-y-3">
            <div className="flex items-start gap-3">
              <div className="mt-1 p-1.5 bg-primary/10 rounded-md text-primary shrink-0">
                <Building2 className="h-4 w-4"/>
              </div>
              <div className="flex flex-col overflow-hidden">
                <span
                  className="text-[10px] font-bold text-muted-foreground tracking-wider">Дилер</span>
                <span className="text-sm font-bold leading-tight truncate">{current.dealerName}</span>
                <span className="text-[11px] text-muted-foreground">ИНН: {current.inn}</span>
              </div>
            </div>

            <div className="flex items-start gap-3 pt-2 border-t border-slate-200">
              <div className="mt-1 p-1.5 bg-slate-100 rounded-md text-slate-500 shrink-0">
                <Store className="h-4 w-4"/>
              </div>
              <div className="flex flex-col overflow-hidden">
                <span
                  className="text-[10px] font-bold text-muted-foreground tracking-wider">Салон</span>
                <span className="text-sm font-medium leading-tight truncate">
                  {current.salonName || "Не выбран"}
                </span>
                <span className="text-[11px] text-muted-foreground">Код: {current.salonCode || "—"}</span>
              </div>
            </div>
          </div>
        ) : (
          /* Предупреждение, если контекст не выбран */
          <Alert variant="default" className="mb-6 border-amber-200 bg-amber-50 text-amber-800">
            <Info className="h-4 w-4 text-amber-600"/>
            <AlertTitle>Внимание</AlertTitle>
            <AlertDescription>
              Для создания заказа необходимо выбрать <strong>один салон</strong> и <strong>одного дилера</strong> в
              списке или сайдбаре.
            </AlertDescription>
          </Alert>
        )}

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="name"
              render={({field}) => (
                <FormItem>
                  <FormLabel>Название заказа</FormLabel>
                  <FormControl>
                    <Input placeholder="Введите название или ФИО клиента" {...field} disabled={isPending || !isSet}/>
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
                  <FormLabel>Описание / Комментарий</FormLabel>
                  <FormControl>
                    <Textarea
                      placeholder="Дополнительная информация по заказу"
                      className="min-h-[120px]"
                      {...field}
                      disabled={isPending || !isSet}
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
                disabled={isPending || !isSet}
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

            {error && (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4"/>
                <AlertTitle>Ошибка</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {isSuccess && (
              <Alert className="border-green-500 bg-green-50 text-green-900">
                <CheckCircle2 className="h-4 w-4 text-green-600"/>
                <AlertTitle>Успешно!</AlertTitle>
                <AlertDescription>Заказ создан. Перенаправление на детальную страницу...</AlertDescription>
              </Alert>
            )}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              {isDealer && (
                <Button
                  type="submit"
                  variant="outline"
                  disabled={isPending || isSuccess || !isSet}
                  onClick={() => setIsDraft(true)}
                  className="w-full sm:w-auto"
                >
                  {isPending && isDraft ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin"/>
                  ) : (
                    <SaveIcon className="mr-2 h-4 w-4"/>
                  )}
                  Сохранить как черновик
                </Button>
              )}

              <Button
                type="submit"
                disabled={isPending || isSuccess || !isSet}
                onClick={() => setIsDraft(false)}
                className="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white"
              >
                {isPending && !isDraft ? (
                  <Loader2 className="mr-2 h-4 w-4 animate-spin"/>
                ) : (
                  <SendIcon className="mr-2 h-4 w-4"/>
                )}
                Отправить в Лигрон
              </Button>
            </div>
          </form>
        </Form>
      </CardContent>
    </Card>
  );

}