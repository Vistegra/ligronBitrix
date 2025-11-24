import OrdersTable from "@/components/Order/OrdersTable/OrdersTable.tsx";

export default function DraftOrdersPage() {
  return (
    <>
     <OrdersTable isDraft={true} />
    </>
  )
}