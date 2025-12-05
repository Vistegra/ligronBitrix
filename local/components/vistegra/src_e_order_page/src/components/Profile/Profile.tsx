import { useAuthStore } from "@/store/authStore"
import { useNavigate } from "react-router-dom"
import { Separator } from "@/components/ui/separator"
import { PAGE } from "@/api/constants"
import type { ManagerDetailed, DealerDetailed } from "@/types/user"

import { UserInfoCard } from "./UserInfoCard"
import { ManagersList } from "./ManagersList"
import { DealersList } from "./DealersList"

export function Profile() {
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()

  if (!user) return null

  const handleLogout = () => {
    logout()
    navigate(PAGE.LOGIN)
  }

  // Проверки ролей
  const isDealer = user.provider === 'dealer';
  const isManager = user.provider === 'ligron';

  // Детальные данные
  const managerDetails = isManager ? (user.detailed as ManagerDetailed) : null;
  const dealerDetails = isDealer ? (user.detailed as DealerDetailed) : null;

  // Данные для правой колонки
  const managedDealers = managerDetails?.managed_dealers || [];

  return (
    <div className="space-y-6">
      {/* Заголовок */}
      <div>
        <h1 className="text-2xl sm:text-2xl font-bold tracking-tight">Мой профиль</h1>
        <p className="text-sm sm:text-base text-muted-foreground">
          Информация о вашей учётной записи
        </p>
      </div>

      <Separator className="my-6" />

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Основная карточка профиля (слева) */}
        <UserInfoCard
          user={user}
          onLogout={handleLogout}
        />

        {/* Правая колонка с информацией */}
        <div className="space-y-6">

          {isDealer && dealerDetails?.managers && (
            <ManagersList managers={dealerDetails.managers} />
          )}

          {isManager && (
            <DealersList dealers={managedDealers} />
          )}
        </div>

      </div>
    </div>
  )
}