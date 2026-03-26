import {useEffect, useRef} from "react";
import {useLocation} from "react-router-dom";
import {useContextStore} from "@/store/contextStore";
import {useAuthStore} from "@/store/authStore";
import {PAGE} from "@/api/constants";
import type {OrderFilterState} from "@/components/Order/Orders/types";

export function useContextSync(
  activeFilters: OrderFilterState,
  updateFilters: (patch: Partial<OrderFilterState>) => void
) {
  const {_set, _clear, inn, salonCode} = useContextStore();
  const {user} = useAuthStore();
  const location = useLocation();

  const hasAttemptedRestore = useRef(false);
  const hierarchy = user?.detailed?.hierarchy || [];

  useEffect(() => {
      const isListPage = location.pathname === PAGE.ORDERS || location.pathname === PAGE.DRAFTS;
      if (!isListPage) {
        hasAttemptedRestore.current = false;
        return;
      }

      const urlInns = activeFilters.inn_dealer || [];
      const urlSalons = activeFilters.salon_code || [];

      if (!hasAttemptedRestore.current && urlInns.length === 0 && urlSalons.length === 0 && (inn || salonCode)) {

        hasAttemptedRestore.current = true;
        updateFilters({
          inn_dealer: inn ? [inn] : [],
          salon_code: salonCode ? [salonCode] : []
        });
        return;
      }

      hasAttemptedRestore.current = true;

      // Очистка слонов без дилеров
      if (urlInns.length > 0 && urlSalons.length > 0) {
        const allowedCodes = hierarchy
          .filter(d => urlInns.includes(d.inn))
          .flatMap(d => d.salons.map(s => s.salon_code));

        const validSalons = urlSalons.filter(code => allowedCodes.includes(code));

        if (validSalons.length !== urlSalons.length) {
          updateFilters({salon_code: validSalons});
          return;
        }
      }

      // Синхронизация в сторе
      const nextInn = urlInns.length === 1 ? urlInns[0] : null;
      const nextSalon = urlSalons.length === 1 ? urlSalons[0] : null;

      if (nextInn !== inn || nextSalon !== salonCode) {
        // Очищаем стор только если в URL пусто или выбрано несколько ИНН и салонов
        if (!nextInn && !nextSalon) {
          if (inn !== null || salonCode !== null) _clear();
        } else {
          _set(nextInn, nextSalon);
        }
      }

    },
    [
      activeFilters.inn_dealer,
      activeFilters.salon_code,
      location.pathname,
      hierarchy,
      _set, _clear,
      inn, salonCode,
      updateFilters
    ]);
}