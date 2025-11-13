"use client";

import { useState } from "react";
import { Table, TableBody } from "@/components/ui/table";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertCircle } from "lucide-react";

import { useOrders } from "@/hooks/useOrders";
import { useOrderStatuses } from "@/hooks/useOrderStatuses";

import { OrdersTableBody } from "./OrdersTableBody";
import { OrdersTableSkeleton } from "./OrdersTableSkeleton";
import { OrdersTableEmpty } from "./OrdersTableEmpty";
import { OrdersPagination } from "./OrdersPagination";
import type { VisibleColumns, PageSize } from "./types";

import {OrdersTablePanel} from "./OrderTablePanel.tsx";
import {OrdersTableHeader} from "./OrdersTableHeader.tsx";

export default function OrdersTable() {
  const [visibleColumns, setVisibleColumns] = useState<VisibleColumns>({
    status: true,
    name: true,
    type: true,
    fabrication: true,
    ready_date: true,
    created_at: true,
  });


  const {
    orders, loading, error, pagination, filter,
    fetchOrders, setLimit, setFilter,
  } = useOrders({ limit: 10 });

  const { statuses, loading: statusesLoading } = useOrderStatuses();

  const handleStatusToggle = (statusIds: number[]) => {
    setFilter(statusIds.length ? `status_id=${statusIds.join(",")}` : "");
  };

  const handlePageChange = (offset: number) => fetchOrders(offset, filter);
  const handlePageSizeChange = (size: PageSize) => setLimit(size);

  if (error) {
    return (
      <Alert variant="destructive">
        <AlertCircle className="h-4 w-4" />
        <AlertDescription>{error}</AlertDescription>
      </Alert>
    );
  }

  const totalPages = Math.ceil(pagination.total / pagination.limit);

  return (
    <div className="space-y-6">
      <OrdersTablePanel visibleColumns={visibleColumns} setVisibleColumns={setVisibleColumns} />

      <div className="rounded-md border">
        <Table>
          <OrdersTableHeader
            visibleColumns={visibleColumns}
            filter={filter}
            statuses={statuses}
            statusesLoading={statusesLoading}
            onStatusToggle={handleStatusToggle}
          />

          <TableBody>
            {loading ? (
              <OrdersTableSkeleton visibleColumns={visibleColumns} />
            ) : orders.length === 0 ? (
              <OrdersTableEmpty />
            ) : (
              <OrdersTableBody orders={orders} pagination={pagination} visibleColumns={visibleColumns} />
            )}
          </TableBody>
        </Table>
      </div>

      {pagination.total > 0 && (
        <OrdersPagination
          pagination={pagination}
          totalPages={totalPages}
          onPageChange={handlePageChange}
          onPageSizeChange={handlePageSizeChange}
        />
      )}
    </div>
  );
}