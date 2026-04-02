import type {DealerRole, LigronRole} from "@/types/user";

/**
 * Роли, которые имеют доступ ко всей базе (Режим Бога)
 */
export const GLOBAL_ROLES: string[] = ['OML', 'GOD_LIGRON', 'GOD_DEALER'];

/**
 * Проверка, является ли роль глобальной
 */
export const isGlobalRole = (role?: string): boolean => {
  return !!role && GLOBAL_ROLES.includes(role);
};

/**
 * Маппинг для отображения названий
 */
export const ROLE_NAMES: Record<DealerRole | LigronRole | string, string> = {
  'M': 'Менеджер дилера',
  'MS': 'Менеджер салона',
  'LM': 'Лигрон менеджер (дил.)',
  'ML': 'Менеджер Лигрон',
  'OML': 'Офис-менеджер Лигрон',
  'GOD_LIGRON': 'Бог Лигрон',
  'GOD_DEALER': 'Бог Дилера'
};