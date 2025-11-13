"use client";

import {useState} from "react";
import {format, fromUnixTime} from "date-fns";
import {ru} from "date-fns/locale";
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from "@/components/ui/table";
import {Badge} from "@/components/ui/badge";
import {Button} from "@/components/ui/button";
import {Skeleton} from "@/components/ui/skeleton";
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuCheckboxItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from "@/components/ui/select";
import {
    Pagination, PaginationContent, PaginationItem,
    PaginationLink, PaginationNext, PaginationPrevious,
} from "@/components/ui/pagination";
import {Alert, AlertDescription} from "@/components/ui/alert";
import {AlertCircle, ChevronDown, ChevronsUpDownIcon, Columns2Icon} from "lucide-react";

import {useOrders} from "@/hooks/useOrders";
import {useOrderStatuses} from "@/hooks/useOrderStatuses";

const PAGE_SIZES = [10, 20, 30, 40, 50] as const;
type PageSize = (typeof PAGE_SIZES)[number];

const getOrderType = (order: any) => {
    if (order.parent_id !== null) return "individual";
    if (order.children_count > 0) return "complex";
    return "standard";
};

const getOrderTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        individual: "Индивидуальный",
        standard: "Стандартный",
        complex: "Комплексный",
    };
    return labels[type] || type;
};

