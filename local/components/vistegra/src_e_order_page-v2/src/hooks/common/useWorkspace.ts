import {useCallback, useMemo} from "react";
import {useNavigate} from "react-router-dom";
import {useContextStore} from "@/store/contextStore";
import {useAuthStore} from "@/store/authStore";
import {PAGE} from "@/api/constants";

export function useWorkspace() {
  const {inn, salonCode, _set, _clear} = useContextStore();
  const {user} = useAuthStore();
  const navigate = useNavigate();

  const hierarchy = user?.detailed?.hierarchy || [];

  const current = useMemo(() => {
    // Если в сторе совсем пусто — данных нет
    if (!inn && !salonCode) return null;

    let foundDealer = hierarchy.find(d => d.inn === inn);
    let foundSalon = null;

    if (foundDealer) {
      foundSalon = foundDealer.salons.find(s => s.salon_code === salonCode);
    } else if (salonCode) {
      // Поиск бесппризорного салона
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
  }, [inn, salonCode, hierarchy]);

  //Формирование ссылок с контекстом для сохранения контекста между страницами
  const getContextLink = useCallback((basePath: string) => {
    if (!inn && !salonCode) return basePath;

    const params = new URLSearchParams();

    if (inn) params.set("inn_dealer", inn);
    if (salonCode) params.set("salon_code", salonCode);

    params.set("offset", "0");

    return `${basePath}?${params.toString()}`;
  }, [inn, salonCode]);


  const setWorkspace = useCallback((newInn: string | null, newSalon: string | null) => {
    _set(newInn, newSalon);

    const params = new URLSearchParams();

    if (newInn) params.set("inn_dealer", newInn);
    if (newSalon) params.set("salon_code", newSalon);

    navigate(`${PAGE.ORDERS}?${params.toString()}`);
  }, [_set, navigate]);

  const resetWorkspace = useCallback(() => {
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