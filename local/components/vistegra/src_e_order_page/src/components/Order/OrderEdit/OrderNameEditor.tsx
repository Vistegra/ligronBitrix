"use client";

import {useState, useEffect} from "react";
import {Input} from "@/components/ui/input";
import {Button} from "@/components/ui/button";
import {CardTitle} from "@/components/ui/card";
import {CheckIcon, Loader2, PencilIcon, XIcon} from "lucide-react";

interface OrderNameEditorProps {
  name: string;
  isDraft: boolean;
  isSaving: boolean;
  onSave: (newName: string) => Promise<any>;
}

export function OrderNameEditor({name, isDraft, isSaving, onSave}: OrderNameEditorProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [value, setValue] = useState(name);

  // Синхронизация стейта, если имя изменилось извне
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
          className="h-10 text-base md:text-lg font-medium"
          placeholder="Название заказа"
          onKeyDown={handleKeyDown}
          autoFocus
        />
        {/* Кнопки сохранения/отмены */}
        <Button size="sm" onClick={handleSave} disabled={isSaving} className="shrink-0">
          {isSaving ? <Loader2 className="h-4 w-4 animate-spin"/> : <CheckIcon className="h-4 w-4"/>}
        </Button>
        <Button size="sm" variant="ghost" onClick={handleCancel} disabled={isSaving} className="shrink-0">
          <XIcon className="h-4 w-4"/>
        </Button>
      </div>
    );
  }

  // Режим просмотра
  return (
    <div className="flex items-center justify-center md:justify-start gap-2 group w-full overflow-hidden">
      <CardTitle
        className="text-base md:text-xl truncate text-center md:text-left leading-normal py-1"
        title={name}
      >
        {name}
      </CardTitle>

      {isDraft && (
        <Button
          variant="ghost"
          size="icon"
          className="h-8 w-8 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
          onClick={() => setIsEditing(true)}
          title="Редактировать название"
        >
          <PencilIcon className="h-4 w-4"/>
        </Button>
      )}
    </div>
  );
}
