import OrderEdit from "@/components/Order/OrderEdit/OrderEdit.tsx";

export default function DraftOrderDetailPage() {
  return (
    <div className="grid grid-cols-[70%_30%] gap-[30px]">
      <OrderEdit isDraft={true}/>
    </div>
  )
}