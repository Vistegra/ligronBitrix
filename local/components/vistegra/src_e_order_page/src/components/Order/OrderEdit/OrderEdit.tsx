"use client";

import {useLocation, useNavigate, useParams} from "react-router-dom";
import {Card, CardContent, CardHeader, CardFooter} from "@/components/ui/card";
import {Tabs, TabsContent, TabsList, TabsTrigger} from "@/components/ui/tabs";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {Button} from "@/components/ui/button";
import {ChevronLeft, Loader2, SendIcon, Trash2Icon} from "lucide-react";
import {useEffect, useState} from "react";

import {useOrder} from "@/hooks/order/useOrder";
import {useChildOrders} from "@/hooks/order/useChildOrders";
import {useOrderMutations} from "@/hooks/order/useOrderMutations";

import {DescriptionTab} from "./DescriptionTab";
import {DocumentsTab} from "./DocumentsTab";
import {StatusHistoryTab} from "./StatusHistoryTab";
import {NestedOrdersTab} from "./NestedOrdersTab";
import {showDeleteConfirmToast} from "@/components/ui/popups/DeleteConfirmToast";
import {OrderNameEditor} from "./OrderNameEditor";
import {OrderJsonModal} from "../OrderJsonModal";
import {useBreadcrumbStore} from "@/store/breadcrumbStore";
import {cn} from "@/lib/utils";
import {PAGE} from "@/api/constants.ts";
import {formatDate} from "@/components/Order/Orders/utils.ts";

interface OrderEditProps {
  className?: string;
  isDraft: boolean;
}

