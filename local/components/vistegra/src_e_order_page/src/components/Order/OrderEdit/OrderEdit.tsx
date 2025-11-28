"use client";

import { useParams } from "react-router-dom";
import { format, fromUnixTime } from "date-fns";
import { ru } from "date-fns/locale";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { CloudUploadIcon, Trash2Icon, Loader2 } from "lucide-react";
import { useState } from "react";

import { useOrder } from "@/hooks/order/useOrder.ts";
import { useChildOrders } from "@/hooks/order/useChildOrders.ts";
import { useOrderMutations } from "@/hooks/order/useOrderMutations.ts";

import { DescriptionTab } from "./DescriptionTab";
import { DocumentsTab } from "./DocumentsTab";
import { StatusHistoryTab } from "./StatusHistoryTab";
import { NestedOrdersTab } from "@/components/Order/OrderEdit/NestedOrdersTab.tsx";
import { showDeleteConfirmToast } from "@/components/ui/popups/DeleteConfirmToast.tsx";
import {OrderNameEditor} from "@/components/Order/OrderEdit/OrderNameEditor.tsx";

interface OrderEditProps {
  isDraft: boolean
}

export default function OrderEdit({ isDraft = false }: OrderEditProps) {
  const { id } = useParams();
  const orderId = parseInt(id!, 10);

  const [activeTab, setActiveTab] = useState("description");

  // Хук чтения
  const { order, loading, error, files } = useOrder(orderId);
  const { children, loading: childLoading } = useChildOrders(orderId);

  // Хук мутаций
  const {
    update,
    uploadFiles,
    deleteFile,
    sendToLigron,
    deleteOrder,
    isWorking // Общий флаг загрузки
  } = useOrderMutations(orderId, isDraft);

  if (loading) return <div className="p-8">Загрузка...</div>;
  if (error || !order) return (
    <Alert variant="destructive">
      <AlertDescription>{error || "Заказ не найден"}</AlertDescription>
    </Alert>
  );

  return (
    <div className="bg-background pb-20">
      <div>
        <Card className="w-full">
          <CardHeader>
            <div className="flex justify-between items-center">
              <OrderNameEditor
                name={order.name}
                isDraft={isDraft}
                isSaving={update.isPending}
                onSave={(newName) => update.mutateAsync({ name: newName })}
              />
              <div className="text-muted-foreground">
                {format(fromUnixTime(order.created_at), "dd.MM.yyyy", { locale: ru })}
              </div>
            </div>
          </CardHeader>

          <CardContent>
            <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
              <TabsList className="grid w-full grid-cols-4 gap-1 p-1 bg-muted rounded-lg">
                <TabsTrigger value="description">Описание</TabsTrigger>
                <TabsTrigger value="documents">Документы</TabsTrigger>
                <TabsTrigger value="statuses">Статусы</TabsTrigger>
                <TabsTrigger value="nested">Вложенные</TabsTrigger>
              </TabsList>

              <TabsContent value="description" className="mt-0">
                <DescriptionTab
                  comment={order.comment}
                  // Передаем mutateAsync, чтобы форма могла ждать завершения
                  onUpdate={async (comment) => update.mutateAsync({ comment })}
                />
              </TabsContent>

              <TabsContent value="documents" className="mt-0">
                <DocumentsTab
                  files={files}
                  // Загрузка для документов берется из статуса мутации
                  uploading={uploadFiles.isPending}
                  onUpload={async (files) => uploadFiles.mutateAsync(files)}
                  onDelete={async (fileId) => deleteFile.mutateAsync(fileId)}
                />
              </TabsContent>

              <TabsContent value="statuses" className="mt-0">
                <StatusHistoryTab history={order.status_history} />
              </TabsContent>

              <TabsContent value="nested" className="mt-0">
                <NestedOrdersTab orders={children} loading={childLoading} />
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>

        <div className="fixed bottom-6 right-6 p-2">
          {isDraft && (
            <div className="flex gap-4">
              <Button
                variant="default"
                size="lg"
                onClick={() => sendToLigron.mutate()}
                disabled={isWorking}
                className="shadow-lg"
              >
                {sendToLigron.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                    Отправка...
                  </>
                ) : (
                  <>
                    <CloudUploadIcon className="mr-2 h-5 w-5" />
                    Отправить в Лигрон
                  </>
                )}
              </Button>

              <Button
                variant="ghost"
                size="lg"
                onClick={() =>
                  showDeleteConfirmToast({
                    title: "Удалить заказ?",
                    description: "Заказ и все файлы будут удалены навсегда.",
                    onConfirm: () => deleteOrder.mutate(),
                  })
                }
                disabled={isWorking}
                className="shadow-lg"
              >
                {deleteOrder.isPending ? (
                  <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                ) : (
                  <Trash2Icon className="mr-2 h-5 w-5" />
                )}
                Удалить заказ
              </Button>
            </div>
          )}
        </div>

      </div>
    </div>
  );
}