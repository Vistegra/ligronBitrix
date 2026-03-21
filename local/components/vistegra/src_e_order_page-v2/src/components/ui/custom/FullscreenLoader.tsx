import {Loader2} from "lucide-react";

interface FullscreenLoaderProps {
  title?: string;
  description?: string;
}

export default function FullscreenLoader({title = "Загрузка...", description = "Пожалуйста, подождите",
  }: FullscreenLoaderProps) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
      <div className="flex flex-col items-center gap-4">
        <Loader2 className="h-10 w-10 animate-spin text-primary"/>

        <div className="flex flex-col items-center gap-2">
          <p className="text-lg font-medium text-foreground">
            {title}
          </p>
          {description && (
            <p className="text-sm text-muted-foreground">
              {description}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
