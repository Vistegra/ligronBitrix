import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { User, Mail, Phone, LogOut, Shield, Store, Hash } from "lucide-react"
import { ROLE_NAMES } from "@/constants/constants"

import type { User as UserType, DealerDetailed } from "@/types/user"
import { ProfileInfoRow } from "./items/ProfileInfoRow"
import {getInitials} from "@/components/Profile/getInitials.ts";

interface UserInfoCardProps {
  user: UserType;
  onLogout: () => void;
}

export function UserInfoCard({ user, onLogout }: UserInfoCardProps) {

  const isDealer = user.provider === 'dealer';

  const dealerDetails = (isDealer && user.detailed)
    ? (user.detailed as DealerDetailed)
    : null;

  return (
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

              {dealerDetails && (
                <>
                  <span className="hidden sm:inline">·</span>
                  <span>
                    Дилер: <span className="font-medium">{dealerDetails.dealer_name}</span>
                  </span>
                </>
              )}
            </div>

          </div>
        </div>
      </CardHeader>

      <CardContent className="space-y-6">
        <div className="grid gap-4 text-sm">

          <ProfileInfoRow icon={User} label="Логин" value={user.login} />
          <ProfileInfoRow icon={Mail} label="Email" value={user.email} />
          <ProfileInfoRow icon={Phone} label="Телефон" value={user.phone} />

          {dealerDetails && (
            <>
              <ProfileInfoRow icon={Store} label="Салон" value={dealerDetails.salon_name} />
              <ProfileInfoRow icon={Hash} label="Код салона" value={dealerDetails.salon_code} />
            </>
          )}
        </div>

        <Separator className="my-6" />

        <div className="flex justify-center sm:justify-end">
          <Button variant="outline" size="lg" onClick={onLogout} className="w-full sm:w-auto">
            <LogOut className="mr-2 h-4 w-4" />
            Выйти из аккаунта
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}