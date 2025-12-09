"use client";

import {TableHead, TableHeader, TableRow} from "@/components/ui/table";
import {OrdersTableStatusFilters} from "./OrdersTableStatusFilters";
import {OrdersTableDealerFilters} from "./OrdersTableDealerFilters";
import {OrdersTableUserFilters} from "./OrdersTableUserFilters";
import {COLUMN_DEFINITIONS, type ColumnKey, type PartVisibleColumns} from "../types.ts";

import type {OrderStatus} from "@/api/orderApi.ts";

interface OrdersTableHeaderProps {
  visibleColumns: PartVisibleColumns;
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

        {Object.keys(COLUMN_DEFINITIONS).map((key) => {
          if (!visibleColumns[key as ColumnKey]) return null;

          const column = COLUMN_DEFINITIONS[key as ColumnKey];

          return (
            <TableHead key={key} className={column.width}>
              {key === "status" ? (
                <OrdersTableStatusFilters
                  selectedStatusIds={activeFilters.status_id}
                  statuses={statuses}
                  statusesLoading={statusesLoading}
                  onStatusToggle={onStatusToggle}
                />
              ) : key === "dealer" ? (
                <OrdersTableDealerFilters
                  selectedDealerPrefix={activeFilters.dealer_prefix}
                  onSelect={onDealerSelect}
                />
              ) : key === "user" ? (
                <OrdersTableUserFilters
                  selectedDealerPrefix={activeFilters.dealer_prefix}
                  selectedUserId={activeFilters.dealer_user_id}
                  onSelect={onUserSelect}
                />
              ) : (
                column.label
              )}
            </TableHead>
          );
        })}

        <TableHead className="w-8"></TableHead>
      </TableRow>
    </TableHeader>
  );
}