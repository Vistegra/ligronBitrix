// базовые константы времени
const TIME = {
  MINUTE: 60 * 1000,
  HOUR: 60 * 60 * 1000,
};

// словарь корневых ключей
const KEYS = {
  AUTH: 'auth',
  ORDERS: 'orders',
  STATUSES: 'statuses',
} as const;

export const queries = {
  /**
   * Авторизация и профиль пользователя
   */
  auth: {
    // запрос данных текущего пользователя
    me: () => ({
      queryKey: [KEYS.AUTH, 'me'] as const,
      staleTime: 30 * TIME.MINUTE,
      retry: false,
      refetchOnWindowFocus: false,
      refetchOnMount: true,
    }),
  },

  /**
   * Заказы
   */
  orders: {
    // самый верхний уровень, чтобы сбросить все запросы по заказам
    all: () => [KEYS.ORDERS] as const,

    // для сброса всех вариантов списков
    lists: () =>[KEYS.ORDERS, 'list'] as const,

    // получение списка заказов с учетом пагинации, фильтров и сортировки
    list: (params: Record<string, any>) => ({
      queryKey: [KEYS.ORDERS, 'list', params] as const,
      staleTime: 5 * TIME.MINUTE,
    }),

    // для массового сброса всех кэшированных детальных страниц
    details: () => [KEYS.ORDERS, 'detail'] as const,

    // получение конкретного заказа по его id
    detail: (id: number) => ({
      queryKey:[KEYS.ORDERS, 'detail', id] as const,
      staleTime: 5 * TIME.MINUTE,
      retry: 1,
    }),

    // вложенные заказы (дети)
    children: (parentId: number) => ({
      queryKey: [KEYS.ORDERS, 'children', parentId] as const,
      staleTime: 5 * TIME.MINUTE,
    }),

    // поиск заказа по его строковому номеру (например, из ссылки)
    byNumber: (orderNumber: string) => ({
      queryKey:[KEYS.ORDERS, 'by-number', orderNumber] as const,
      staleTime: 0,
      retry: false,
    }),

    // json данные для предпросмотра перед отправкой в лигрон
    jsonPreview: (id: number) => ({
      queryKey:[KEYS.ORDERS, 'json-preview', id] as const,
      staleTime: 0,
    }),
  },

  /**
   * Справочники
   */
  statuses: {
    // статусы заказов меняются редко, поэтому кэшируем их подольше
    all: () => ({
      queryKey:[KEYS.STATUSES] as const,
      staleTime: 10 * TIME.MINUTE,
      gcTime: 60 * TIME.MINUTE,
    })
  }
};