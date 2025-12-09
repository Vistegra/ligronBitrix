export function OrdersTableEmpty() {
  return (
    <tr>
      <td colSpan={7} className="text-center py-8 text-muted-foreground">
        Заказы не найдены
      </td>
    </tr>
  );
}