import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Form, FormControl, FormField, FormItem, FormLabel } from "@/components/ui/form";
import { Textarea } from "@/components/ui/textarea";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";

const schema = z.object({ comment: z.string().optional() });

type Props = {
  comment: string | null;
  onUpdate: (comment?: string) => Promise<boolean>;
};

export function DescriptionTab({ comment, onUpdate }: Props) {

  const form = useForm<z.infer<typeof schema>>({
    resolver: zodResolver(schema),
    defaultValues: { comment: comment || "" },
  });

  const onSubmit = async (data: z.infer<typeof schema>) => {
    const success = await onUpdate(data.comment);
    if (success) {
      toast.success("Описание обновлено");
    } else {
      toast.error("Ошибка обновления");
    }
  };

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        <FormField
          control={form.control}
          name="comment"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Описание</FormLabel>
              <FormControl>
                <Textarea {...field} rows={6} />
              </FormControl>
            </FormItem>
          )}
        />
        <Button type="submit" disabled={form.formState.isSubmitting}>
          {form.formState.isSubmitting ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Сохранение...
            </>
          ) : (
            "Обновить"
          )}
        </Button>

      </form>
    </Form>
  );
}