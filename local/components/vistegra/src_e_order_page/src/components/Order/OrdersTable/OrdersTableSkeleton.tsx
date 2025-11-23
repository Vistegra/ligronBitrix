"use client";

import {Skeleton} from "@/components/ui/skeleton";
import {TableRow, TableCell} from "@/components/ui/table";
import type {VisibleColumns} from "./types";
import {COLUMNS_CONFIG} from "./types";

interface OrdersTableSkeletonProps {
  visibleColumns: VisibleColumns;
}

export function OrdersTableSkeleton({visibleColumns}: OrdersTableSkeletonProps) {

  return (
    <>
      {Array.from({length: 10}).map((_, i) => (
        <TableRow key={i} className="h-10">

          <TableCell className="py-1">
            <Skeleton className="h-4 w-8"/>
          </TableCell>

          {COLUMNS_CONFIG.map((column) =>
              visibleColumns[column.key] && (
                <TableCell key={column.key} className="py-1">
                  <Skeleton className={`h-4 ${column.width}`}/>
                </TableCell>
              )
          )}

          <TableCell className="py-1">
            <Skeleton className="h-8 w-8 rounded-md"/>
          </TableCell>
        </TableRow>
      ))}
    </>
  );
}