export default function OrderEdit({isDraft = false, className}: OrderEditProps) {
  const {setOrderNumber} = useBreadcrumbStore();
  const {id} = useParams();
  const orderId = parseInt(id!, 10);

  const [activeTab, setActiveTab] = useState("description");

  const {order, loading, error, files} = useOrder(orderId);
  const {children, loading: childLoading} = useChildOrders(orderId);

  const navigate = useNavigate();
  const location = useLocation();

  const {
    update,
    uploadFiles,
    deleteFile,
    sendToLigron,
    deleteOrder,
    isWorking
  } = useOrderMutations(orderId, isDraft);

  useEffect(() => {
    setOrderNumber(order?.number || null);
    return () => setOrderNumber(null);
  }, [order?.number, setOrderNumber]);

  const handleBack = () => {
    if (location.key !== "default") {
      navigate(-1);
    } else {
      navigate(isDraft ? PAGE.DRAFTS : PAGE.ORDERS);
    }
  };

  if (loading) return <div className="h-full flex items-center justify-center"><Loader2
    className="animate-spin h-8 w-8 text-muted-foreground"/></div>;

  if (error || !order) return (
    <Alert variant="destructive">
      <AlertDescription>{error || "Заказ не найден"}</AlertDescription>
    </Alert>
  );

  return (
    <div className={cn('bg-background h-full flex flex-col overflow-hidden relative', className)}>

      <Card className="w-full h-full border-none shadow-none md:border md:shadow-sm flex flex-col overflow-hidden">

        <CardHeader className="px-3 pt-3 pb-2 md:px-6 md:pt-6 shrink-0">
          <div
            className="flex flex-col gap-1 flex-start items-start w-full md:flex md:flex-row md:flex-center md:flex-start">

            <Button
              variant="ghost"
              className="md:hidden h-auto p-0 text-primary hover:bg-transparent hover:text-primary/80 shrink-0 justify-self-start"
              onClick={handleBack}
            >
              <ChevronLeft className="h-5 w-5"/>
              <span className="text-sm font-medium">Назад</span>
            </Button>

            <div className="min-w-0 flex justify-center md:justify-start md:flex-1 md:px-4">
              <OrderNameEditor
                name={order.name}
                isDraft={isDraft}
                isSaving={update.isPending}
                onSave={(newName) => update.mutateAsync({name: newName})}
              />
            </div>

            <div className="text-xs text-muted-foreground whitespace-nowrap shrink-0 justify-self-end">
              {formatDate(order.created_at)}
            </div>
          </div>
        </CardHeader>

        <CardContent className="px-0 md:px-6 flex-1 flex flex-col overflow-hidden min-h-0">

          <Tabs value={activeTab} onValueChange={setActiveTab} className="flex flex-col h-full">

            <div className="bg-background pb-2 md:p-0 shrink-0">
              <TabsList className={cn(
                "w-full h-auto p-1 bg-muted rounded-lg",
                "flex overflow-x-auto justify-start no-scrollbar",
                "md:grid md:grid-cols-4"
              )}>
                <TabsTrigger value="description" className="flex-1 min-w-[100px]">Описание</TabsTrigger>
                <TabsTrigger value="documents" className="flex-1 min-w-[110px]">Документы</TabsTrigger>
                {!isDraft && <>
                  <TabsTrigger value="statuses" className="flex-1 min-w-[90px]">Статусы</TabsTrigger>
                  <TabsTrigger value="nested" className="flex-1 min-w-[110px]">Вложенные</TabsTrigger>
                </>
                }
              </TabsList>
            </div>

            <div className="flex-1 overflow-y-auto mt-2 pb-4 scroll-smooth">
              <TabsContent value="description" className="mt-0 animate-in fade-in-50 h-full">
                <DescriptionTab
                  comment={order.comment}
                  onUpdate={async (comment) => update.mutateAsync({comment})}
                />
              </TabsContent>

              <TabsContent value="documents" className="mt-0 animate-in fade-in-50 h-full">
                <DocumentsTab
                  files={files}
                  uploading={uploadFiles.isPending}
                  onUpload={async (files) => uploadFiles.mutateAsync(files)}
                  onDelete={async (fileId) => deleteFile.mutateAsync(fileId)}
                />
              </TabsContent>

              <TabsContent value="statuses" className="mt-0 animate-in fade-in-50 h-full">
                <StatusHistoryTab history={order.status_history}/>
              </TabsContent>

              <TabsContent value="nested" className="mt-0 animate-in fade-in-50 h-full">
                <NestedOrdersTab orders={children} loading={childLoading}/>
              </TabsContent>
            </div>

          </Tabs>
        </CardContent>

        {isDraft && (
          <CardFooter className="shrink-0 p-3 border-t bg-background z-10 flex justify-end items-center gap-2">
            <div className="flex justify-end items-center gap-2 md:gap-3">
              <Button
                variant="ghost"
                onClick={() => showDeleteConfirmToast({
                  title: "Удалить заказ?",
                  description: "Заказ и все файлы будут удалены навсегда.",
                  onConfirm: () => deleteOrder.mutate(),
                })}
                disabled={isWorking}
                className="rounded-lg text-destructive hover:text-destructive hover:bg-destructive/10 px-3"
              >
                {deleteOrder.isPending ?
                  <Loader2 className="h-5 w-5 animate-spin"/> :
                  <Trash2Icon className="h-5 w-5"/>
                }
                <span className="hidden sm:inline ml-2">Удалить</span>
              </Button>

              <Button
                size="default"
                onClick={() => sendToLigron.mutate()}
                disabled={isWorking}
                className="bg-green-600 hover:bg-green-700 text-white shadow-sm rounded-lg px-4"
              >
                {sendToLigron.isPending ?
                  <Loader2 className="mr-2 h-4 w-4 animate-spin"/> :
                  <SendIcon className="mr-2 h-4 w-4"/>
                }
                <span className="font-medium">Отправить в Лигрон</span>
              </Button>

              <OrderJsonModal
                orderId={orderId}
                className="rounded-lg w-10 h-10 hover:bg-muted text-muted-foreground"
              />
            </div>
          </CardFooter>
        )}
      </Card>
    </div>
  );
}