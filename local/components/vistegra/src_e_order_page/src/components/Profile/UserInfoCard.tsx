import {Card, CardContent, CardHeader, CardTitle} from "@/components/ui/card"
import {Avatar, AvatarFallback, AvatarImage} from "@/components/ui/avatar"
import {Button} from "@/components/ui/button"
import {Separator} from "@/components/ui/separator"
import {User, Mail, Phone, LogOut, Shield, Store, Hash} from "lucide-react"
import {ROLE_NAMES} from "@/constants/constants"

import type {User as UserType, DealerDetailed} from "@/types/user"
import {ProfileInfoRow} from "./items/ProfileInfoRow"
import {getInitials} from "@/components/Profile/getInitials.ts";

interface UserInfoCardProps {
  user: UserType;
  onLogout: () => void;
}

export function UserInfoCard({user, onLogout}: UserInfoCardProps) {

  const isDealer = user.provider === 'dealer';

  const dealerDetails = (isDealer && user.detailed)
    ? (user.detailed as DealerDetailed)
    : null;

  return (
    <Card className="lg:col-span-2">
      <CardHeader className="p-4 sm:p-6 pb-2 sm:pb-4">
        <div className="flex flex-row items-start gap-4 sm:gap-5">
          <Avatar className="h-16 w-16 sm:h-24 sm:w-24 shrink-0">
            <AvatarImage src=""/>
            <AvatarFallback className="text-xl sm:text-3xl font-bold bg-primary/10">
              {getInitials(user.name)}
            </AvatarFallback>
          </Avatar>

          <div className="space-y-1 sm:space-y-2 sm:text-left w-full sm:w-auto">
            <CardTitle className="text-lg sm:text-xl md:text-2xl break-words">
              {user.name}
            </CardTitle>

            <div className="flex flex-col sm:flex-rowsm:items-start gap-1 sm:gap-2 text-sm text-muted-foreground">
              <div className="flex gap-1.5">
                <Shield className="h-3.5 w-3.5 sm:h-4 sm:w-4"/>
                {ROLE_NAMES[user.role] || user.role}
              </div>

              {dealerDetails && (
                <>
                  <span className="text-xs sm:text-sm">
                    Дилер: <span className="font-medium">{dealerDetails.dealer_name}</span>
                  </span>
                </>
              )}
            </div>

          </div>
        </div>
      </CardHeader>

      <CardContent className="p-4 sm:p-6 pt-2">
        <div className="grid gap-3 sm:gap-4 text-sm">

          <ProfileInfoRow icon={User} label="Логин" value={user.login}/>
          <ProfileInfoRow icon={Mail} label="Email" value={user.email}/>
          <ProfileInfoRow icon={Phone} label="Телефон" value={user.phone}/>

          {dealerDetails && (
            <>
              <ProfileInfoRow icon={Store} label="Салон" value={dealerDetails.salon_name}/>
              <ProfileInfoRow icon={Hash} label="Код салона" value={dealerDetails.salon_code}/>
            </>
          )}
        </div>

        <Separator className="my-4 sm:my-6"/>

        <div className="flex justify-center sm:justify-end">
          <Button
            variant="outline"
            size="default"
            onClick={onLogout}
            className="w-full sm:w-auto border-red-200 text-red-600 hover:text-red-700 hover:bg-red-50 hover:border-red-300"
          >
            <LogOut className="mr-2 h-4 w-4"/>
            Выйти из аккаунта
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}