export default function OrdersTable() {
    const [visibleColumns, setVisibleColumns] = useState({
        status: true, name: true, type: true, fabrication: true, ready_date: true, created_at: true,
    });

    const {
        orders, loading, error, pagination, filter,
        fetchOrders, setLimit, setFilter,
    } = useOrders({limit: 10});

    const {statuses, loading: statusesLoading} = useOrderStatuses();

    // Множественный выбор статусов
    const selectedStatusIds = filter
        .split(";")
        .find(part => part.startsWith("status_id="))
        ?.replace("status_id=", "")
        .split(",")
        .filter(Boolean) || [];

    const handleStatusToggle = (statusId: string) => {
        const newIds = selectedStatusIds.includes(statusId)
            ? selectedStatusIds.filter(id => id !== statusId)
            : [...selectedStatusIds, statusId];

        const newFilter = newIds.length
            ? `status_id=${newIds.join(",")}`
            : "";

        setFilter(newFilter);
    };

    const handlePageChange = (newOffset: number) => {
        fetchOrders(newOffset, filter);
    };

    const handlePageSizeChange = (value: string) => {
        const newSize = parseInt(value) as PageSize;
        setLimit(newSize);
    };

    if (error) {
        return (
            <Alert variant="destructive">
                <AlertCircle className="h-4 w-4"/>
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        );
    }

    const totalPages = Math.ceil(pagination.total / pagination.limit);

    return (
        <div className="space-y-6">
            {/* Заголовок + колонки */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 className="text-2xl font-bold">Заказы</h2>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" className="gap-1">
                            <Columns2Icon className="h-4 w-4"/>
                            Все колонки <ChevronDown className="h-4 w-4"/>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        {Object.entries({
                            status: "Статус", name: "Наименование", type: "Тип заказа",
                            fabrication: "Изготовление", ready_date: "Готовность", created_at: "Дата создания",
                        }).map(([key, label]) => (
                            <DropdownMenuCheckboxItem
                                key={key}
                                checked={visibleColumns[key as keyof typeof visibleColumns]}
                                onCheckedChange={(checked) =>
                                    setVisibleColumns(prev => ({...prev, [key]: checked}))
                                }
                            >
                                {label}
                            </DropdownMenuCheckboxItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            {/* Таблица */}
            <div className="rounded-md border">
                <Table>
                    <TableHeader className="bg-muted [&_th]:text-foreground">
                        <TableRow>
                            <TableHead className="w-12">№</TableHead>

                            {/* Фильтр по статусам (множественный) */}
                            {visibleColumns.status && (
                                <TableHead>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost"
                                                    className="flex items-center gap-1 p-0 h-auto font-medium">
                                                Статус
                                                {selectedStatusIds.length > 0 && ` (${selectedStatusIds.length})`}
                                                <ChevronsUpDownIcon className="h-4 w-4"/>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="start" className="w-56">
                                            {statusesLoading ? (
                                                <div className="p-2 text-sm">Загрузка...</div>
                                            ) : (
                                                statuses.map(status => (
                                                    <DropdownMenuCheckboxItem
                                                        key={status.id}
                                                        checked={selectedStatusIds.includes(status.id.toString())}
                                                        onCheckedChange={() => handleStatusToggle(status.id.toString())}
                                                    >
                                                        <div className="flex items-center gap-2">
                                                            <div className="h-3 w-3 rounded-full"
                                                                 style={{backgroundColor: status.color}}/>
                                                            {status.name}
                                                        </div>
                                                    </DropdownMenuCheckboxItem>
                                                ))
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableHead>
                            )}

                            {visibleColumns.name && <TableHead>Наименование заказа</TableHead>}
                            {visibleColumns.type && <TableHead>Тип заказа</TableHead>}
                            {visibleColumns.fabrication && <TableHead>Изготовление</TableHead>}
                            {visibleColumns.ready_date && <TableHead>Готовность</TableHead>}
                            {visibleColumns.created_at && <TableHead>Дата создания</TableHead>}
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {loading ? (
                            Array.from({length: 5}).map((_, i) => (
                                <TableRow key={i}>
                                    <TableCell><Skeleton className="h-4 w-8"/></TableCell>
                                    {visibleColumns.status && <TableCell><Skeleton className="h-4 w-32"/></TableCell>}
                                    {visibleColumns.name && <TableCell><Skeleton className="h-4 w-48"/></TableCell>}
                                    {visibleColumns.type && <TableCell><Skeleton className="h-4 w-32"/></TableCell>}
                                    {visibleColumns.fabrication &&
                                        <TableCell><Skeleton className="h-4 w-16"/></TableCell>}
                                    {visibleColumns.ready_date &&
                                        <TableCell><Skeleton className="h-4 w-24"/></TableCell>}
                                    {visibleColumns.created_at &&
                                        <TableCell><Skeleton className="h-4 w-32"/></TableCell>}
                                </TableRow>
                            ))
                        ) : orders.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                                    Заказы не найдены
                                </TableCell>
                            </TableRow>
                        ) : (
                            orders.map((order, index) => {
                                const createdAt = order.created_at
                                    ? format(fromUnixTime(order.created_at / 1000), "dd.MM.yyyy HH:mm", {locale: ru})
                                    : "—";

                                return (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-medium">
                                            {pagination.offset + index + 1}
                                        </TableCell>

                                        {visibleColumns.status && (
                                            <TableCell>
                                                <Badge variant="secondary" className="gap-2">
                                                    <div className="h-2 w-2 rounded-full"
                                                         style={{backgroundColor: order.status_color || "#ccc"}}/>
                                                    {order.status_name || "—"}
                                                </Badge>
                                            </TableCell>
                                        )}

                                        {visibleColumns.name && (
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{order.name}</div>
                                                    {order.number && <div
                                                        className="text-sm text-muted-foreground">№ {order.number}</div>}
                                                </div>
                                            </TableCell>
                                        )}

                                        {visibleColumns.type && (
                                            <TableCell>
                                                <Badge variant="outline" className="gap-2">
                                                    {getOrderTypeLabel(getOrderType(order))}
                                                </Badge>
                                            </TableCell>
                                        )}

                                        {visibleColumns.fabrication && (
                                            <TableCell>{order.fabrication ? `${order.fabrication} дн.` : "—"}</TableCell>
                                        )}

                                        {visibleColumns.ready_date && (
                                            <TableCell>
                                                {order.ready_date ? format(new Date(order.ready_date), "dd.MM.yyyy") : "—"}
                                            </TableCell>
                                        )}

                                        {visibleColumns.created_at && <TableCell>{createdAt}</TableCell>}
                                    </TableRow>
                                );
                            })
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Пагинация */}
            {totalPages > 0 && (
                <div className="flex items-center justify-between">
                    <div className="text-sm text-muted-foreground">
                        Показано {pagination.offset + 1}–{Math.min(pagination.offset + pagination.limit, pagination.total)} из {pagination.total}
                    </div>

                    <div className="flex items-center gap-2 font-medium">
                        <span className="text-sm text-foreground whitespace-nowrap">Строк на странице:</span>
                        <Select value={pagination.limit.toString()} onValueChange={handlePageSizeChange}>
                            <SelectTrigger className="w-20">
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                {PAGE_SIZES.map(size => (
                                    <SelectItem key={size} value={size.toString()}>{size}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {totalPages > 1 && (
                            <Pagination>
                                <PaginationContent>
                                    <PaginationItem>
                                        <PaginationPrevious
                                            onClick={() => handlePageChange(Math.max(0, pagination.offset - pagination.limit))}
                                            className={pagination.offset === 0 ? "pointer-events-none opacity-50" : "cursor-pointer"}
                                        />
                                    </PaginationItem>

                                    {/* Генерация 3 кнопок страниц */}
                                    {(() => {
                                        const currentPage = Math.floor(pagination.offset / pagination.limit) + 1;
                                        const pages: number[] = [];

                                        // Всегда 3 кнопки
                                        for (let i = -1; i <= 1; i++) {
                                            const page = currentPage + i;
                                            if (page >= 1 && page <= totalPages) {
                                                pages.push(page);
                                            }
                                        }

                                        // Если меньше 3 — дополняем
                                        while (pages.length < 3 && pages.length < totalPages) {
                                            const first = pages[0] || 1;
                                            const last = pages[pages.length - 1] || 1;
                                            if (first > 1) pages.unshift(first - 1);
                                            else if (last < totalPages) pages.push(last + 1);
                                        }

                                        return pages.map(page => (
                                            <PaginationItem key={page}>
                                                <PaginationLink
                                                    onClick={() => handlePageChange((page - 1) * pagination.limit)}
                                                    isActive={(page - 1) * pagination.limit === pagination.offset}
                                                    className="cursor-pointer"
                                                >
                                                    {page}
                                                </PaginationLink>
                                            </PaginationItem>
                                        ));
                                    })()}

                                    <PaginationItem>
                                        <PaginationNext
                                            onClick={() => handlePageChange(pagination.offset + pagination.limit)}
                                            className={pagination.offset + pagination.limit >= pagination.total ? "pointer-events-none opacity-50" : "cursor-pointer"}
                                        />
                                    </PaginationItem>
                                </PaginationContent>
                            </Pagination>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}