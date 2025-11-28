"use client";

import {useEffect, useMemo, useState} from "react";
import {Table, TableBody} from "@/components/ui/table";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {AlertCircle, Loader2Icon} from "lucide-react";

import {useOrders} from "@/hooks/order/useOrders.ts";
import {useOrderStatuses} from "@/hooks/order/useOrderStatuses.ts";
import {useAuthStore} from "@/store/authStore";

import {OrdersTableBody} from "./OrdersTableBody";
import {OrdersTableSkeleton} from "./OrdersTableSkeleton";
import {OrdersTableEmpty} from "./OrdersTableEmpty";
import {OrdersPagination} from "./OrdersPagination";
import {OrdersTablePanel} from "./OrderTablePanel";
import {OrdersTableHeader} from "./OrdersTableHeader";

import {COLUMNS_VISIBILITY_PRESETS, type PartVisibleColumns} from "./types";
import {PAGE} from "@/api/constants.ts";

interface OrdersTableProps {
  isDraft: boolean
}

export default function OrdersTable({isDraft = false}: OrdersTableProps) {
  const {user} = useAuthStore();
  const isManager = user?.provider === "ligron";

  const basePage = isDraft ? PAGE.DRAFTS : PAGE.ORDERS;

  const initialVisibility = useMemo(() => {
    let presetKey = 'default';

    if (isDraft) {
      presetKey = 'draft';
    } else if (isManager) {
      presetKey = 'manager';
    }

    return COLUMNS_VISIBILITY_PRESETS[presetKey];

  }, [isDraft, isManager]);


  const [visibleColumns, setVisibleColumns] = useState<PartVisibleColumns>(initialVisibility);

  useEffect(() => {
    setVisibleColumns(initialVisibility);
  }, [initialVisibility]);

  const {
    orders,
    loading, // первая загрузка
    isFetching, // фоновое обновление
    error,
    pagination,
    activeFilters,
    updateFilters,
    setPage,
    setLimit,
  } = useOrders(10, isDraft);

  const {statuses, loading: statusesLoading} = useOrderStatuses();

  const handleStatusToggle = (statusIds: number[]) => {
    updateFilters({status_id: statusIds});
  };
  const handleDealerSelect = (prefix: string | null) => {
    // При смене дилера сбрасываем пользователя
    updateFilters({dealer_prefix: prefix, dealer_user_id: null});
  };

  const handleUserSelect = (userId: number | null, dealerPrefix?: string | null) => {
    if (userId === null) {
      // Если пользователь сброшен
      updateFilters({dealer_user_id: null});
    } else {
      // Если пользователь выбран, устанавливаем и ID, и Префикс
      updateFilters({
        dealer_user_id: userId,
        // Если префикс пришел из компонента фильтра, используем его.
        // Если нет (редкий случай), оставляем текущий.
        dealer_prefix: dealerPrefix || activeFilters.dealer_prefix
      });
    }
  };


  const totalPages = Math.ceil(pagination.total / pagination.limit);

  if (error) {
    return (
      <Alert variant="destructive">
        <AlertCircle className="h-4 w-4"/>
        <AlertDescription>{error}</AlertDescription>
      </Alert>
    );
  }

  return (
    <div className="space-y-6">
      <OrdersTablePanel
        visibleColumns={visibleColumns}
        setVisibleColumns={setVisibleColumns}
        selectedUserId={activeFilters.dealer_user_id}
      />

      <div className="rounded-md border relative">

        {isFetching && !loading && (
          <div className="absolute top-2 right-2 z-10">
            <Loader2Icon className="h-4 w-4 animate-spin text-muted-foreground"/>
          </div>
        )}

        <Table>
          <OrdersTableHeader
            visibleColumns={visibleColumns}
            activeFilters={activeFilters}
            statuses={statuses}
            statusesLoading={statusesLoading}
            onStatusToggle={handleStatusToggle}
            onDealerSelect={handleDealerSelect}
            onUserSelect={handleUserSelect}
          />

          <TableBody>
            {loading ? (
              <OrdersTableSkeleton visibleColumns={visibleColumns}/>
            ) : orders.length === 0 ? (
              <OrdersTableEmpty/>
            ) : (
              <OrdersTableBody
                orders={orders}
                pagination={pagination}
                visibleColumns={visibleColumns}
                basePage={basePage}
              />
            )}
          </TableBody>
        </Table>
      </div>

      {pagination.total > 0 && (
        <OrdersPagination
          pagination={pagination}
          totalPages={totalPages}
          onPageChange={setPage}
          onPageSizeChange={(size) => setLimit(size)}
        />
      )}
    </div>
  );
}