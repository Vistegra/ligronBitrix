import api, {type ApiResponse} from "./client";
import { ENDPOINT } from "./constants";

export type CreateOrderData = {
  name: string;
  comment?: string;
  files?: File[];
  is_draft: number; //0,1

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
  date: string; // формат "DD.MM.YYYY HH:mm:ss" //ToDo подумать timestamp
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
  fabrication: any | null;
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
  //ToDo error messages
};


export const orderApi = {
  //Создание заказа
  async createOrder(data: CreateOrderData): Promise<ApiResponse<OrderResponse>> {
    const formData = new FormData();
    formData.append("name", data.name);

    if (data.comment) {
      formData.append("comment", data.comment);
    }

    data.files?.forEach((file) => {
      formData.append("file[]", file);
    });

    if (data.is_draft) {
      formData.append("is_draft", "1");
    }

    //Если заказ создает менеджер
    if (data.dealer_prefix && data.dealer_user_id) {
      formData.append("dealer_prefix", data.dealer_prefix);
      formData.append("dealer_user_id", data.dealer_user_id);
    }

    const response = await api.post(ENDPOINT.ORDERS, formData);

    if (!response.data?.data) {
      throw new Error("Invalid response format");
    }

    return response.data;
  },

  // Получение заказов
  async getOrders(params?: {
    filter?: string;
    limit?: number;
    offset?: number;
    is_draft: number // 0, 1
  }): Promise<ApiResponse<OrdersResponse>> {

    const response = await api.get(ENDPOINT.ORDERS, { params });

    return response.data;
  },

  // Получить заказ
  async getOrder(id: number): Promise<ApiResponse<OrderResponse>> {

    const { data } = await api.get(`${ENDPOINT.ORDERS}/${id}`);

    return data;
  },

  // Обновление заказа
  async updateOrder(
    id: number,
    data: Partial<Pick<CreateOrderData, "name" | "comment">>
  ): Promise<ApiResponse<{ order: Order }>> {

    const resp = await api.put(`${ENDPOINT.ORDERS}/${id}`, data);

    return resp.data;
  },

  // Удалить заказ
  async deleteOrder(id: number): Promise<ApiResponse<null>> {
    const response = await api.delete<ApiResponse<null>>(`${ENDPOINT.ORDERS}/${id}`);

    if (response.data.status !== "success") {
      throw new Error(response.data.message || "Ошибка удаления заказа");
    }

    return response.data;
  },

  //Обновить файлы
  async uploadFiles(orderId: number, files: File[]): Promise<ApiResponse<UploadFilesResponse>> {
    const formData = new FormData();
    files.forEach((file) => formData.append("file[]", file));

    const { data } = await api.post(`${ENDPOINT.ORDERS}/${orderId}/files`, formData);
    return data;
  },

  // Удлаить файл
  async deleteFile(orderId: number, fileId: number): Promise<void> {
    await api.delete(`${ENDPOINT.ORDERS}/${orderId}/files/${fileId}`);
  },

  //Получение статусов
  async getStatuses(): Promise<ApiResponse<OrderStatus[]>> {
      const response = await api.get(ENDPOINT.STATUSES);
      return response.data;
  },

  // Отправить черновик в Лигрон (превращает черновик в обычный заказ)
  async sendToLigron(id: number): Promise<ApiResponse<{ order: Order }>> {
    const response = await api.post(`${ENDPOINT.ORDERS}/${id}/send-to-ligron`);
    return response.data;
  },


};