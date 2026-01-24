"use client";

import {useMemo} from "react";
import {Table, TableBody} from "@/components/ui/table";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {AlertCircle, Loader2Icon} from "lucide-react";

import {useOrders} from "@/hooks/order/useOrders.ts";
import {useOrderStatuses} from "@/hooks/order/useOrderStatuses.ts";
import {useAuthStore} from "@/store/authStore.ts";

import {OrdersTableBody} from "./OrdersTableBody";
import {OrdersTableSkeleton} from "./OrdersTableSkeleton";
import {OrdersTableEmpty} from "./OrdersTableEmpty";
import {OrdersPagination} from "./OrdersPagination";
import {OrdersTablePanel} from "./OrderTablePanel";
import {OrdersTableHeader} from "./OrdersTableHeader";

import {COLUMNS_VISIBILITY_PRESETS, type PageSize} from "../types.ts";
import {PAGE} from "@/api/constants.ts";
import {useTableSettings} from "@/hooks/order/useTableSettings.ts";

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

  //Хук для настроек
  const {
    visibleColumns,
    setVisibleColumns,
    pageSize,
    setPageSize
  } = useTableSettings({
    storageKey: isDraft ? "drafts" : "orders",
    initialVisibleColumns: initialVisibility,
    initialPageSize: 10,
  });


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
  } = useOrders(pageSize, isDraft);

  // Обертка для изменения размера страницы (и в URL, и в localStorage)
  const handlePageSizeChange = (size: PageSize) => {
    setPageSize(size); // сохраняем в localStorage
    setLimit(size);    // обновляем URL параметр ?limit=...
  };

  const {statuses, loading: statusesLoading} = useOrderStatuses();
  const totalColumns = 1 + Object.values(visibleColumns).filter(Boolean).length + 1;

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

  const handleOriginToggle = (ids: number[]) => {
    updateFilters({origin_type: ids});
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
        isDraft={isDraft}
      />

      <div className="rounded-md border relative min-h-[300px]">

        {isFetching && !loading && (
          <div className="absolute inset-0 z-20 flex items-center justify-center bg-background/50 backdrop-blur-[1px]">
            <Loader2Icon className="h-10 w-10 animate-spin text-primary"/>
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
            onOriginToggle={handleOriginToggle}
          />

          <TableBody>
            {loading ? (
              <OrdersTableSkeleton visibleColumns={visibleColumns}/>
            ) : orders.length === 0 ? (
              <OrdersTableEmpty colSpan={totalColumns}/>
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
          onPageSizeChange={(size) => handlePageSizeChange(size)}
        />
      )}
    </div>
  );
}