"use client";

import {useAuthStore} from "@/store/authStore";
import {useNavigate} from "react-router-dom";
import {Separator} from "@/components/ui/separator";
import {PAGE} from "@/api/constants";
import type {DealerDetailed} from "@/types/user";

import {UserInfoCard} from "./UserInfoCard";
import {ManagersList} from "./ManagersList";
import {DealersList} from "./DealersList";

export function Profile() {
  const {user, logout} = useAuthStore();
  const navigate = useNavigate();

  if (!user) return null;

  const handleLogout = () => {
    logout();
    navigate(PAGE.LOGIN);
  };

  const isDealer = user.provider === 'dealer';
  const isLigron = user.provider === 'ligron';

  // дерево hierarchy есть у обоих провайдеров
  const hierarchy = user.detailed?.hierarchy || [];

  // Менеджеры Лигрон, закрепленные за Дилером (только для дилеров)
  const attachedManagers = isDealer ? (user.detailed as DealerDetailed).managers : [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Мой профиль</h1>
        <p className="text-sm text-muted-foreground">
          Управление учетной записью и информация о привязках V2
        </p>
      </div>

      <Separator className="my-6"/>

      <div className="grid gap-6 lg:grid-cols-3">
        <UserInfoCard user={user} onLogout={handleLogout}/>

        <div className="space-y-6">
          {/* Если это Дилер - показываем его персональных менеджеров Лигрон */}
          {isDealer && attachedManagers.length > 0 && (
            <ManagersList managers={attachedManagers}/>
          )}

          {/* Если это Менеджер Лигрон - показываем дерево дилеров */}
          {isLigron && hierarchy.length > 0 && (
            <DealersList dealers={hierarchy}/>
          )}
        </div>
      </div>
    </div>
  );
}