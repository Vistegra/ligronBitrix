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
  is_substituted: boolean;
  salons: SalonNode[];
}

/** Данные в сессии для сотрудника Лигрон */
/** Общие поля для всех типов детальных данных */
interface BaseDetailed {
  hierarchy: DealerNode[];
  available_inns: string[];
  available_salons: string[];
  session_id: string;
  fetched_at: number;
}

export interface ManagerDetailed extends BaseDetailed {
  substituting_codes: string[];
}

export interface DealerDetailed extends BaseDetailed {
  inn: string;           // Основной ИНН (для профиля)
  dealer_name: string;   // Название организации
  salon_code: string;    // Родной салон
  salon_name: string;    // Название родного салона
  managers: ManagerOfDealer[]; // Закрепленные менеджеры Лигрон
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