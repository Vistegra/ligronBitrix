import {useMemo} from 'react';

export interface Salon {
  salon_code: string;
  name: string;
}

export interface Dealer {
  inn: string;
  name: string;
  is_substituted?: boolean;
  salons: Salon[];
}

/**
 * Хук для фильтрации иерархии дилеров и их салонов.
 */
export function useSidebarFilter(
  hierarchy: Dealer[] | undefined,
  searchTerm: string
): Dealer[] {
  return useMemo(() => {
    // 1. Если иерархии нет — возвращаем пустой массив
    if (!hierarchy) return [];

    // 2. Если поиск пустой — возвращаем оригинал
    if (!searchTerm.trim()) return hierarchy;

    const term = searchTerm.toLowerCase().trim();

    return hierarchy
      .map((dealer) => {
        // Проверяем совпадения у дилера (имя или ИНН)
        const dealerNameMatches = dealer.name.toLowerCase().includes(term);
        const dealerInnMatches = dealer.inn.includes(term);

        // Проверяем совпадения у салонов (имя или код)
        const matchingSalons = dealer.salons?.filter(
          (salon) =>
            salon.name.toLowerCase().includes(term) ||
            salon.salon_code.includes(term)
        );

        const hasMatchingSalons = matchingSalons && matchingSalons.length > 0;

        // Если совпал сам дилер — отдаем его со всеми салонами
        if (dealerNameMatches || dealerInnMatches) {
          return dealer;
        }

        // Если совпали только салоны — отдаем дилера только с этими салонами
        if (hasMatchingSalons) {
          return {
            ...dealer,
            salons: matchingSalons,
          };
        }

        // Если ничего не совпало — помечаем на удаление
        return null;
      })
      .filter((item): item is Dealer => item !== null); // Type Guard для фильтрации null
  }, [hierarchy, searchTerm]);
}