import {Card, CardContent, CardHeader, CardTitle, CardDescription} from "@/components/ui/card";
import {Building2} from "lucide-react";
import type {DealerNode} from "@/types/user";
import {DealerItem} from "./items/DealerItem";

interface DealersListProps {
  dealers: DealerNode[];
}

export function DealersList({dealers}: DealersListProps) {
  return (
    <Card>
      <CardHeader className="p-4 pb-2">
        <CardTitle className="text-base flex items-center gap-2">
          <Building2 className="h-4 w-4 text-primary"/>
          Доступные организации
        </CardTitle>
        <CardDescription className="text-xs">
          Компании, заказы которых вам доступны ({dealers.length})
        </CardDescription>
      </CardHeader>

      <CardContent className="p-4 pt-0">
        <div className="flex flex-col gap-1 max-h-[400px] overflow-y-auto pr-1">
          {dealers.map((dealer) => (
            <DealerItem key={dealer.inn} dealer={dealer}/>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}