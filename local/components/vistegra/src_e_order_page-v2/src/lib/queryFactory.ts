import {queryOptions} from "@tanstack/react-query";
import {authApi, type LoginCredentials} from "@/api/authApi";
import {orderApi, type OrdersRequest} from "@/api/orderApi";


const TIME = {
  SECOND: 1000,
  MINUTE: 60 * 1000,
  HOUR: 60 * 60 * 1000,
};

// Словарь корневых ключей
const KEYS = {
  AUTH: 'auth',
  ORDERS: 'orders',
  STATUSES: 'statuses',
} as const;

export const queries = {
  /**
   * АВТОРИЗАЦИЯ И ПРОФИЛЬ
   */
  auth: {
    me: () => queryOptions({
      queryKey: [KEYS.AUTH, 'me'] as const,
      queryFn: () => authApi.me(),
      staleTime: 30 * TIME.MINUTE,
      retry: false,
      refetchOnWindowFocus: false,
      refetchOnMount: true,
    }),

    // Мутация логина
    login: () => ({
      mutationFn: async (creds: LoginCredentials) => {
        const res = await authApi.login(creds);
        if (!res.data) throw new Error("Данные авторизации отсутствуют");
        return res.data;
      }
    }),

    // Получение ссылки для SSO
    calculatorLink: () => ({
      mutationFn: async (ligronNumber: string | null) => {
        const res = await authApi.getCalculatorLink(ligronNumber);
        if (!res.data?.url) throw new Error("Ссылка не получена");
        return res.data.url;
      }
    }),

    // Вход по временному токену
    loginByUt: () => ({
      mutationFn: async (token: string) => {
        const res = await authApi.loginByUt(token);
        if (!res.data) throw new Error("Ссылка недействительна или просрочена");
        return res.data;
      }
    }),

  },

  /**
   * ЗАКАЗЫ
   */
  orders: {
    // Базовые ключи для инвалидации
    _root: () => [KEYS.ORDERS] as const,
    _lists: () => [KEYS.ORDERS, 'list'] as const,
    _details: () => [KEYS.ORDERS, 'detail'] as const,

    // Получение списка заказов (Таблица / Мобильный список)
    list: (params: OrdersRequest) => queryOptions({
      queryKey: [...queries.orders._lists(), params] as const,
      queryFn: () => orderApi.getOrders(params),
      staleTime: 30 * TIME.SECOND,
      refetchOnWindowFocus: 'always' as const,
    }),


    // Детальная страница заказа по ID
    detail: (id: number) => queryOptions({
      queryKey: [...queries.orders._details(), id] as const,
      queryFn: () => orderApi.getOrder(id),
      staleTime: 30 * TIME.SECOND,
      retry: 1,
    }),

    // Вложенные заказы
    children: (parentId: number) => queryOptions({
      queryKey: [KEYS.ORDERS, 'children', parentId] as const,
      queryFn: () => orderApi.getOrders({filter: `parent_id=${parentId}`, is_draft: 0}),
      staleTime: 5 * TIME.MINUTE,
      enabled: !!parentId,
    }),

    // Поиск заказа по номеру Лигрон
    byNumber: (orderNumber: string) => queryOptions({
      queryKey: [KEYS.ORDERS, 'by-number', orderNumber] as const,
      queryFn: () => orderApi.getByNumber(orderNumber),
      staleTime: 0, // Всегда проверяем актуальность при поиске по номеру
      retry: false,
    }),

    // Предпросмотр JSON перед отправкой в Лигрон
    jsonPreview: (id: number) => queryOptions({
      queryKey: [KEYS.ORDERS, 'json-preview', id] as const,
      queryFn: () => orderApi.getLigronRequestData(id),
      staleTime: 0,
    }),

    // Бесконечный список заказов для мобильной версии
    infiniteList: (params: Omit<OrdersRequest, 'offset'>) => ({
      queryKey: [...queries.orders._lists(), 'infinite', params] as const,
      queryFn: async ({ pageParam = 0 }) => {
        const res = await orderApi.getOrders({
          ...params,
          offset: pageParam as number,
        });
        return res.data;
      },
      initialPageParam: 0,
      getNextPageParam: (lastPage: any, allPages: any[]) => {
        const currentTotal = allPages.reduce((acc, page) => acc + (page?.orders?.length || 0), 0);
        return lastPage && currentTotal < lastPage.pagination.total ? currentTotal : undefined;
      },
      staleTime: 30 * TIME.SECOND,
      refetchOnWindowFocus: 'always' as const,
    }),

  },

  /**
   * СПРАВОЧНИКИ
   */
  statuses: {
    all: () => queryOptions({
      queryKey: [KEYS.STATUSES] as const,
      queryFn: () => orderApi.getStatuses(),
      staleTime: 10 * TIME.MINUTE,
      gcTime: 60 * TIME.MINUTE,
    }),
  },
};