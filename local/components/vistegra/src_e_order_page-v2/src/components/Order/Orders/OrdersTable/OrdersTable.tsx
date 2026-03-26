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
  isDraft: boolean;
}

export default function OrdersTable({isDraft = false}: OrdersTableProps) {
  const {user} = useAuthStore();

  const isManager = user?.provider === "ligron";
  const basePage = isDraft ? PAGE.DRAFTS : PAGE.ORDERS;

  // Определение начальной видимости колонок на основе пресетов
  const initialVisibility = useMemo(() => {
    if (isDraft) return COLUMNS_VISIBILITY_PRESETS.draft;
    if (isManager) return COLUMNS_VISIBILITY_PRESETS.manager;
    return COLUMNS_VISIBILITY_PRESETS.default;
  }, [isDraft, isManager]);

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
    loading,
    isFetching,
    error,
    pagination,
    activeFilters,
    updateFilters,
    setPage,
    setLimit,
    sortConfig,
    toggleSort,
  } = useOrders(pageSize, isDraft);


  const handlePageSizeChange = (size: PageSize) => {
    setPageSize(size);
    setLimit(size);
  };

  const {statuses, loading: statusesLoading} = useOrderStatuses();

  // Динамический расчет кол-ва колонок для Skeleton/Empty
  const totalColumns = useMemo(() => {
    return 1 + Object.values(visibleColumns).filter(Boolean).length + 1;
  }, [visibleColumns]);

  const handleStatusToggle = (statusIds: number[]) => updateFilters({status_id: statusIds});
  const handleDealerToggle = (inns: string[]) => updateFilters({inn_dealer: inns});
  const handleSalonToggle = (codes: string[]) => updateFilters({salon_code: codes});
  const handleOriginToggle = (ids: number[]) => updateFilters({origin_type: ids});

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

      <div className="rounded-md border relative min-h-[400px] w-full overflow-hidden bg-card">
        {/* Прелоадер при фетче */}
        {isFetching && !loading && (
          <div
            className="absolute inset-0 z-20 flex items-center justify-center bg-background/40 backdrop-blur-[1px] transition-all">
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
          totalPages={Math.ceil(pagination.total / pagination.limit)}
          onPageChange={setPage}
          onPageSizeChange={handlePageSizeChange}
        />
      )}

    </div>
  );
}