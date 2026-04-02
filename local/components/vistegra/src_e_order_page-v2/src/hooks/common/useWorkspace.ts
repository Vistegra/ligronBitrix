import {useCallback, useMemo} from "react";
import {useNavigate} from "react-router-dom";
import {useContextStore} from "@/store/contextStore";
import {useAuthStore} from "@/store/authStore";
import {PAGE} from "@/api/constants";
import type {DealerNode, SalonNode} from "@/types/user";

export interface WorkspaceContext {
  inn: string | null;
  salonCode: string | null;
  dealerName: string | null;
  salonName: string | null;
}

export function useWorkspace() {
  const {inn, salonCode, _set, _clear} = useContextStore();
  const {user} = useAuthStore();
  const navigate = useNavigate();

  const current = useMemo<WorkspaceContext | null>(() => {
    // Если в сторе совсем пусто - данных нет
    if (!inn && !salonCode) return null;

    const hierarchy: DealerNode[] = user?.detailed?.hierarchy || [];

    let foundDealer: DealerNode | undefined = hierarchy.find(d => d.inn === inn);
    let foundSalon: SalonNode | undefined = undefined;

    if (foundDealer) {
      foundSalon = foundDealer.salons.find(s => s.salon_code === salonCode);
    } else if (salonCode) {
      // Поиск беспризорного салона
      for (const dNode of hierarchy) {
        const s = dNode.salons.find(item => item.salon_code === salonCode);
        if (s) {
          foundSalon = s;
          foundDealer = dNode;
          break;
        }
      }
    }

    return {
      inn,
      salonCode,
      dealerName: foundDealer?.name || (inn ? `ИНН ${inn}` : null),
      salonName: foundSalon?.name || (salonCode ? `Салон ${salonCode}` : null),
    };

  }, [inn, salonCode, user?.detailed?.hierarchy]);

  // Формирование ссылок с контекстом для сохранения контекста между страницами
  const getContextLink = useCallback((basePath: string): string => {
    if (!inn && !salonCode) return basePath;

    const params = new URLSearchParams();

    if (inn) params.set("inn_dealer", inn);
    if (salonCode) params.set("salon_code", salonCode);

    params.set("offset", "0");

    return `${basePath}?${params.toString()}`;
  }, [inn, salonCode]);

  const setWorkspace = useCallback((newInn: string | null, newSalon: string | null): void => {
    _set(newInn, newSalon);

    const params = new URLSearchParams();

    if (newInn) params.set("inn_dealer", newInn);
    if (newSalon) params.set("salon_code", newSalon);

    navigate(`${PAGE.ORDERS}?${params.toString()}`);
  }, [_set, navigate]);

  const resetWorkspace = useCallback((): void => {
    _clear();
    navigate(PAGE.ORDERS);
  }, [_clear, navigate]);

  return {
    inn,
    salonCode,
    current,
    isSet: !!inn,
    setWorkspace,
    resetWorkspace,
    getContextLink
  };
}