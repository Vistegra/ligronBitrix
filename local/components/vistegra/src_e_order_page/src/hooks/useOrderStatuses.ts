import { useState, useEffect } from "react";
import { orderApi } from "@/api/orderApi";
import type { OrderStatus } from "@/api/orderApi";


let cachedStatuses: OrderStatus[] | null = null;
let loadPromise: Promise<void> | null = null;

export function useOrderStatuses() {
    const [loading, setLoading] = useState(!cachedStatuses);

    useEffect(() => {
        // Если уже есть кэш или идёт загрузка — ничего не делаем
        if (cachedStatuses || loadPromise) return;

        loadPromise = (async () => {
            try {
                setLoading(true);
                const response = await orderApi.getStatuses();
                if (response.status === "success") {
                    cachedStatuses = response.data;
                }
            } catch (error) {
                console.warn("Не удалось загрузить статусы:", error);
                //toast.error('Ошибка сети!')
            } finally {
                setLoading(false);
                loadPromise = null;
            }
        })();

        loadPromise.catch(() => {});
    }, []);

    const getStatusById = (id: number): OrderStatus | undefined =>
        cachedStatuses?.find(s => s.id === id);

    const getStatusByCode = (code: string): OrderStatus | undefined =>
        cachedStatuses?.find(s => s.code === code);

    return {
        loading,
        statuses: cachedStatuses || [],
        getStatusById,
        getStatusByCode,
    };
}