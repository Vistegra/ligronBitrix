"use client";

import {useState} from "react";
import {Table, TableBody} from "@/components/ui/table";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {AlertCircle} from "lucide-react";

import {useOrders} from "@/hooks/useOrders";
import {useOrderStatuses} from "@/hooks/useOrderStatuses";
import {useAuthStore} from "@/store/authStore";

import {OrdersTableBody} from "./OrdersTableBody";
import {OrdersTableSkeleton} from "./OrdersTableSkeleton";
import {OrdersTableEmpty} from "./OrdersTableEmpty";
import {OrdersPagination} from "./OrdersPagination";
import {OrdersTablePanel} from "./OrderTablePanel";
import {OrdersTableHeader} from "./OrdersTableHeader";

import type {VisibleColumns} from "./types";

interface OrdersTableProps {
  isDraft: boolean
}

export default function OrdersTable({ isDraft = false }:OrdersTableProps) {
  const {user} = useAuthStore();
  const isManager = user?.provider === "ligron"; //ToDo подумать над ролью

  // Инициализация колонок: менеджеру показываем дилера и пользователя, дилеру - нет
  const [visibleColumns, setVisibleColumns] = useState<VisibleColumns>({
    id: true,
    number: isDraft,
    status: isDraft, // для черновиков нет фильтра по статусам
    name: true,
    type: isDraft,
    dealer: isDraft && isManager,
    user: isDraft && isManager,
    fabrication: isDraft,
    ready_date: isDraft,
    created_at: true,
    updated_at: true,
  });

  const {
    orders,
    loading,
    error,
    pagination,
    activeFilters,
    updateFilters,
    setPage,
    setLimit,
  } = useOrders(10);

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

      <div className="rounded-md border">
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