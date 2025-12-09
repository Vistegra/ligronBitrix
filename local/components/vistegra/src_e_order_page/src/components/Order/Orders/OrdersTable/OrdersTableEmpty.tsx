import {SearchIcon} from "lucide-react";

interface OrdersTableEmptyProps {
  colSpan: number;
}

export function OrdersTableEmpty({ colSpan = 7 }: OrdersTableEmptyProps) {
  return (
    <tr>
      <td colSpan={colSpan} className="text-center py-8 text-muted-foreground">
        <div className="flex flex-col items-center justify-center py-12 text-center px-4">
          <div className="bg-muted/50 p-4 rounded-full mb-4">
            <SearchIcon className="h-8 w-8 text-muted-foreground/50"/>
          </div>
          <h3 className="text-lg font-medium">Ничего не найдено</h3>
          <p className="text-sm text-muted-foreground mt-1">Попробуйте изменить фильтры</p>
        </div>
      </td>
    </tr>
  );
}