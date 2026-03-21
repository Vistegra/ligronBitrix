export type ProviderType = 'dealer' | 'ligron';

/** Роли Дилеров: M (Менеджер), MS (Менеджер салона), LM (Лигрон Менеджер у дилера) */
export type DealerRole = 'M' | 'MS' | 'LM';

/** Роли Лигрон: ML (Менеджер Лигрон), OML (Офисный менеджер Лигрон) */
export type LigronRole = 'ML' | 'OML';

export type UserRole = DealerRole | LigronRole;

export interface ManagerOfDealer {
  code_user: string;
  name: string;
  email: string;
  phone: string;
  role: string; // Можно уточнить 'manager' | 'office_manager' если бэк всегда шлет так
  is_on_vacation: boolean;
  is_substitute: boolean;
  substituting_for: string | null;
}

export interface SalonNode {
  salon_code: string;
  name: string;
}

export interface DealerNode {
  inn: string;
  name: string;
  salons: SalonNode[];
}

/** Данные в сессии для сотрудника Лигрон */
export interface ManagerDetailed {
  hierarchy: DealerNode[]; // Дерево всех доступных дилеров и их салонов
  available_inns: string[]; // Плоский список ИНН для фильтрации
  session_id: string;
  fetched_at: number;
}

/** Данные в сессии для сотрудника Дилера */
export interface DealerDetailed {
  inn: string;
  dealer_name: string;
  salon_code: string;
  salon_name: string;
  available_inns: string[];
  available_salons: string[];
  hierarchy: DealerNode[];
  managers: ManagerOfDealer[];
  session_id: string;
  fetched_at: number;
}

interface BaseUser {
  id: number;
  name: string;
  login: string;
  email?: string;
  phone?: string;
}

export type LigronUser = BaseUser & {
  provider: 'ligron';
  role: LigronRole;
  user_code: string;
  detailed?: ManagerDetailed;
};

export type DealerUser = BaseUser & {
  provider: 'dealer';
  role: DealerRole;
  inn_dealer: string;
  salon_code: string;
  detailed?: DealerDetailed;
};

export type User = LigronUser | DealerUser;