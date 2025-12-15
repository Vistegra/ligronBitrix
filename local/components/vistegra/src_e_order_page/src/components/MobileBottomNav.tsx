import {Link, useLocation} from "react-router-dom";
import {User, PenLine, ListChecks, FileText} from "lucide-react";
import {cn} from "@/lib/utils";
import {PAGE} from "@/api/constants";
import {useAuthStore} from "@/store/authStore";

export function MobileBottomNav() {
  const location = useLocation();
  const {user} = useAuthStore();

  // Определяем пункты меню в зависимости от роли
  const navItems = (() => {
    //Меню Менеджера (Ligron)
    if (user?.provider === 'ligron') {
      return [
        {
          label: "Профиль",
          icon: User,
          path: PAGE.PROFILE,
        },
        {
          label: "Заказы",
          icon: ListChecks,
          path: PAGE.ORDERS,
        },

        {
          label: "Заявки",
          icon: FileText,
          path: PAGE.REQUESTS,
        },
      ];
    }

    // Меню Дилера (по умолчанию)
    return [
      {
        label: "Профиль",
        icon: User,
        path: PAGE.PROFILE,
      },
      {
        label: "Заказы",
        icon: ListChecks,
        path: PAGE.ORDERS,
      },
      {
        label: "Черновики",
        icon: PenLine,
        path: PAGE.DRAFTS,
      },
    ];
  })();

  return (
    <div
      className="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 pb-[env(safe-area-inset-bottom)]">
      <div className="flex h-16 items-center justify-around">
        {navItems.map((item) => {

          const isActive = location.pathname.startsWith(item.path);

          return (
            <Link
              key={item.path}
              to={item.path}
              className={cn(
                "flex flex-1 flex-col items-center justify-center gap-1 h-full transition-colors active:scale-95",
                isActive
                  ? "text-green-600"
                  : "text-muted-foreground hover:text-foreground"
              )}
            >
              <item.icon
                className={cn("h-6 w-6", isActive && "stroke-[2.5px]")}
              />
              <span className={cn("text-[10px] font-medium", isActive && "font-semibold")}>
                {item.label}
              </span>
            </Link>
          );
        })}
      </div>
    </div>
  );
}