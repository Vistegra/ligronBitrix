"use client";

import { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { CardTitle } from "@/components/ui/card";
import { CheckIcon, Loader2, PencilIcon, XIcon } from "lucide-react";

interface OrderNameEditorProps {
  name: string;
  isDraft: boolean;
  isSaving: boolean;
  onSave: (newName: string) => Promise<never>;
}

export function OrderNameEditor({ name, isDraft, isSaving, onSave }: OrderNameEditorProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [value, setValue] = useState(name);

  // Синхронизируем стейт, если имя изменилось извне
  useEffect(() => {
    setValue(name);
  }, [name]);

  const handleSave = async () => {
    if (!value.trim()) return;
      await onSave(value);
      setIsEditing(false);    
  };

  const handleCancel = () => {
    setValue(name);
    setIsEditing(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter") handleSave();
    if (e.key === "Escape") handleCancel();
  };

  // Режим редактирования
  if (isEditing) {
    return (
      <div className="flex items-center gap-2 w-full max-w-lg animate-in fade-in duration-200">
        <Input
          value={value}
          onChange={(e) => setValue(e.target.value)}
          disabled={isSaving}
          className="h-9 text-lg font-medium"
          placeholder="Название заказа"
          onKeyDown={handleKeyDown}
          autoFocus
        />
        <Button
          size="sm"
          onClick={handleSave}
          disabled={isSaving}
        >
          {isSaving ? <Loader2 className="h-4 w-4 animate-spin" /> : <CheckIcon className="h-4 w-4" />}
        </Button>
        <Button
          size="sm"
          variant="ghost"
          onClick={handleCancel}
          disabled={isSaving}
        >
          <XIcon className="h-4 w-4" />
        </Button>
      </div>
    );
  }

  // Режим просмотра
  return (
    <div className="flex items-center gap-2 group">
      <CardTitle className="text-xl truncate max-w-[600px]" title={name}>
        {name}
      </CardTitle>

      {/* Показываем карандаш только если это черновик */}
      {isDraft && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity"
          onClick={() => setIsEditing(true)}
          title="Редактировать название"
        >
          <PencilIcon className="h-4 w-4" />
        </Button>
      )}
    </div>
  );
}