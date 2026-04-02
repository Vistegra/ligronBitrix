import api, {makeRequest} from "./client";
import {ENDPOINT} from "./constants";
import type {DealerDetailed, ManagerDetailed, ProviderType, User} from "@/types/user";

export interface LoginResponse {
  user: User;
  token: string;
  expires_in: number;
  token_type: string;
  provider: ProviderType;
}

export interface SsoLinkParams {
  ligron_number?: string | null;
  inn_dealer?: string | null;
  salon_code?: string | null;
}

export type DetailedResponse = { detailed: ManagerDetailed } | { detailed: DealerDetailed };

export interface LoginCredentials {
  login: string;
  password: string;
  providerType: ProviderType;
}

export const authApi = {
  /** Логин */
  login(credentials: LoginCredentials) {
    return makeRequest<LoginResponse>(() =>
      api.post(ENDPOINT.AUTH_LOGIN, credentials)
    );
  },

  /** Получение детальных данных профиля */
  me() {
    return makeRequest<DetailedResponse>(() =>
      api.get(ENDPOINT.AUTH_DETAILED)
    );
  },

  /** Вход по специальному токену из URL */
  loginByUt(user_token: string) {
    return makeRequest<LoginResponse>(() =>
      api.post('/auth/login-by-token', { user_token })
    );
  },

  /**
   * Получение SSO ссылки для калькулятора
   * Если передан ligron_number, генерируется ссылка на конкретный заказ
   */
  getCalculatorLink(params: SsoLinkParams = {}) {
    const searchParams = new URLSearchParams();

    if (params.ligron_number) searchParams.append('ligron_number', params.ligron_number);
    if (params.inn_dealer) searchParams.append('inn_dealer', params.inn_dealer);
    if (params.salon_code) searchParams.append('salon_code', params.salon_code);

    const queryString = searchParams.toString();
    const url = queryString ? `${ENDPOINT.AUTH_SSO}?${queryString}` : ENDPOINT.AUTH_SSO;

    return makeRequest<{ url: string }>(() => api.get(url));
  },

};