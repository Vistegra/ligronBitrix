"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { User, Mail, Phone, LogOut, Shield, Store, Hash } from "lucide-react";
import { ROLE_NAMES } from "@/config/roles.ts";
import type { User as UserType, DealerDetailed } from "@/types/user";
import { ProfileInfoRow } from "./items/ProfileInfoRow";
import { getInitials } from "./getInitials";

interface UserInfoCardProps {
  user: UserType;
  onLogout: () => void;
}

export function UserInfoCard({ user, onLogout }: UserInfoCardProps) {
  const isDealer = user.provider === 'dealer';
  const details = user.detailed as DealerDetailed | undefined;

  return (
    <Card className="lg:col-span-2">
      <CardHeader className="p-6">
        <div className="flex flex-row items-center gap-5">
          <Avatar className="h-20 w-20 shrink-0">
            <AvatarFallback className="text-2xl font-bold bg-primary/10 text-primary">
              {getInitials(user.name)}
            </AvatarFallback>
          </Avatar>

          <div className="space-y-1">
            <CardTitle className="text-xl md:text-2xl">{user.name}</CardTitle>
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Shield className="h-4 w-4 text-green-600" />
              <span>{ROLE_NAMES[user.role as keyof typeof ROLE_NAMES] || user.role}</span>
            </div>
          </div>
        </div>
      </CardHeader>

      <CardContent className="p-6 pt-0">
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-4">
            <ProfileInfoRow icon={User} label="Логин" value={user.login} />
            <ProfileInfoRow icon={Mail} label="Email" value={user.email} />
            <ProfileInfoRow icon={Phone} label="Телефон" value={user.phone} />
          </div>

          {isDealer && details && (
            <div className="space-y-4 border-l pl-6 hidden sm:block">
              <ProfileInfoRow icon={Store} label="Организация" value={details.dealer_name} />
              <ProfileInfoRow icon={Hash} label="ИНН" value={details.inn} />
              <ProfileInfoRow icon={Store} label="Ваш основной салон" value={details.salon_name} />
            </div>
          )}
        </div>

        <Separator className="my-8" />

        <div className="flex justify-end">
          <Button
            variant="outline"
            onClick={onLogout}
            className="text-red-600 border-red-100 hover:bg-red-50 hover:text-red-700"
          >
            <LogOut className="mr-2 h-4 w-4" />
            Выйти из системы
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}