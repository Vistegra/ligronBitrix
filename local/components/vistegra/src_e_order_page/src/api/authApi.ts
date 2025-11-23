import api, { type ApiResponse } from "./client";
import { ENDPOINT } from "./constants";
import type {ProviderType, User, ManagerDetailed, DealerDetailed} from "@/types/user";


export interface LoginResponse {
  user: User;
  token: string;
  expires_in: number;
  token_type: string;
  provider: ProviderType;
}

export interface ManagerDetailedResponse {
  detailed: ManagerDetailed;
}

export interface DealerDetailedResponse {
  detailed: DealerDetailed;
}

export type DetailedResponse = ManagerDetailedResponse | DealerDetailedResponse

export interface LoginCredentials {
  login: string;
  password: string;
  providerType: ProviderType;
}

export const authApi = {

  /** POST /auth/login */
  async login(credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> {
    const response = await api.post(ENDPOINT.AUTH_LOGIN, credentials);

    return response.data;
  },

  /** GET /auth/me — детальные данные (managed_dealers, salon_name и т.д.) */
  async me(): Promise<ApiResponse<DetailedResponse>> {
    const response = await api.get(ENDPOINT.AUTH_DETAILED);

    return response.data;
  },

};