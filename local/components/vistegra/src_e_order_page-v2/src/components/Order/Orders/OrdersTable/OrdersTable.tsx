"use client";

import {useEffect, useMemo, useRef} from "react";
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
import {useContextStore} from "@/store/contextStore.ts";

interface OrdersTableProps {
  isDraft: boolean
}

export default function OrdersTable({isDraft = false}: OrdersTableProps) {
  const {user} = useAuthStore();
  const { inn, salonCode } = useContextStore();
  const lastStoreContext = useRef({ inn, salonCode });

  const isManager = user?.provider === "ligron";

  const basePage = isDraft ? PAGE.DRAFTS : PAGE.ORDERS;

  const initialVisibility = useMemo(() => {
    let presetKey: 'default' | 'draft' | 'manager' = 'default';

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
    storageKey: `${user?.provider}_${isDraft ? "drafts" : "orders"}`,
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
    sortConfig,
    toggleSort,
  } = useOrders(pageSize, isDraft);

  useEffect(() => {
    const isSidebarAction =
      inn !== lastStoreContext.current.inn ||
      salonCode !== lastStoreContext.current.salonCode;

    if (isSidebarAction) {
      updateFilters({
        inn_dealer: inn ?[inn] : [],
        salon_code: salonCode ? [salonCode] :[]
      });
      lastStoreContext.current = { inn, salonCode };
    }
  },[inn, salonCode, updateFilters]);

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

  /*
  // Старая логика
  const handleDealerSelect = (prefix: string | null) => {
    updateFilters({dealer_prefix: prefix, dealer_user_id: null});
  };

  const handleUserSelect = (userId: number | null, dealerPrefix?: string | null) => {
    if (userId === null) {
      updateFilters({dealer_user_id: null});
    } else {
      updateFilters({
        dealer_user_id: userId,
        dealer_prefix: dealerPrefix || activeFilters.dealer_prefix
      });
    }
  };
  */

  const handleDealerToggle = (inns: string[]) => {
    updateFilters({ inn_dealer: inns });
  };

  const handleSalonToggle = (codes: string[]) => {
    updateFilters({ salon_code: codes });
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
        isDraft={isDraft}
      />

      <div className="rounded-md border relative min-h-[300px] w-full overflow-hidden">

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
            /*
            ToDo
            onDealerSelect={handleDealerSelect}
            onUserSelect={handleUserSelect}
            */
            onDealerToggle={handleDealerToggle}
            onSalonToggle={handleSalonToggle}
            onOriginToggle={handleOriginToggle}
            sortConfig={sortConfig}
            onSort={toggleSort}
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