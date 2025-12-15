"use client";

import {Table, TableBody, TableCell, TableRow} from "@/components/ui/table";
import StatusBadge from "@/components/Order/StatusBage";
import {useOrderStatuses} from "@/hooks/order/useOrderStatuses.ts";

type StatusHistoryItem = {
  id: number;
  date: string;
};

type Props = {
  history: StatusHistoryItem[];
};

export function StatusHistoryTab({history}: Props) {
  const {getStatusById, loading: statusesLoading} = useOrderStatuses();

  if (history.length === 0) {
    return (
      <p className="text-center text-muted-foreground py-6">
        Нет истории изменений статуса
      </p>
    );
  }

  return (
    <div className="space-y-3">
      <h3 className="text-base font-semibold text-foreground">
        История изменения статусов
      </h3>

      <div className="rounded-lg border bg-card">
        <Table>
          <TableBody>
            {history.map((item, index) => {
              const status = getStatusById(item.id);
              const isFirst = index === 0;
              const isLast = index === history.length - 1;

              return (
                <TableRow
                  key={item.id}
                  className={`
                    border-b last:border-b-0
                    ${isFirst ? "rounded-t-lg" : ""}
                    ${isLast ? "rounded-b-lg" : ""}
                    hover:bg-muted/50
                  `}
                >
                  <TableCell className="py-3 pl-4 pr-2">
                    {statusesLoading ? (
                      <div className="h-6 w-24 animate-pulse rounded-full bg-muted"/>
                    ) : (
                      <StatusBadge
                        name={status?.name || "Неизвестно"}
                        color={status?.color || "#ccc"}
                      />
                    )}
                  </TableCell>
                  <TableCell className="py-3 pr-4 text-right text-sm text-muted-foreground">
                    {item.date}
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </div>
    </div>
  );
}