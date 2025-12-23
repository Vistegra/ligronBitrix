import api, {makeRequest} from "./client";
import {ENDPOINT} from "./constants";
import type {ProviderType, User, ManagerDetailed, DealerDetailed} from "@/types/user";

export interface LoginResponse {
  user: User;
  token: string;
  expires_in: number;
  token_type: string;
  provider: ProviderType;
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
  getCalculatorLink(ligron_number: string | null = null) {
    const url = ligron_number
      ? `${ENDPOINT.AUTH_SSO}?ligron_number=${ligron_number}`
      : ENDPOINT.AUTH_SSO;

    return makeRequest<{ url: string }>(() =>
      api.get(url)
    );
  },
};