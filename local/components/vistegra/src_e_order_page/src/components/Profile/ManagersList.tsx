import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Users } from "lucide-react"
import type {ManagerOfDealer} from "@/types/user"
import { ManagerItem } from "./items/ManagerItem"

interface ManagersListProps {
  managers: Array<ManagerOfDealer>;
}

export function ManagersList({ managers }: ManagersListProps) {
  if (!managers || managers.length === 0) return null;

  return (
    <Card>
      <CardHeader className="pb-4">
        <CardTitle className="text-lg flex items-center gap-2">
          <Users className="h-5 w-5" />
          Ваши менеджеры
        </CardTitle>
        <CardDescription className="text-xs sm:text-sm">
          Закреплённые специалисты LIGRON
        </CardDescription>
      </CardHeader>

      <CardContent className="space-y-4">
        {managers.map((manager) => (
          <ManagerItem key={manager.code_user} manager={manager} />
        ))}
      </CardContent>
    </Card>
  )
}