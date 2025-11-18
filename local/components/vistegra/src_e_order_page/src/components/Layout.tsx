import {Link, Outlet, useNavigate} from "react-router-dom";
import {useAuthStore} from "@/store/authStore";
import {ROLE_NAMES} from "@/constants/constants.ts";
import {PAGE} from "@/api/constants.ts";
import {Button} from "@/components/ui/button.tsx";
import {UserIcon, LogOutIcon} from "lucide-react";

export default function Layout() {
  const {user, logout} = useAuthStore();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate(PAGE.LOGIN);
  };

  return (
    <div className="min-h-screen ">
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex flex-row items-center justify-between gap-4">
            <div className="rounded-lg overflow-hidden">
              <Link to="/" className="">
                <svg xmlns="http://www.w3.org/2000/svg" width="49" height="51" viewBox="0 0 49 51" fill="none">
                  <path d="M48.2023 0H0V50.8276H48.2023V0Z" fill="#229E35"/>
                  <path
                    d="M31.4627 28.1771V34.6543H16V31.8412L18.0773 31.402V17.9399L16 17.5008V14.6738H25.4372V17.5008L22.906 17.9399V30.9628H27.6658L27.7759 28.1771H31.4627Z"
                    fill="white"/>
                </svg>
              </Link>
            </div>

            <div className="flex flex-col">
              <h1 className="text-lg font-bold">LIGRON</h1>
              <p className="text-md text-muted-foreground">Электронный заказ</p>
            </div>
          </div>

          {user && (
            <div className="flex items-center gap-3">
              {/* Иконка пользователя */}
              <div className="flex items-center justify-center h-9 w-9 rounded-full bg-muted">
                <UserIcon className="h-4 w-4 text-muted-foreground" />
              </div>

              {/* Имя и должность (вертикально) */}
              <div className="flex flex-col leading-tight">
                <span className="text-sm font-medium text-foreground">{user.name}</span>
                <span className="text-xs text-muted-foreground">{ROLE_NAMES[user.role]}</span>
              </div>

              {/* Кнопка выхода */}
              <Button
                variant="ghost"
                size="sm"
                onClick={handleLogout}
                className="ml-auto flex items-center gap-1 text-muted-foreground hover:text-foreground"
              >
                <LogOutIcon className="h-4 w-4" />
                <span>Выйти</span>
              </Button>
            </div>
          )}
        </div>
      </header>
      <main className="max-w-7xl mx-auto p-6">
        <Outlet/>
      </main>
    </div>
  );
}