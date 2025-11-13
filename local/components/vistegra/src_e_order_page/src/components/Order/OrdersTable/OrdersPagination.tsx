"use client";

import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/components/ui/select";
import {
  Pagination, PaginationContent, PaginationItem,
  PaginationLink, PaginationNext, PaginationPrevious,
} from "@/components/ui/pagination";
import type{Pagination as PaginationType, PageSize} from "./types";
import {PAGE_SIZES} from "./types";
interface OrdersPaginationProps {
  pagination: PaginationType;
  totalPages: number;
  onPageChange: (offset: number) => void;
  onPageSizeChange: (size: PageSize) => void;
}

export function OrdersPagination({
                                   pagination,
                                   totalPages,
                                   onPageChange,
                                   onPageSizeChange,
                                 }: OrdersPaginationProps) {
  return (
    <div className="flex items-center justify-between">
      <div className="text-sm text-muted-foreground">
        Показано {pagination.offset + 1}–{Math.min(pagination.offset + pagination.limit, pagination.total)} из {pagination.total}
      </div>

      <div className="flex items-center gap-2 font-medium">
        <span className="text-sm text-foreground whitespace-nowrap">Строк на странице:</span>
        <Select value={pagination.limit.toString()} onValueChange={(v) => onPageSizeChange(parseInt(v) as PageSize)}>
          <SelectTrigger className="w-20">
            <SelectValue/>
          </SelectTrigger>
          <SelectContent>
            {PAGE_SIZES.map(size => (
              <SelectItem key={size} value={size.toString()}>{size}</SelectItem>
            ))}
          </SelectContent>
        </Select>

        {totalPages > 1 && (
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious
                  onClick={() => onPageChange(Math.max(0, pagination.offset - pagination.limit))}
                  className={pagination.offset === 0 ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>

              {(() => {
                const currentPage = Math.floor(pagination.offset / pagination.limit) + 1;
                const pages: number[] = [];

                for (let i = -1; i <= 1; i++) {
                  const page = currentPage + i;
                  if (page >= 1 && page <= totalPages) pages.push(page);
                }

                while (pages.length < 3 && pages.length < totalPages) {
                  const first = pages[0] || 1;
                  const last = pages[pages.length - 1] || 1;
                  if (first > 1) pages.unshift(first - 1);
                  else if (last < totalPages) pages.push(last + 1);
                }

                return pages.map(page => (
                  <PaginationItem key={page}>
                    <PaginationLink
                      onClick={() => onPageChange((page - 1) * pagination.limit)}
                      isActive={(page - 1) * pagination.limit === pagination.offset}
                      className="cursor-pointer"
                    >
                      {page}
                    </PaginationLink>
                  </PaginationItem>
                ));
              })()}

              <PaginationItem>
                <PaginationNext
                  onClick={() => onPageChange(pagination.offset + pagination.limit)}
                  className={pagination.offset + pagination.limit >= pagination.total ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        )}
      </div>
    </div>
  );
}