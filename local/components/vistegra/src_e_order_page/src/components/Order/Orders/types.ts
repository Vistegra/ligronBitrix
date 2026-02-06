export type PageSize = 10 | 20 | 30 | 40 | 50;
export const PAGE_SIZES = [10, 20, 30, 40, 50] as const;

export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}

export type ColumnKey =
  /*| 'id' |*/ 'number' | 'status' | 'name' | 'type' | 'origin'
  | 'dealer' | 'user' | 'production_time' | 'ready_date' | 'percent_payment'
  | 'created_at' | 'updated_at';

type VisibleColumns = Record<ColumnKey, boolean>;
export type PartVisibleColumns = Partial<VisibleColumns>

export interface ColumnDefinition {
  key: ColumnKey;
  label: string;
  width: string;
  sortable?: boolean;
}

export const COLUMN_DEFINITIONS: Record<ColumnKey, ColumnDefinition> = {
 /* id: {key: 'id', label: 'ID', width: 'w-16', sortable: true},*/
  number: {key: 'number', label: 'Номер', width: 'w-24', sortable: true},
  name: {key: 'name', label: 'Наименование заказа', width: ''}, //резиновая
  type: {key: 'type', label: 'Тип заказа', width: 'w-32'},
  origin: {key: 'origin', label: 'Источник', width: 'w-24'},
  dealer: {key: 'dealer', label: 'Дилер', width: 'w-40'},
  user: {key: 'user', label: 'Пользователь', width: 'w-40'},
  status: {key: 'status', label: 'Статус', width: 'w-48'},
  production_time: {key: 'production_time', label: 'Изготовление', width: 'w-24', sortable: true},
  ready_date: {key: 'ready_date', label: 'Готовность', width: 'w-32', sortable: true},
  percent_payment: {key: 'percent_payment', label: 'Оплата', width: 'w-24', sortable: true},
  created_at: {key: 'created_at', label: 'Создан', width: 'w-32', sortable: true},
  updated_at: {key: 'updated_at', label: 'Обновлен', width: 'w-32', sortable: true},
};

export const COLUMNS_VISIBILITY_PRESETS: Record<string, Partial<VisibleColumns>> = {
  'default': {
    /*id: true,*/
    number: true,
    name: true,
    type: true,
    origin: true,
    status: true,
    production_time: true,
    ready_date: false,
    percent_payment: false,
    created_at: true,
    updated_at: true,
  },
  'draft': {
   /* id: true,*/
    name: true,
    created_at: true,
    updated_at: false,
  },
  'manager': {
  /*  id: true,*/
    number: true,
    name: true,
    type: true,
    origin: true,
    dealer: true,
    user: true,
    status: true,
    production_time: true,
    ready_date: false,
    percent_payment: false,
    created_at: true,
    updated_at: true,
  }
}

export interface OrderFilterState {
  search: string;
  status_id: number[];
  dealer_prefix: string | null;
  dealer_user_id: number | null;
  origin_type: number[];
  created_at_from: string;
  created_at_to: string;
  updated_at_from: string;
  updated_at_to: string;
  // percent_payment: number[] и т.д.
}


