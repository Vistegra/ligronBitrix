export type PageSize = 10 | 20 | 30 | 40 | 50;
export const PAGE_SIZES = [10, 20, 30, 40, 50] as const;

export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}

export interface VisibleColumns {
  id: boolean;
  number: boolean;
  status: boolean;
  name: boolean;
  type: boolean;
  dealer: boolean;
  user: boolean;
  fabrication: boolean;
  ready_date: boolean;
  created_at: boolean;
  updated_at: boolean;
}

export interface ColumnConfig {
  key: keyof VisibleColumns;
  label: string;
  width?: string;
  managerOnly?: boolean;
}

export const COLUMNS_CONFIG: ColumnConfig[] = [
  {key: 'id', label: 'ID', width: 'w-16'},
  {key: 'number', label: 'Номер', width: 'w-24'},
  {key: 'name', label: 'Наименование заказа', width: 'w-48'},
  {key: 'type', label: 'Тип заказа', width: 'w-32'},
  {key: 'dealer', label: 'Дилер', width: 'w-40', managerOnly: true},
  {key: 'user', label: 'Пользователь', width: 'w-40', managerOnly: true},
  {key: 'status', label: 'Статус', width: 'w-32'},
  {key: 'fabrication', label: 'Изготовление', width: 'w-24'},
  {key: 'ready_date', label: 'Готовность', width: 'w-32'},
  {key: 'created_at', label: 'Создан', width: 'w-32'},
  {key: 'updated_at', label: 'Обновлен', width: 'w-32'},
];

