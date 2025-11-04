"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, Eye, EyeOff } from "lucide-react";
import axios from "axios";
import {useNavigate} from "react-router-dom";
import {useAuthStore} from "@/store/authStore.ts";

const loginSchema = z.object({
  login: z.string().min(1, "Логин обязателен"),
  password: z.string().min(1, "Пароль обязателен"),
});

type LoginFormData = z.infer<typeof loginSchema>;

// Настройка axios один раз — с перехватчиком
const api = axios.create({
  baseURL: "https://ligron.ru/local/api-e-order",
  headers: {
    "Content-Type": "application/json",
  },
});

// Добавляем токен к каждому запросу
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default function LoginPage() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [providerType, setProviderType] = useState<"dealer" | "ligron">("dealer");
  const [showPassword, setShowPassword] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const navigate = useNavigate();
  const { login } = useAuthStore();

  const onSubmit = async (data: LoginFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      const res = await api.post("/auth/login/", {
        login: data.login,
        password: data.password,
        providerType,
      });

      const resData = res.data?.data;
      console.log('resData', resData)
      login({ user: resData.user, token: resData.token });
      navigate("/orders");
    } catch (err: any) {
      const resData = err.response?.data?.data;

      setError(resData?.message || "Ошибка входа");
      reset({ password: "" });
    } finally {
      setIsLoading(false);
    }
  };
/*  const onSubmit = async (data: LoginFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await api.post("/auth/login/", {
        login: data.login,
        password: data.password,
        providerType,
      });

      const { token, user } = response.data.data;

      // Сохраняем в localStorage
      localStorage.setItem("auth_token", token);
      localStorage.setItem("auth_user", JSON.stringify(user));

      console.log("Успешный вход:", { user });

    } catch (err: any) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.error ||
        "Неверный логин или пароль";
      setError(message);
      reset({ password: "" });
    } finally {
      setIsLoading(false);
    }
  };*/

  return (
    <div className="flex min-h-screen items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="space-y-1">
          <CardTitle className="text-2xl text-center">Вход в систему</CardTitle>
          <CardDescription className="text-center">
            Выберите тип пользователя и введите данные
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs
            value={providerType}
            onValueChange={(v) => setProviderType(v as "dealer" | "ligron")}
          >
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="dealer">Дилер</TabsTrigger>
              <TabsTrigger value="ligron">Менеджер Лигрона</TabsTrigger>
            </TabsList>

            <TabsContent value={providerType} className="space-y-4 mt-6">
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="login">Логин</Label>
                  <Input
                    id="login"
                    placeholder="Введите логин"
                    {...register("login")}
                    disabled={isLoading}
                  />
                  {errors.login && (
                    <p className="text-sm text-destructive">{errors.login.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="password">Пароль</Label>
                  <div className="relative">
                    <Input
                      id="password"
                      type={showPassword ? "text" : "password"}
                      placeholder="Введите пароль"
                      {...register("password")}
                      disabled={isLoading}
                      className="pr-10"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="absolute right-0 top-0 h-full px-3"
                      onClick={() => setShowPassword((prev) => !prev)}
                      disabled={isLoading}
                    >
                      {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </Button>
                  </div>
                  {errors.password && (
                    <p className="text-sm text-destructive">{errors.password.message}</p>
                  )}
                </div>

                {error && (
                  <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}

                <Button type="submit" className="w-full" disabled={isLoading}>
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Вход...
                    </>
                  ) : (
                    "Войти"
                  )}
                </Button>
              </form>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
}