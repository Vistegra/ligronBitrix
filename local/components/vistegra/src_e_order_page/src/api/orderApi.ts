import api, {makeRequest} from "./client";
import {ENDPOINT} from "./constants";

export type CreateOrderData = {
  name: string;
  comment?: string;
  files?: File[];
  is_draft: number; // 0, 1
  dealer_prefix?: string;
  dealer_user_id?: string;
};

export interface OrderStatus {
  id: number;
  name: string;
  code: string;
  color: string;
}

export interface StatusHistoryItem {
  id: number;
  date: string;
}

export interface OrderFile {
  id: number;
  order_id: number;
  name: string;
  path: string;
  size?: number;
  mime?: string;
  created_at?: string;
  uploaded_by?: number;
  uploaded_by_id?: number;
}

export type Order = {
  id: number;
  number: string | null;
  name: string;
  status_id: number | null;
  parent_id: number | null;
  created_by: number;
  created_by_id: number;
  dealer_prefix: string | null;
  dealer_user_id: number | null;
  manager_id: number | null;
  fabrication: string | null;
  ready_date: number | null;
  comment: string | null;
  children_count: number;
  status_history: StatusHistoryItem[];
  created_at: number;
  updated_at: number;
  status_code: string | null;
  status_name: string | null;
  status_color: string | null;
  parent_order_number: string | null;
  parent_order_id: number | null;
};

export type OrdersResponse = {
  orders: Order[];
  pagination: {
    limit: number;
    offset: number;
    total: number;
  };
};

export type OrderResponse = {
  order: Order;
  files: OrderFile[];
};

export type UploadFilesResponse = {
  files: OrderFile[];
};

export interface OrdersRequest {
  filter?: string;
  limit?: number;
  offset?: number;
  is_draft: number
}

export const orderApi = {
  // Создание заказа
  createOrder(data: CreateOrderData) {
    const formData = new FormData();

    formData.append("name", data.name);
    if (data.comment) formData.append("comment", data.comment);
    if (data.is_draft) formData.append("is_draft", "1");

    // Если создает менеджер за дилера
    if (data.dealer_prefix && data.dealer_user_id) {
      formData.append("dealer_prefix", data.dealer_prefix);
      formData.append("dealer_user_id", data.dealer_user_id);
    }

    data.files?.forEach((file) => {
      formData.append("file[]", file);
    });

    return makeRequest<OrderResponse>(() =>
      api.post(ENDPOINT.ORDERS, formData, {
        headers: {"Content-Type": "multipart/form-data"},
      })
    );
  },

  // Получение списка заказов
  getOrders(params?: OrdersRequest) {
    return makeRequest<OrdersResponse>(() =>
      api.get(ENDPOINT.ORDERS, {params})
    );
  },

  // Получить один заказ
  getOrder(id: number) {
    return makeRequest<OrderResponse>(() =>
      api.get(`${ENDPOINT.ORDERS}/${id}`)
    );
  },

  // Обновление заказа
  updateOrder(id: number, data: Partial<Pick<CreateOrderData, "name" | "comment">>) {
    return makeRequest<{ order: Order }>(() =>
      api.put(`${ENDPOINT.ORDERS}/${id}`, data)
    );
  },

  // Удалить заказ
  deleteOrder(id: number) {
    return makeRequest<null>(() =>
      api.delete(`${ENDPOINT.ORDERS}/${id}`)
    );
  },

  // Загрузить файлы
  uploadFiles(orderId: number, files: File[]) {
    const formData = new FormData();
    files.forEach((file) => formData.append("file[]", file));

    return makeRequest<UploadFilesResponse>(() =>
      api.post(`${ENDPOINT.ORDERS}/${orderId}/files`, formData, {
        headers: {"Content-Type": "multipart/form-data"},
      })
    );
  },

  // Удалить файл
  deleteFile(orderId: number, fileId: number) {
    return makeRequest<null>(() =>
      api.delete(`${ENDPOINT.ORDERS}/${orderId}/files/${fileId}`)
    );
  },

  // Статусы
  getStatuses() {
    return makeRequest<OrderStatus[]>(() =>
      api.get(ENDPOINT.STATUSES)
    );
  },

  // Отправить черновик в Лигрон и создать заказ (получаем номерб статус)
  sendToLigron(id: number) {
    return makeRequest<{ order: Order }>(() =>
      api.post(`${ENDPOINT.ORDERS}/${id}/send-to-ligron`)
    );
  },
};