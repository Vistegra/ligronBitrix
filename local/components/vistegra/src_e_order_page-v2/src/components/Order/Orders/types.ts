export type PageSize = 10 | 20 | 30 | 40 | 50;
export const PAGE_SIZES: PageSize[] = [10, 20, 30, 40, 50];

export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}

/** Ключи доступных колонок в таблице */
export type ColumnKey =
  | 'number'
  | 'status'
  | 'name'
  | 'type'
  | 'origin'
  | 'inn_dealer'
  | 'salon_code'
  | 'production_time'
  | 'ready_date'
  | 'percent_payment'
  | 'due_payment'
  | 'created_at'
  | 'updated_at';

/** Тип для управления видимостью колонок */
export type PartVisibleColumns = Partial<Record<ColumnKey, boolean>>;

export interface ColumnDefinition {
  key: ColumnKey;
  label: string;
  width: string;
  sortable?: boolean;
}

export const COLUMN_DEFINITIONS: Record<ColumnKey, ColumnDefinition> = {
  number: { key: 'number', label: 'Номер', width: 'w-24', sortable: true },
  name: { key: 'name', label: 'Наименование заказа', width: '', sortable: true },
  type: { key: 'type', label: 'Тип заказа', width: 'w-32' },
  origin: { key: 'origin', label: 'Источник', width: 'w-24' },
  inn_dealer: { key: 'inn_dealer', label: 'Дилер (ИНН)', width: 'w-40' },
  salon_code: { key: 'salon_code', label: 'Салон', width: 'w-40' },
  status: { key: 'status', label: 'Статус', width: 'w-48' },
  production_time: { key: 'production_time', label: 'Изготовление', width: 'w-24', sortable: true },
  ready_date: { key: 'ready_date', label: 'Готовность', width: 'w-32', sortable: true },
  percent_payment: { key: 'percent_payment', label: 'Оплата', width: 'w-24', sortable: true },
  due_payment: { key: 'due_payment', label: 'Осталось оплатить', width: 'w-32', sortable: true },
  created_at: { key: 'created_at', label: 'Создан', width: 'w-32', sortable: true },
  updated_at: { key: 'updated_at', label: 'Обновлен', width: 'w-32', sortable: true },
};

/** Пресеты видимости для разных ролей/страниц */
export const COLUMNS_VISIBILITY_PRESETS: Record<'default' | 'draft' | 'manager', PartVisibleColumns> = {
  'default': {
    number: true,
    name: true,
    origin: true,
    inn_dealer: true,
    salon_code: true,
    status: true,
    production_time: true,
    created_at: true,
    updated_at: true,
  },
  'draft': {
    name: true,
    created_at: true,
  },
  'manager': {
    number: true,
    name: true,
    origin: true,
    inn_dealer: true,
    salon_code: true,
    status: true,
    due_payment: true,
    created_at: true,
    updated_at: true,
  }
};

/** Состояние фильтров в URL и UI */
export interface OrderFilterState {
  search: string;
  status_id: number[];
  inn_dealer: string[];
  salon_code: string[];
  origin_type: number[];
  created_at_from: string;
  created_at_to: string;
  updated_at_from: string;
  updated_at_to: string;
}