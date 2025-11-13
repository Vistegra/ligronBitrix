export type PageSize = 10 | 20 | 30 | 40 | 50;
export const PAGE_SIZES = [10, 20, 30, 40, 50] as const;
export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}

export interface VisibleColumns {
  status: boolean;
  name: boolean;
  type: boolean;
  fabrication: boolean;
  ready_date: boolean;
  created_at: boolean;
}

// types.ts
export interface VisibleColumns {
  status: boolean;
  name: boolean;
  type: boolean;
  fabrication: boolean;
  ready_date: boolean;
  created_at: boolean;
}

export interface Pagination {
  limit: number;
  offset: number;
  total: number;
}


export interface ColumnConfig {
  key: keyof VisibleColumns;
  label: string;
  width?: string;
}

export const COLUMNS_CONFIG: ColumnConfig[] = [
  { key: 'name', label: 'Наименование заказа', width: 'w-48' },
  { key: 'type', label: 'Тип заказа', width: 'w-32' },
  { key: 'status', label: 'Статус', width: 'w-32' },
  { key: 'fabrication', label: 'Изготовление', width: 'w-24' },
  { key: 'ready_date', label: 'Готовность', width: 'w-32' },
  { key: 'created_at', label: 'Дата создания', width: 'w-32' },
];
