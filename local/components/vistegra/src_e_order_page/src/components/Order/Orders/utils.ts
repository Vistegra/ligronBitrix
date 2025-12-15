import type {Order} from "@/api/orderApi.ts";
import {format, fromUnixTime} from "date-fns";
import {ru} from "date-fns/locale";
import type {ManagerDetailed, User} from "@/types/user";

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
 * Вспомогательная функция для безопасного получения списка управляемых дилеров
 * из объекта пользователя.
 */
const getManagedDealers = (user: User | null) => {
  // Список дилеров есть только у менеджеров Ligron
  if (user?.provider === 'ligron') {
    return (user.detailed as ManagerDetailed)?.managed_dealers || [];
  }
  return [];
};

/**
 * Получить имя дилера по префиксу.
 * Принимает объект User целиком.
 */
export const getDealerNameByOrder = (user: User | null, order: Order) => {
  if (!order.dealer_prefix) return "—";
  const dealers = getManagedDealers(user);
  const dealer = dealers.find((d) => d.dealer_prefix === order.dealer_prefix);

  return dealer ? dealer.name : order.dealer_prefix;
};

/**
 * Получить имя пользователя по ID.
 * Принимает объект User целиком.
 */
export const getUserNameByOrder = (user: User | null, order: Order) => {
  if (!order.dealer_prefix || !order.dealer_user_id) return "—";

  const dealers = getManagedDealers(user);

  const dealer = dealers.find((d) => d.dealer_prefix === order.dealer_prefix);
  // Если дилер не найден в списке управляемых — возвращаем ID как строку
  if (!dealer) return String(order.dealer_user_id);

  const appUser = dealer.users.find((u) => u.id === order.dealer_user_id);
  return appUser ? appUser.name : String(order.dealer_user_id);
};


/**
 * Проверка прав на создание заказа
 * Дилер может всегда.
 * Менеджер Лигрон - только если выбрал конкретного пользователя (суб-аккаунт дилера).
 */
export const checkCanCreateOrder = (user: User | null, selectedUserId: number | null): boolean => {
  if (!user) return false;
  return (
    user.role === 'dealer' ||
    (user.role === 'manager' && !!selectedUserId)
  );
};

/**
 * Генерация ссылки на заказ
 * Принимает объект заказа (достаточно полей id, dealer_prefix, dealer_user_id)
 */
export const buildOrderLink = (
  basePage: string,
  order: { id: number; dealer_prefix?: string | null; dealer_user_id?: number | null }
) => {
  const params = new URLSearchParams();

  // Добавляем контекст только если он есть в заказе
  if (order.dealer_prefix) {
    params.set("dealer_prefix", order.dealer_prefix);
  }
  if (order.dealer_user_id) {
    params.set("dealer_user_id", String(order.dealer_user_id));
  }

  const queryString = params.toString();
  return `${basePage}/${order.id}${queryString ? `?${queryString}` : ""}`;
};