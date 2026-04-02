import React from "react";
import {Search, X} from "lucide-react";
import {Input} from "@/components/ui/input";

interface SidebarSearchProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

export const SidebarSearch: React.FC<SidebarSearchProps> =
  ({
     value,
     onChange,
     placeholder = "Поиск дилера или салона ..."
   }) => {
    return (
      <div className="px-2 mb-2 group-data-[collapsible=icon]:hidden">
        <div className="relative">
          <Search className="absolute left-2 top-2.5 h-3.5 w-3.5 text-muted-foreground pointer-events-none"/>
          <Input
            placeholder={placeholder}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            className="pl-8 h-8 text-[11px] bg-sidebar-accent/50 border-none focus-visible:ring-1 focus-visible:ring-primary/50"
          />
          {value && (
            <button
              onClick={() => onChange("")}
              className="absolute right-2 top-2.5 hover:text-foreground text-muted-foreground transition-colors"
              type="button"
            >
              <X className="h-3.5 w-3.5"/>
            </button>
          )}
        </div>
      </div>
    );
  };