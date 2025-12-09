import {Badge} from "@/components/ui/badge"
import {Store, Users, UserPlusIcon} from "lucide-react"
import {cn} from "@/lib/utils"
import type {ManagedDealer} from "@/types/user"

interface DealerItemProps {
  dealer: ManagedDealer;
}

export function DealerItem({dealer}: DealerItemProps) {
  const isSubstituted = dealer.is_substituted;

  return (
    <div
      className={cn(
        "flex gap-3 group p-2 rounded-lg border transition-all",
        isSubstituted
          ? "bg-blue-50/50 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900"
          : "border-transparent hover:bg-muted/50"
      )}
    >
      <div className={cn(
        "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition-colors",
        isSubstituted
          ? "bg-blue-100 text-blue-700"
          : "bg-muted text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary"
      )}>
        <Store className="h-5 w-5"/>
      </div>

      <div className="flex-1 space-y-1 overflow-hidden min-w-0">
        <div className="flex items-center justify-between gap-2">
          <p className="font-medium text-sm truncate" title={dealer.name}>
            {dealer.name}
          </p>
          {isSubstituted && (
            <Badge variant="secondary"
                   className="h-5 px-1.5 text-[10px] bg-blue-100 text-blue-700 hover:bg-blue-100 shrink-0">
              <UserPlusIcon className="h-3 w-3 mr-1"/>
              Замена
            </Badge>
          )}
        </div>

        <div className="flex flex-col gap-0.5 text-xs text-muted-foreground">
          <span className="truncate">ИНН: {dealer.inn}</span>
          <div className="flex items-center gap-2">
            <Users className="h-3 w-3"/>
            <span>Пользователей: {dealer.users.length}</span>
          </div>
        </div>
      </div>
    </div>
  );
}