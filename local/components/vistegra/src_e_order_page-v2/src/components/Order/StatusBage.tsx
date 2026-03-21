import { Badge } from "@/components/ui/badge";
import type { FC } from "react";

interface StatusBadgeProps {
  color?: string | null;
  name?: string | null;
}

const StatusBadge: FC<StatusBadgeProps> = ({ color, name }) => {
  return (
    <Badge variant="secondary" className="gap-2">
      <div
        className="h-2 w-2 rounded-full"
        style={{ backgroundColor: color || "#ccc" }}
      />
      {name || "—"}
    </Badge>
  );
};

export default StatusBadge;