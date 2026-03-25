import type {Order} from "@/api/orderApi.ts";
import {format, fromUnixTime} from "date-fns";
import {ru} from "date-fns/locale";
import type {User} from "@/types/user";

/**
 * Определяет тип заказа на основе иерархических связей
 */
export const getOrderType = (order: Order): string => {
  if (order.parent_id !== null) return "individual";
  if (order.children_count && order.children_count > 0) return "complex";
  return "standard";
};

/**
 * Преобразует системный тип заказа в читаемое название на русском
 */
export const getOrderTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    individual: "Индивидуальный",
    standard: "Стандартный",
    complex: "Комплексный",
  };
  return labels[type] || type;
};

/**
 * Форматирует UNIX-таймстамп в строку даты и времени
 */
export const formatDateTime = (timestamp?: number): string =>
  timestamp
    ? format(fromUnixTime(timestamp), "dd.MM.yyyy HH:mm", {locale: ru})
    : "—";

/**
 * Форматирует UNIX-таймстамп в строку даты (без времени)
 */
export const formatDate = (timestamp?: number): string =>
  timestamp
    ? format(fromUnixTime(timestamp), "dd.MM.yyyy", {locale: ru})
    : "—";

/**
 * Вспомогательная функция для получения дерева иерархии из профиля
 */
const getHierarchy = (user: User | null) => {
  return user?.detailed?.hierarchy || [];
};

/**
 * Получить имя дилера по ИНН.
 */
export const getDealerNameByOrder = (user: User | null, order: Order) => {
  /* if (!order.dealer_prefix) return "—";
  const dealers = getManagedDealers(user);
  const dealer = dealers.find((d) => d.dealer_prefix === order.dealer_prefix);
  return dealer ? dealer.name : order.dealer_prefix; */

  const inn = order.inn_dealer;
  if (!inn) return "—";

  const hierarchy = getHierarchy(user);
  const dealer = hierarchy.find(d => d.inn === inn);

  return dealer ? dealer.name : inn;
};

/**
 * Получить название салона по коду (V2).
 */
export const getSalonNameByOrder = (user: User | null, order: Order) => {
  const inn = order.inn_dealer;
  const code = order.salon_code;
  if (!code) return "—";

  const hierarchy = getHierarchy(user);
  const dealer = hierarchy.find(d => d.inn === inn);

  if (!dealer) return code;

  const salon = dealer.salons.find(s => s.salon_code === code);
  return salon ? salon.name : code;
};

/**
 * Получить имя пользователя по ID (устарело в V2, так как перешли на салоны).
 */
export const getUserNameByOrder = (_user: User | null, order: Order) => {
  /* if (!order.dealer_prefix || !order.dealer_user_id) return "—";
  const dealers = getManagedDealers(user);
  const dealer = dealers.find((d) => d.dealer_prefix === order.dealer_prefix);
  if (!dealer) return String(order.dealer_user_id);
  const appUser = dealer.users.find((u) => u.id === order.dealer_user_id);
  return appUser ? appUser.name : String(order.dealer_user_id); */

  return order.dealer_user_id ? `ID ${order.dealer_user_id}` : "—";
};


/**
 * Проверка прав на создание заказа.
 * Заказ можно создать только если в контексте (contextStore)
 * выбран один конкретный дилер (ИНН) и один конкретный салон.
 */
export const checkCanCreateOrder = ( //ToDo user_id
  user: User | null,
  inn: string | null,
  salonCode: string | null
): boolean => {
  if (!user) return false;

  // Кнопка доступна только если выбран и дилер, и салон
  return !!inn && !!salonCode;
};

/**
 * Генерация ссылки на заказ.
 */
export const buildOrderLink = (
  basePage: string,
  order: { id: number; inn_dealer?: string | null; salon_code?: string | null }
) => {
  const params = new URLSearchParams();

  /* if (order.dealer_prefix) {
    params.set("dealer_prefix", order.dealer_prefix);
  }
  if (order.dealer_user_id) {
    params.set("dealer_user_id", String(order.dealer_user_id));
  } */

  // Передаем новый контекст ИНН + Салон
  if (order.inn_dealer) params.set("inn_dealer", order.inn_dealer);
  if (order.salon_code) params.set("salon_code", order.salon_code);

  const queryString = params.toString();
  return `${basePage}/${order.id}${queryString ? `?${queryString}` : ""}`;
};

export const getOriginLabel = (type: number): { label: string; color: string } => {
  switch (type) {
    case 1:
      return {label: "1C", color: "bg-yellow-100 text-yellow-800 border-yellow-200"};
    case 2:
      return {label: "Калькулятор", color: "bg-green-100 text-green-800 border-green-200"};
    default:
      return {label: "Сайт", color: "bg-gray-100 text-gray-800 border-gray-200"};
  }

};