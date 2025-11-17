import { useState, useEffect } from "react";
import { orderApi, type Order } from "@/api/orderApi";

export function useChildOrders(parentId: number) {
  const [children, setChildren] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetch = async () => {
      setLoading(true);
      try {
        const res = await orderApi.getOrders({ filter: `parent_id=${parentId}` });
        if (res.status === "success") {
          setChildren(res.data.orders);
        }
      } catch {
        // ignore
      } finally {
        setLoading(false);
      }
    };
    fetch();
  }, [parentId]);

  return { children, loading };
}