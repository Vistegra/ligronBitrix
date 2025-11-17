"use client";

import { Link, useLocation } from "react-router-dom";
import {
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { Home } from "lucide-react";

export function Breadcrumbs() {
  const location = useLocation();
  const pathnames = location.pathname.split("/").filter(Boolean);

  if (pathnames.length === 0) return null;

  const getLabel = (segment: string, isLast: boolean) => {
    if (!segment) return "Главная";
    if (segment === "orders") return "Заказы";
    if (segment === "profile") return "Мой профиль";
    if (segment === "drafts") return "Черновики";
    if (isLast && /^\d+$/.test(segment)) return `Заказ №${segment}`;
    return segment.charAt(0).toUpperCase() + segment.slice(1);
  };

  return (
    <BreadcrumbList>
      <BreadcrumbItem>
        <BreadcrumbLink asChild>
          <Link to="/" className="flex items-center gap-1.5">
            <Home className="h-4 w-4" />
            <span className="hidden sm:inline">Главная</span>
          </Link>
        </BreadcrumbLink>
      </BreadcrumbItem>

      {pathnames.map((segment, i) => {
        const href = "/" + pathnames.slice(0, i + 1).join("/");
        const isLast = i === pathnames.length - 1;
        const label = getLabel(segment, isLast);

        return (
          <div key={href} className="flex items-center">
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              {isLast ? (
                <BreadcrumbPage>{label}</BreadcrumbPage>
              ) : (
                <BreadcrumbLink asChild>
                  <Link to={href}>{label}</Link>
                </BreadcrumbLink>
              )}
            </BreadcrumbItem>
          </div>
        );
      })}
    </BreadcrumbList>
  );
}