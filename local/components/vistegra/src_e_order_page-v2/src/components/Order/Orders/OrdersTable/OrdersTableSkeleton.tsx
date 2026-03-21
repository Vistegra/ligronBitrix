"use client";

import {Skeleton} from "@/components/ui/skeleton";
import {TableRow, TableCell} from "@/components/ui/table";
import {COLUMN_DEFINITIONS, type ColumnKey, type PartVisibleColumns} from "../types.ts";

interface OrdersTableSkeletonProps {
  visibleColumns: PartVisibleColumns;
}

export function OrdersTableSkeleton({visibleColumns}: OrdersTableSkeletonProps) {

  return (
    <>
      {Array.from({length: 10}).map((_, i) => (
        <TableRow key={i} className="h-10">
          <TableCell className="py-1">
            <Skeleton className="h-4 w-8"/>
          </TableCell>

          {Object.keys(COLUMN_DEFINITIONS).map((key) => {
            if (!visibleColumns[key as ColumnKey]) return null;

            const column = COLUMN_DEFINITIONS[key as ColumnKey];
            return (
              <TableCell key={key} className="py-1">
                <Skeleton className={`h-4 ${column.width}`}/>
              </TableCell>
            );
          })}

          <TableCell className="py-1">
            <Skeleton className="h-8 w-8 rounded-md"/>
          </TableCell>
        </TableRow>
      ))}
    </>
  );
}