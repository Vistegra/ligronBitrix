import {Avatar, AvatarFallback} from "@/components/ui/avatar"
import {Badge} from "@/components/ui/badge"
import {Mail, Phone, PalmtreeIcon, UserPlusIcon} from "lucide-react"
import {cn} from "@/lib/utils"
import type {ManagerOfDealer} from "@/types/user"
import {getInitials} from "@/components/Profile/getInitials.ts";

interface ManagerItemProps {
  manager: ManagerOfDealer;
}

export function ManagerItem({manager}: ManagerItemProps) {
  const isOnVacation = manager.is_on_vacation;
  const isSubstitute = manager.is_substitute;

  return (
    <div
      className={cn(
        "flex gap-2.5 sm:gap-3 p-2.5 sm:p-3 rounded-lg transition-all relative overflow-hidden border",
        isOnVacation
          ? "bg-muted/30 opacity-70 grayscale-[0.8]"
          : "bg-muted/50 border-transparent",
        isSubstitute
          ? "bg-blue-50/50 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900"
          : ""
      )}
    >
      <Avatar className="h-10 w-10 sm:h-12 sm:w-12 shrink-0">
        <AvatarFallback className={cn(
          "text-xs sm:text-sm font-bold",
          isSubstitute ? "bg-blue-100 text-blue-700" : "bg-primary/20"
        )}>
          {getInitials(manager.name)}
        </AvatarFallback>
      </Avatar>

      <div className="flex-1 space-y-1 min-w-0">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
          <p className={cn(
            "font-medium text-sm truncate max-w-full",
            isOnVacation && "line-through decoration-slate-400"
          )} title={manager.name}>
            {manager.name}
          </p>

          {/* Бейджи */}
          {isOnVacation && (
            <Badge variant="outline"
                   className="h-5 px-1.5 text-[10px] text-orange-600 border-orange-200 bg-orange-50/50 whitespace-nowrap">
              <PalmtreeIcon className="h-3 w-3 mr-1"/>
              В отпуске
            </Badge>
          )}

          {isSubstitute && (
            <Badge variant="secondary"
                   className="h-5 px-1.5 text-[10px] bg-blue-100 text-blue-700 hover:bg-blue-100 whitespace-nowrap">
              <UserPlusIcon className="h-3 w-3 mr-1"/>
              Заменяет
            </Badge>
          )}
        </div>

        <p className="text-xs text-muted-foreground capitalize flex items-center gap-2">
          {manager.role === "office_manager" ? "Офис-менеджер" : "Менеджер"}
        </p>

        {manager.email && (
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <Mail className="h-3.5 w-3.5 flex-shrink-0"/>
            <span className="truncate" title={manager.email}>{manager.email}</span>
          </div>
        )}
        {manager.phone && (
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <Phone className="h-3.5 w-3.5 flex-shrink-0"/>
            <span className="truncate" title={manager.phone}>{manager.phone}</span>
          </div>
        )}
      </div>
    </div>
  );
}