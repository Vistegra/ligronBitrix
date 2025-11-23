export type ProviderType = 'dealer' | 'ligron';

export interface ManagedDealer {
  inn: string;
  dealer_id: number;
  name: string;
  dealer_prefix: string;
  users: Array<{ id: number; name: string }>;
}

export interface ManagerDetailed {
  managed_dealers?: ManagedDealer[];
}

export interface DealerDetailed {
    salon_name: string;
    salon_code: string;
}

type BaseUser = {
  id: number;
  name: string;
  login: string;
  email?: string;
  phone?: string;
};

type LigronUser = BaseUser & {
  provider: "ligron";
  role: "manager" | "office_manager";
  detailed?: ManagerDetailed
};

type DealerUser = BaseUser & {
  dealer_id: number;
  dealer_prefix: string;
  provider: "dealer";
  role: "dealer"
  detailed?: DealerDetailed
};

type User = LigronUser | DealerUser;