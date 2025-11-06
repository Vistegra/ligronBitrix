import NewOrderForm from "@/components/Order/NewOrderForm.tsx";
import {SideModal} from "@/components/SideModal.tsx";

export default function OrdersPage() {
  return (
    <>
     Страница заказов

     <NewOrderForm/>

      <SideModal
        trigger={<button>Открыть</button>} // trigger обязателен, но можно скрыть
      >
        <div className="p-6">
          <h2 className="text-2xl font-bold mb-4">Управляемая модалка</h2>
          <p>Эта модалка управляется через state</p>
        </div>
      </SideModal>
    </>
  )
}