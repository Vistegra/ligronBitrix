"use client";

import {TableHead, TableHeader, TableRow} from "@/components/ui/table";
import {OrdersTableStatusFilters} from "./OrdersTableStatusFilters";
import {OrdersTableDealerFilters} from "./OrdersTableDealerFilters";
import {OrdersTableSalonFilters} from "./OrdersTableSalonFilters.tsx";
import {COLUMN_DEFINITIONS, type ColumnKey, type PartVisibleColumns} from "../types.ts";

import type {OrderStatus} from "@/api/orderApi.ts";
import {OrdersTableOriginFilters} from "@/components/Order/Orders/OrdersTable/OrdersTableOriginFilters.tsx";
import {cn} from "@/lib/utils.ts";
import {ArrowDownNarrowWide, ArrowUpDown, ArrowUpNarrowWide} from "lucide-react";

interface OrdersTableHeaderProps {
  visibleColumns: PartVisibleColumns;
  activeFilters: {
    status_id: number[];
    inn_dealer: string[];
    salon_code: string[];
    /* dealer_prefix: string | null; ToDo удалить
    dealer_user_id: number | null;*/
    origin_type: number[];
  };
  statuses: OrderStatus[];
  statusesLoading: boolean;
  onStatusToggle: (statusId: number[]) => void;
  /*onDealerSelect: (prefix: string | null) => void; ToDo удалить
  onUserSelect: (userId: number | null, dealerPrefix?: string | null) => void;*/
  onDealerToggle: (inns: string[]) => void;
  onSalonToggle: (codes: string[]) => void;
  onOriginToggle: (ids: number[]) => void;

  sortConfig: { field: string | null; direction: "asc" | "desc" | null };
  onSort: (key: string) => void;
}

export function OrdersTableHeader({
                                    visibleColumns,
                                    activeFilters,
                                    statuses,
                                    statusesLoading,
                                    onStatusToggle,
                                    /*onDealerSelect, ToDo удалить
                                    onUserSelect,*/
                                    onDealerToggle,
                                    onSalonToggle,
                                    onOriginToggle,
                                    sortConfig,
                                    onSort,
                                  }: OrdersTableHeaderProps) {
  return (
    <TableHeader className="bg-muted [&_th]:text-foreground">
      <TableRow>
        <TableHead className="w-12 text-center">№</TableHead>

        {(Object.keys(COLUMN_DEFINITIONS) as ColumnKey[]).map((key) => {
          if (!visibleColumns[key]) return null;

          const column = COLUMN_DEFINITIONS[key];
          const isSorted = sortConfig.field === key;

          return (
            <TableHead
              key={key}
              className={cn(
                "h-10 px-2",
                column.width,
                column.sortable &&
                "cursor-pointer select-none hover:bg-muted-foreground/5 transition-colors group"
              )}
              onClick={() => column.sortable && onSort(key)}
            >
              <div className="flex items-center gap-1">
                <div
                  /* onClick={(e) => column.sortable && e.stopPropagation()} ToDo удалить */
                  className="flex items-center min-w-0"
                >
                  {key === "status" ? (
                    <OrdersTableStatusFilters
                      selectedStatusIds={activeFilters.status_id}
                      statuses={statuses}
                      statusesLoading={statusesLoading}
                      onStatusToggle={onStatusToggle}
                    />
                    /*) : key === "dealer" ? ( ToDo удалить
                      <OrdersTableDealerFilters
                        selectedDealerPrefix={activeFilters.dealer_prefix}
                        onSelect={onDealerSelect}
                      />
                    ) : key === "user" ? (
                      <OrdersTableSalonFilters
                        selectedDealerPrefix={activeFilters.dealer_prefix}
                        selectedUserId={activeFilters.dealer_user_id}
                        onSelect={onUserSelect}
                      />*/
                  ) : key === "inn_dealer" ? (
                    <OrdersTableDealerFilters
                      selectedInns={activeFilters.inn_dealer}
                      onToggle={onDealerToggle}
                    />
                  ) : key === "salon_code" ? (
                    <OrdersTableSalonFilters
                      selectedInns={activeFilters.inn_dealer}
                      selectedSalons={activeFilters.salon_code}
                      onToggle={onSalonToggle}
                    />
                  ) : key === "origin" ? (
                    <OrdersTableOriginFilters
                      selectedOrigins={activeFilters.origin_type}
                      onToggle={onOriginToggle}
                    />
                  ) : (
                    <span className="font-medium truncate">{column.label}</span>
                  )}
                </div>

                {/* Индикатор сортировки */}
                {column.sortable && (
                  <span className="shrink-0">
                    {isSorted ? (
                      sortConfig.direction === "asc" ? (
                        <ArrowUpNarrowWide className="h-4 w-4 text-primary animate-in fade-in zoom-in duration-200"/>
                      ) : (
                        <ArrowDownNarrowWide className="h-4 w-4 text-primary animate-in fade-in zoom-in duration-200"/>
                      )
                    ) : (
                      <ArrowUpDown
                        className="h-3.5 w-3.5 text-muted-foreground/30 opacity-0 group-hover:opacity-100 transition-opacity"/>
                    )}
                  </span>
                )}
              </div>
            </TableHead>
          );
        })}

        <TableHead className="w-8"></TableHead>
      </TableRow>
    </TableHeader>
  );
}