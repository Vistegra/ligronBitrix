import { useAuthStore } from "@/store/authStore"
import { ROLE_NAMES } from "@/constants/constants.ts"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { User, Mail, Phone, LogOut, Shield, Users } from "lucide-react"
import { PAGE } from "@/api/constants.ts"
import { useNavigate } from "react-router-dom"

export default function ProfilePage() {
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()

  if (!user) return null

  const handleLogout = () => {
    logout()
    navigate(PAGE.LOGIN)
  }

  const getInitials = (name: string) =>
    name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .slice(0, 2)

  const isDealer = "dealer_prefix" in user

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
        {/* Основная карточка профиля */}
        <Card className="lg:col-span-2">
          <CardHeader className="pb-4">
            <div className="flex flex-col sm:flex-row items-start gap-5">
              <Avatar className="h-20 w-20 sm:h-24 sm:w-24 shrink-0">
                <AvatarImage src="" />
                <AvatarFallback className="text-2xl sm:text-3xl font-bold bg-primary/10">
                  {getInitials(user.name)}
                </AvatarFallback>
              </Avatar>

              <div className="space-y-2 text-center sm:text-left">
                <CardTitle className="text-xl sm:text-2xl">{user.name}</CardTitle>
                <div className="flex flex-col sm:flex-row items-center sm:items-start gap-2 text-sm text-muted-foreground">
                  <div className="flex items-center gap-1.5">
                    <Shield className="h-4 w-4" />
                    {ROLE_NAMES[user.role] || user.role}
                  </div>
                  {isDealer && user.dealer_prefix && (
                    <>
                      <span className="hidden sm:inline">·</span>
                      <span>Дилер: <span className="font-medium">{user.dealer_prefix}</span></span>
                    </>
                  )}
                </div>
              </div>
            </div>
          </CardHeader>

          <CardContent className="space-y-6">
            {/* Информация */}
            <div className="grid gap-4 text-sm">
              <div className="flex items-center gap-3">
                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-muted shrink-0">
                  <User className="h-4.5 w-4.5 text-muted-foreground" />
                </div>
                <div>
                  <p className="font-medium">Логин</p>
                  <p className="text-muted-foreground">{user.login}</p>
                </div>
              </div>

              {user.email && (
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-muted shrink-0">
                    <Mail className="h-4.5 w-4.5 text-muted-foreground" />
                  </div>
                  <div>
                    <p className="font-medium">Email</p>
                    <p className="text-muted-foreground break-all">{user.email}</p>
                  </div>
                </div>
              )}

              {user.phone && (
                <div className="flex items-center gap-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-muted shrink-0">
                    <Phone className="h-4.5 w-4.5 text-muted-foreground" />
                  </div>
                  <div>
                    <p className="font-medium">Телефон</p>
                    <p className="text-muted-foreground">{user.phone}</p>
                  </div>
                </div>
              )}
            </div>

            <Separator className="my-6" />

            {/* Кнопка выхода */}
            <div className="flex justify-center sm:justify-end">
              <Button variant="outline" size="lg" onClick={handleLogout} className="w-full sm:w-auto">
                <LogOut className="mr-2 h-4 w-4" />
                Выйти из аккаунта
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Блок с менеджерами — только для дилеров */}
        {isDealer && (
          <Card className="h-fit">
            <CardHeader className="pb-4">
              <CardTitle className="text-lg flex items-center gap-2">
                <Users className="h-5 w-5" />
                Ваши менеджеры
              </CardTitle>
              <CardDescription className="text-xs sm:text-sm">
                Закреплённые специалисты LIGRON
              </CardDescription>
            </CardHeader>

            <CardContent className="space-y-5">
              {/* Менеджер по продажам */}
              <div className="flex gap-3">
                <Avatar className="h-11 w-11 shrink-0">
                  <AvatarImage src="" />
                  <AvatarFallback className="text-xs">МП</AvatarFallback>
                </Avatar>
                <div className="space-y-1 text-sm">
                  <p className="font-medium text-base">Иванов Иван Иванович</p>
                  <p className="text-muted-foreground">Менеджер по продажам</p>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Mail className="h-3.5 w-3.5" />
                    <span className="text-xs break-all">ivanov@ligron.ru</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Phone className="h-3.5 w-3.5" />
                    <span className="text-xs">+7 (999) 123-45-67</span>
                  </div>
                </div>
              </div>

              <Separator />

              {/* Офис-менеджер */}
              <div className="flex gap-3">
                <Avatar className="h-11 w-11 shrink-0">
                  <AvatarImage src="" />
                  <AvatarFallback className="text-xs">ОП</AvatarFallback>
                </Avatar>
                <div className="space-y-1 text-sm">
                  <p className="font-medium text-base">Петрова Анна Сергеевна</p>
                  <p className="text-muted-foreground">Офис-менеджер</p>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Mail className="h-3.5 w-3.5" />
                    <span className="text-xs break-all">petrova@ligron.ru</span>
                  </div>
                  <div className="flex items-center gap-2 text-muted-foreground">
                    <Phone className="h-3.5 w-3.5" />
                    <span className="text-xs">+7 (999) 987-65-43</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  )
}