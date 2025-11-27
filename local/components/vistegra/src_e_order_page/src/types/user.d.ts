export type ProviderType = 'dealer' | 'ligron';
export type UserRole = 'manager' | 'office_manager'

export interface ManagedDealer {
  inn: string;
  dealer_id: number;
  name: string;
  dealer_prefix: string;
  users: Array<{ id: number; name: string }>;
}

export interface ManagerDetailed {
  managed_dealers?: ManagedDealer[];
  session_id: string;
  validation_key: string;
  fetched_at: number; // timestamp
}

export interface DealerDetailed {
  dealer_name: string;
  salon_name: string;
  salon_code: string;
  inn: string;
  managers: Array<{
    code_user: string;
    name: string;
    email: string;
    phone: string;
    role: "manager" | "office_manager";
  }>;
  session_id: string;
  validation_key: string;
  fetched_at: number; // timestamp
}

type BaseUser = {
  id: number;
  name: string;
  login: string;
  email?: string;
  phone?: string;
};

type LigronUser = BaseUser & {
  provider: 'ligron';
  role: 'manager' | 'office_manager';
  detailed?: ManagerDetailed
};

type DealerUser = BaseUser & {
  dealer_id: number;
  dealer_prefix: string;
  provider: 'dealer';
  role: 'dealer';
  detailed?: DealerDetailed
};

type User = LigronUser | DealerUser;