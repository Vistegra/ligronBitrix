import OrderEdit from "@/components/Order/OrderEdit/OrderEdit";


export default function OrderDetailPage() {
  return (
    <div className="h-full">
      <div className="flex flex-col lg:grid lg:grid-cols-[66%_28%] gap-6 lg:gap-8 h-full">
        <div className="min-w-0 min-h-0">
          <OrderEdit isDraft={false} className="h-full"/>
        </div>

        <div className="hidden lg:block">
          <div className="border border-dashed p-4 rounded-lg text-muted-foreground text-sm h-full">
            Место под чат
          </div>
        </div>

      </div>
    </div>
  )
}