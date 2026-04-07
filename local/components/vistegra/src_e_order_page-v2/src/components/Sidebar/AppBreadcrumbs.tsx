"use client";

import {Link} from "react-router-dom";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import {useBreadcrumbs} from "@/hooks/sidebar/useBreadcrumbs";
import React from "react";

export function AppBreadcrumbs() {
  const breadcrumbs = useBreadcrumbs();

  return (
    <Breadcrumb>
      <BreadcrumbList className="flex-nowrap">
        {breadcrumbs.map((crumb, index) => (
          <React.Fragment key={index}>
            {index > 0 && <BreadcrumbSeparator className="shrink-0"/>}
            <BreadcrumbItem className="shrink min-w-0">
              {crumb.to ? (
                <BreadcrumbLink asChild className="max-w-[100px] sm:max-w-[200px] truncate block">
                  <Link to={crumb.to} title={crumb.label}>{crumb.label}</Link>
                </BreadcrumbLink>
              ) : crumb.isGroup ? (
                <span
                  className="text-muted-foreground font-medium max-w-[100px] sm:max-w-[200px] truncate block"
                  title={crumb.label}
                >
                  {crumb.label}
                </span>
              ) : (
                <BreadcrumbPage
                  className="max-w-[100px] sm:max-w-[200px] truncate block"
                  title={crumb.label}
                >
                  {crumb.label}
                </BreadcrumbPage>
              )}
            </BreadcrumbItem>
          </React.Fragment>
        ))}
      </BreadcrumbList>
    </Breadcrumb>
  );
}