"use client";

import {useState} from "react";
import {useForm} from "react-hook-form";
import {zodResolver} from "@hookform/resolvers/zod";
import {z} from "zod";

import {Input} from "@/components/ui/input";
import {Button} from "@/components/ui/button";
import {Label} from "@/components/ui/label";
import {Tabs, TabsContent, TabsList, TabsTrigger} from "@/components/ui/tabs";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {Loader2, Eye, EyeOff} from "lucide-react";
import type {ProviderType} from "@/types/user";
import {useAuth} from "@/hooks/auth/useAuth.ts";
import {Link} from "react-router-dom";
import LigronLogo from "@/components/ui/custom/LigronLogo.tsx";

const loginSchema = z.object({
  login: z.string().min(1, "Логин обязателен"),
  password: z.string().min(1, "Пароль обязателен"),
});

type LoginFormData = z.infer<typeof loginSchema>;


export default function LoginPage() {
  const [providerType, setProviderType] = useState<ProviderType>('dealer');
  const [showPassword, setShowPassword] = useState(false);

  const {login, isLoading, error, clearError} = useAuth();

  const {
    register,
    handleSubmit,
    formState: {errors, isSubmitting},
    reset,
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const isFormSubmitting = isLoading || isSubmitting;

  const onSubmit = async (data: LoginFormData) => {
    clearError();
    const {success} = await login({
      ...data,
      providerType
    });
    if (!success) {
      reset({password: ""});
    }
  };

  const handleProviderChange = (provider: string) => {
    setProviderType(provider as ProviderType);
    clearError();
  };

  return (
    <div className="min-h-screen w-full bg-background flex flex-col items-center justify-center p-6 sm:bg-muted/20">
      <div
        className="w-full max-w-[400px] sm:max-w-[460px] flex flex-col items-center sm:bg-white sm:p-10 sm:rounded-xl sm:shadow-sm sm:border transition-all">
        <LigronLogo/>

        <div className="text-center space-y-2 mb-8">
          <h1 className="text-2xl font-bold tracking-tight text-foreground">
            Войдите в свой аккаунт
          </h1>
          <p className="text-sm text-muted-foreground">
            Выберите тип пользователя и введите данные
          </p>
        </div>

        <Tabs
          value={providerType}
          onValueChange={handleProviderChange}
          className="w-full"
        >
          <TabsList className="grid w-full grid-cols-2 mb-6 bg-muted/50">
            <TabsTrigger value="dealer">Дилер</TabsTrigger>
            <TabsTrigger value="ligron">Менеджер</TabsTrigger>
          </TabsList>

          <TabsContent value={providerType} className="mt-0">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">

              <div className="space-y-1.5">
                <Label htmlFor="login" className="text-sm font-medium">Логин</Label>
                <Input
                  id="login"
                  placeholder=""
                  {...register("login")}
                  disabled={isFormSubmitting}
                  className="bg-muted/40 border-transparent focus:bg-background focus:border-primary h-11"
                />
                {errors.login && (
                  <p className="text-xs text-destructive">{errors.login.message}</p>
                )}
              </div>

              <div className="space-y-1.5">
                <div className="flex items-center justify-between">
                  <Label htmlFor="password">Пароль</Label>
                  <Link
                    to="#"
                    className="text-xs text-foreground/70 hover:text-primary transition-colors hidden"
                    onClick={(e) => e.preventDefault()}
                  >
                    Забыли пароль?
                  </Link>
                </div>

                <div className="relative">
                  <Input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    {...register("password")}
                    disabled={isFormSubmitting}
                    className="pr-10 bg-muted/40 border-transparent focus:bg-background focus:border-primary h-11"
                  />
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute right-0 top-0 h-full px-3 text-muted-foreground hover:text-foreground hover:bg-transparent"
                    onClick={() => setShowPassword((prev) => !prev)}
                    disabled={isFormSubmitting}
                  >
                    {showPassword ? <EyeOff className="h-4 w-4"/> : <Eye className="h-4 w-4"/>}
                  </Button>
                </div>
                {errors.password && (
                  <p className="text-xs text-destructive">{errors.password.message}</p>
                )}
              </div>

              {error && (
                <Alert variant="destructive" className="py-2">
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}

              <Button
                type="submit"
                className="w-full h-11 text-base font-medium bg-[#229E35] hover:bg-[#1e8a30] text-white shadow-none mt-2"
                disabled={isFormSubmitting}
              >
                {isFormSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin"/>
                    Вход...
                  </>
                ) : (
                  "Войти"
                )}
              </Button>
            </form>
          </TabsContent>
        </Tabs>

        <div className="mt-12 text-center hidden">
          <p className="text-xs text-muted-foreground">
            Не получается войти?{" "}
            <a href="#" className="underline hover:text-primary transition-colors">
              Обратитесь в поддержку
            </a>
          </p>
        </div>

      </div>
    </div>
  );
}