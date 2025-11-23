"use client";

import {TableHead, TableHeader, TableRow} from "@/components/ui/table";
import {OrdersTableStatusFilters} from "./OrdersTableStatusFilters.tsx";
import {OrdersTableDealerFilters} from "./OrdersTableDealerFilters.tsx";
import {OrdersTableUserFilters} from "./OrdersTableUserFilters.tsx";
import type {VisibleColumns} from "./types";
import {COLUMNS_CONFIG} from "./types";
import type {OrderStatus} from "@/api/orderApi.ts";

interface OrdersTableHeaderProps {
  visibleColumns: VisibleColumns;
  activeFilters: {
    status_id: number[];
    dealer_prefix: string | null;
    dealer_user_id: number | null;
  };
  statuses: OrderStatus[];
  statusesLoading: boolean;
  onStatusToggle: (statusId: number[]) => void;
  onDealerSelect: (prefix: string | null) => void;

  onUserSelect: (userId: number | null, dealerPrefix?: string | null) => void;
}

export function OrdersTableHeader({
                                    visibleColumns,
                                    activeFilters,
                                    statuses,
                                    statusesLoading,
                                    onStatusToggle,
                                    onDealerSelect,
                                    onUserSelect,
                                  }: OrdersTableHeaderProps) {
  return (
    <TableHeader className="bg-muted [&_th]:text-foreground">
      <TableRow>
        <TableHead className="w-12">№</TableHead>

        {COLUMNS_CONFIG.map(
          (column) =>
            visibleColumns[column.key] && (
              <TableHead key={column.key} className={column.width}>
                {column.key === "status" ? (
                  <OrdersTableStatusFilters
                    selectedStatusIds={activeFilters.status_id}
                    statuses={statuses}
                    statusesLoading={statusesLoading}
                    onStatusToggle={onStatusToggle}
                  />
                ) : column.key === "dealer" ? (
                  <OrdersTableDealerFilters
                    selectedDealerPrefix={activeFilters.dealer_prefix}
                    onSelect={onDealerSelect}
                  />
                ) : column.key === "user" ? (
                  <OrdersTableUserFilters
                    selectedDealerPrefix={activeFilters.dealer_prefix}
                    selectedUserId={activeFilters.dealer_user_id}
                    onSelect={onUserSelect}
                  />
                ) : (
                  column.label
                )}
              </TableHead>
            )
        )}

        <TableHead className="w-8"></TableHead>
      </TableRow>
    </TableHeader>
  );
}