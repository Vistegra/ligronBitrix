import { cn } from "@/lib/utils";

interface SidebarResizeHandleProps {
  onMouseDown: (e: React.MouseEvent) => void;
  isResizing: boolean;
}

export function SidebarResizeHandle({ onMouseDown, isResizing }: SidebarResizeHandleProps) {
  return (
    <div
      onMouseDown={onMouseDown}
      className={cn(
        "absolute right-0 top-0 h-full w-1 cursor-col-resize z-50 transition-colors",
        "hover:bg-primary/40",
        isResizing ? "bg-primary w-1" : "bg-transparent"
      )}
    />
  );
}