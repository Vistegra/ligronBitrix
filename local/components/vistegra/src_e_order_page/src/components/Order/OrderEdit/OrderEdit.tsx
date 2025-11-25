"use client";

import {useParams, useNavigate} from "react-router-dom";
import {format, fromUnixTime} from "date-fns";
import {ru} from "date-fns/locale";
import {Card, CardContent, CardHeader, CardTitle} from "@/components/ui/card";
import {Tabs, TabsContent, TabsList, TabsTrigger} from "@/components/ui/tabs";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {Button} from "@/components/ui/button";
import {Trash2Icon} from "lucide-react";
import {toast} from "sonner";

import {useOrder} from "@/hooks/useOrder";
import {useChildOrders} from "@/hooks/useChildOrders";
import {DescriptionTab} from "./DescriptionTab";
import {DocumentsTab} from "./DocumentsTab";
import {StatusHistoryTab} from "./StatusHistoryTab";

import {useState} from "react";
import {orderApi} from "@/api/orderApi";
import {NestedOrdersTab} from "@/components/Order/OrderEdit/NestedOrdersTab.tsx";
import {showDeleteConfirmToast} from "@/components/ui/popups/DeleteConfirmToast.tsx";
import {PAGE} from "@/api/constants.ts";

interface OrderEditProps {
  isDraft: boolean
}

export default function OrderEdit({isDraft = false}: OrderEditProps) {
  const {id} = useParams();
  const navigate = useNavigate();
  const orderId = parseInt(id!, 10);
  const {order, loading, error, files, updateComment, uploadFiles, deleteFile} = useOrder(orderId, isDraft);
  const {children, loading: childLoading} = useChildOrders(orderId);
  const [uploading, setUploading] = useState(false);
  const [activeTab, setActiveTab] = useState("description");

  const basePage = isDraft ? PAGE.DRAFTS : PAGE.ORDERS;

  const handleUpload = async (files: File[]) => {
    setUploading(true);
    try {
      await uploadFiles(files);
    } finally {
      setUploading(false);
    }
  };

  const handleDelete = async () => {
    try {
      const res = await orderApi.deleteOrder(orderId);
      if (res.status === "success") {
        toast.success("Заказ удалён");
        navigate(basePage);
      } else {
        toast.error(res.message || "Ошибка удаления");
      }
    } catch {
      toast.error("Не удалось удалить заказ");
    }
  };


  if (loading) return <div className="p-8">Загрузка...</div>;
  if (error || !order) return <Alert
    variant="destructive"><AlertDescription>{error || "Заказ не найден"}</AlertDescription></Alert>;

  return (
    <div className="bg-background pb-20">
      <div className="">
        <Card className="w-full">

          <CardHeader>
            <div className="flex justify-between items-center">
              <CardTitle>{order.name}</CardTitle>
              <div className="text-muted-foreground">
                {format(fromUnixTime(order.created_at), "dd.MM.yyyy", {locale: ru})}
              </div>
            </div>
          </CardHeader>

          <CardContent>

            <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">

              <TabsList className="grid w-full grid-cols-4 gap-1 p-1 bg-muted rounded-lg">
                <TabsTrigger value="description"
                             className="data-[state=active]:bg-background data-[state=active]:shadow-sm">
                  Описание
                </TabsTrigger>
                <TabsTrigger value="documents"
                             className="data-[state=active]:bg-background data-[state=active]:shadow-sm">
                  Документы
                </TabsTrigger>
                <TabsTrigger value="statuses"
                             className="data-[state=active]:bg-background data-[state=active]:shadow-sm">
                  Статусы
                </TabsTrigger>
                <TabsTrigger value="nested" className="data-[state=active]:bg-background data-[state=active]:shadow-sm">
                  Вложенные
                </TabsTrigger>
              </TabsList>

              <TabsContent value="description" className="mt-0">
                <DescriptionTab comment={order.comment} onUpdate={updateComment}/>
              </TabsContent>

              <TabsContent value="documents" className="mt-0">
                <DocumentsTab files={files} uploading={uploading} onUpload={handleUpload} onDelete={deleteFile}/>
              </TabsContent>

              <TabsContent value="statuses" className="mt-0">
                <StatusHistoryTab history={order.status_history}/>
              </TabsContent>

              <TabsContent value="nested" className="mt-0">
                <NestedOrdersTab orders={children} loading={childLoading}/>
              </TabsContent>
            </Tabs>

          </CardContent>
        </Card>

        <div className="fixed bottom-6 right-6">
          {
            isDraft && <Button
              variant="ghost"
              size="lg"
              onClick={() =>
                showDeleteConfirmToast({
                  title: "Удалить заказ?",
                  description: "Заказ и все файлы будут удалены навсегда.",
                  onConfirm: handleDelete,
                })
              }
              className="shadow-lg fixed bottom-6 right-6"
            >
              <Trash2Icon className="mr-2 h-5 w-5"/>
              Удалить заказ
            </Button>
          }
        </div>

      </div>
    </div>
  );
}