import { Link, Outlet, useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import {ROLE_NAMES} from "@/constants/constants.ts";

export default function Layout() {
  const { user, logout } = useAuthStore();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate("/login");
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <Link to="/orders" className="text-2xl font-bold text-blue-600">
            Ligron
          </Link>
          {
            user && <div className="flex items-center gap-4">
            <span className="text-sm">
              {user.name} ({ROLE_NAMES[user.role]})
            </span>
              <button onClick={handleLogout} className="btn btn-sm btn-error">
                  Выйти
              </button>
          </div>
          }

        </div>
      </header>
      <main className="max-w-7xl mx-auto p-6">
        <Outlet/>
      </main>
    </div>
  );
}