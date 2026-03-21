"use client";

import {Store, Users} from "lucide-react";
import type {DealerNode} from "@/types/user";

interface DealerItemProps {
  dealer: DealerNode;
}

export function DealerItem({dealer}: DealerItemProps) {
  return (
    <div className="flex gap-3 group p-3 rounded-lg border border-transparent hover:bg-muted/50 transition-all">
      <div
        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary transition-colors">
        <Store className="h-5 w-5"/>
      </div>

      <div className="flex-1 space-y-1 overflow-hidden min-w-0">
        <p className="font-medium text-sm truncate" title={dealer.name}>
          {dealer.name}
        </p>

        <div className="flex flex-col gap-0.5 text-xs text-muted-foreground">
          <span className="font-mono">ИНН: {dealer.inn}</span>
          <div className="flex items-center gap-1.5">
            <Users className="h-3 w-3"/>
            <span>Салонов: {dealer.salons.length}</span>
          </div>
        </div>
      </div>
    </div>
  );
}