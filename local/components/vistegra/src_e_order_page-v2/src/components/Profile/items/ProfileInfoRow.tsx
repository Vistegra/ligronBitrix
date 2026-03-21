import {type LucideIcon} from "lucide-react"

interface ProfileInfoRowProps {
  icon: LucideIcon;
  label: string;
  value: string | null | undefined;
  className?: string;
}

export function ProfileInfoRow({icon: Icon, label, value, className}: ProfileInfoRowProps) {
  if (!value) return null;

  return (
    <div className={`flex items-center gap-3 ${className}`}>
      <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-muted shrink-0">
        <Icon className="h-4.5 w-4.5 text-muted-foreground"/>
      </div>
      <div className="min-w-0">
        <p className="font-medium text-foreground">{label}</p>
        <p className="text-muted-foreground truncate" title={value}>
          {value}
        </p>
      </div>
    </div>
  );
}