"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, Eye, EyeOff } from "lucide-react";
import axios from "axios";

const loginSchema = z.object({
  login: z.string().min(1, "Логин обязателен"),
  password: z.string().min(1, "Пароль обязателен"),
});

type LoginFormData = z.infer<typeof loginSchema>;

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

  const onSubmit = async (data: LoginFormData) => {
    setIsLoading(true);
    setError(null);

    try {
      const response = await axios.post(
        "https://ligron.ru/local/api-e-order/auth/login/",
        {
          login: data.login,
          password: data.password,
          providerType,
        },
        {
          headers: {
            "Content-Type": "application/json",
          },
          withCredentials: true, // важно для отправки кук
        }
      );

      // Успешный ответ
      console.log("Успешный вход:", response.data);
      // ToDo: переход на следующую страницу
    } catch (err: any) {
      const message = err.response?.data?.message || err.message || "Ошибка авторизации";
      setError(message);
      reset({ password: "" });
    } finally {
      setIsLoading(false);
    }
  };

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
          <Tabs value={providerType} onValueChange={(v) => setProviderType(v as "dealer" | "ligron")}>
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="dealer">Дилер</TabsTrigger>
              <TabsTrigger value="ligron">Менеджер Лигрона</TabsTrigger>
            </TabsList>

            <TabsContent value={providerType} className="space-y-4 mt-6">
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                {/* Логин */}
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

                {/* Пароль с глазом */}
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
                      className="absolute right-0 top-0 h-full px-3 text-muted-foreground hover:bg-transparent"
                      onClick={() => setShowPassword((prev) => !prev)}
                      disabled={isLoading}
                    >
                      {showPassword ? (
                        <EyeOff className="h-4 w-4" />
                      ) : (
                        <Eye className="h-4 w-4" />
                      )}
                      <span className="sr-only">
                        {showPassword ? "Скрыть пароль" : "Показать пароль"}
                      </span>
                    </Button>
                  </div>
                  {errors.password && (
                    <p className="text-sm text-destructive">{errors.password.message}</p>
                  )}
                </div>

                {/* Ошибка */}
                {error && (
                  <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}

                {/* Кнопка входа */}
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