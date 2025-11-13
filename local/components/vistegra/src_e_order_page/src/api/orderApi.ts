import api from "./client";
import { ENDPOINT } from "./constants";

export type CreateOrderData = {
  name: string;
  comment?: string;
  files?: File[];
};

export type FileUploadResult = {
  file_id?: number;
  original_name: string;
  error?: string;
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
  files: any | null;
};

export type CreateOrderResponse = {
  order: Order;
  files?: FileUploadResult[];
};

export type OrdersListResponse = {
  order: Order[];
  pagination: {
    limit: number;
    offset: number;
    total: number;
  };
};

export type ApiResponse<T> = {
  status: 'success' | 'error' | 'partial';
  message: string;
  data: T;
};

export const orderApi = {
  async createOrder(data: CreateOrderData): Promise<ApiResponse<CreateOrderResponse>> {
    const formData = new FormData();
    formData.append("name", data.name);

    if (data.comment) {
      formData.append("comment", data.comment);
    }

    data.files?.forEach((file) => {
      formData.append("file[]", file);
    });

    const response = await api.post(ENDPOINT.ORDERS, formData);

    if (!response.data?.data) {
      throw new Error("Invalid response format");
    }

    return response.data;
  },

  async getOrders(params?: {
    filter?: any;
    limit?: number;
    offset?: number;
  }): Promise<ApiResponse<OrdersListResponse>> {
    const response = await api.get(ENDPOINT.ORDERS, { params });
    return response.data;
  },

  async getStatuses(): Promise<ApiResponse<OrderStatus[]>> {
      const response = await api.get(ENDPOINT.STATUSES);
      return response.data;
  },


  // async getOrder(id: number): Promise<ApiResponse<{ order: Order }>> {
  //   const response = await api.get(`${ENDPOINT.ORDERS}/${id}`);
  //   return response.data;
  // },
  //
  // async updateOrder(id: number, data: Partial<CreateOrderData>): Promise<ApiResponse<{ order: Order }>> {
  //   const response = await api.put(`${ENDPOINT.ORDERS}/${id}`, data);
  //   return response.data;
  // },
  //
  // async deleteOrder(id: number): Promise<void> {
  //   await api.delete(`${ENDPOINT.ORDERS}/${id}`);
  // },
  //
  // async uploadFiles(orderId: number, files: File[]): Promise<ApiResponse<{ files: FileUploadResult[] }>> {
  //   const formData = new FormData();
  //   files.forEach((file) => formData.append("file[]", file));
  //
  //   const response = await api.post(`${ENDPOINT.ORDERS}/${orderId}/files`, formData);
  //   return response.data;
  // },
  //

  //
  // async getStatuses(): Promise<ApiResponse<any[]>> {
  //   const response = await api.get(`${ENDPOINT.ORDERS}/statuses`);
  //   return response.data;
  // }
};