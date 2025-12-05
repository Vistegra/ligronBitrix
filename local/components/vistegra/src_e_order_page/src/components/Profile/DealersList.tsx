import {Card, CardContent, CardHeader, CardTitle, CardDescription} from "@/components/ui/card"
import {Building2} from "lucide-react"
import type {ManagedDealer} from "@/types/user"

import {DealerItem} from "./items/DealerItem"

interface DealersListProps {
  dealers: Array<ManagedDealer>;
}

export function DealersList({dealers}: DealersListProps) {
  return (
    <Card>
      <CardHeader className="pb-4">
        <CardTitle className="text-lg flex items-center gap-2">
          <Building2 className="h-5 w-5"/>
          Закрепленные дилеры
        </CardTitle>
        <CardDescription className="text-xs sm:text-sm">
          Список ваших дилеров ({dealers?.length || 0})
        </CardDescription>
      </CardHeader>

      <CardContent>
        {dealers && dealers.length > 0 ? (
          <div className="flex flex-col gap-3 max-h-[500px] overflow-y-auto pr-1">
            {dealers.map((dealer) => (
              <DealerItem key={dealer.dealer_prefix} dealer={dealer}/>
            ))}
          </div>
        ) : (
          <div className="text-center py-4 text-sm text-muted-foreground">
            Нет закрепленных дилеров
          </div>
        )}
      </CardContent>
    </Card>
  )
}