"use client";

import {TableHead, TableHeader, TableRow} from "@/components/ui/table";
import {OrdersTableStatusFilters} from "./OrdersTableStatusFilters.tsx";
import type {VisibleColumns} from "./types";
import {COLUMNS_CONFIG} from "./types";

interface OrdersTableHeaderProps {
  visibleColumns: VisibleColumns;
  filter: string;
  statuses: any[];
  statusesLoading: boolean;
  onStatusToggle: (statusId: number[]) => void;
}

export function OrdersTableHeader({
                                    visibleColumns,
                                    filter,
                                    statuses,
                                    statusesLoading,
                                    onStatusToggle
                                  }: OrdersTableHeaderProps) {
  return (
    <TableHeader className="bg-muted [&_th]:text-foreground">
      <TableRow>
        <TableHead className="w-12">№</TableHead>
        {COLUMNS_CONFIG.map((column) =>
            visibleColumns[column.key] && (
              <TableHead key={column.key} className={column.width}>
                {column.key === 'status' ? (
                  <OrdersTableStatusFilters
                    filter={filter}
                    statuses={statuses}
                    statusesLoading={statusesLoading}
                    onStatusToggle={onStatusToggle}
                  />
                ) : (
                  column.label
                )}
              </TableHead>
            )
        )}

        {/* Просто пустая ячейка для действий */}
        <TableHead className="w-8"></TableHead>
      </TableRow>
    </TableHeader>
  );
}