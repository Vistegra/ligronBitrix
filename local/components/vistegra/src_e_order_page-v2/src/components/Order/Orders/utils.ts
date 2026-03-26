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
 * Преобразует системный тип заказа в читаемое название
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
 * Форматирует таймстамп в строку даты и времени
 */
export const formatDateTime = (timestamp?: number): string =>
  timestamp
    ? format(fromUnixTime(timestamp), "dd.MM.yyyy HH:mm", {locale: ru})
    : "—";

/**
 * Форматирует таймстамп в строку даты (без времени)
 */
export const formatDate = (timestamp?: number): string =>
  timestamp
    ? format(fromUnixTime(timestamp), "dd.MM.yyyy", {locale: ru})
    : "—";

/**
 * Получить имя дилера по ИНН из иерархии пользователя
 */
export const getDealerNameByOrder = (user: User | null, order: Order): string => {
  const inn = order.inn_dealer;
  if (!inn) return "—";

  const hierarchy = user?.detailed?.hierarchy || [];
  const dealer = hierarchy.find(d => d.inn === inn);

  return dealer ? dealer.name : `ИНН ${inn}`;
};

/**
 * Получить название салона по коду из иерархии пользователя
 */
export const getSalonNameByOrder = (user: User | null, order: Order): string => {
  const inn = order.inn_dealer;
  const code = order.salon_code;
  if (!code) return "—";

  const hierarchy = user?.detailed?.hierarchy || [];
  const dealer = hierarchy.find(d => d.inn === inn);

  if (!dealer) return code;

  const salon = dealer.salons.find(s => s.salon_code === code);

  return salon ? salon.name : code;
};

/**
 * Проверка прав на создание заказа
 */
export const checkCanCreateOrder = (
  user: User | null,
  inn: string | null,
  salonCode: string | null
): boolean => {
  if (!user) return false;
  // Заказ создается только при наличии четко выбранного дилера и салона
  return !!inn && !!salonCode;
};

/**
 * Генерация ссылки на заказ с сохранением контекста
 */
export const buildOrderLink = (
  basePage: string,
  order: { id: number; inn_dealer?: string | null; salon_code?: string | null }
) => {
  const params = new URLSearchParams();
  if (order.inn_dealer) params.set("inn_dealer", order.inn_dealer);
  if (order.salon_code) params.set("salon_code", order.salon_code);

  const queryString = params.toString();

  return `${basePage}/${order.id}${queryString ? `?${queryString}` : ""}`;
};

/**
 * Лейблы источников
 */
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