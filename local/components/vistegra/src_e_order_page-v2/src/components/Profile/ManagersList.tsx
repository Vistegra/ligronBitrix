import {Card, CardContent, CardHeader, CardTitle, CardDescription} from "@/components/ui/card"
import {Users} from "lucide-react"
import type {ManagerOfDealer} from "@/types/user"
import {ManagerItem} from "./items/ManagerItem"

interface ManagersListProps {
  managers: Array<ManagerOfDealer>;
}

export function ManagersList({managers}: ManagersListProps) {
  if (!managers || managers.length === 0) return null;

  return (
    <Card>
      <CardHeader className="p-4 sm:p-6 pb-2 sm:pb-4">
        <CardTitle className="text-base sm:text-lg flex items-center gap-2">
          <Users className="h-4 w-4 sm:h-5 sm:w-5"/>
          Ваши менеджеры
        </CardTitle>
        <CardDescription className="text-xs sm:text-sm">
          Закреплённые специалисты LIGRON
        </CardDescription>
      </CardHeader>

      <CardContent className="p-4 sm:p-6 pt-0 space-y-3 sm:space-y-4">
        {managers.map((manager) => (
          <ManagerItem key={manager.code_user} manager={manager}/>
        ))}
      </CardContent>
    </Card>
  )
}