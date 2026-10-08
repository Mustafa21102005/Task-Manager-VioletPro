import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
    [key: string]: unknown;
    flash: {
        success?: string | null;
        error?: string | null;
        undo?: string | null;
    };
    notifications: NotificationsData | null;
}

export interface AppNotification {
    id: string;
    title: string;
    message: string;
    url: string | null;
    read: boolean;
    created_at: string;
}

export interface NotificationsData {
    unread_count: number;
    items: AppNotification[];
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type TaskRecurrence = 'daily' | 'weekly' | 'monthly' | 'custom';

export type RecurrenceUnit = 'day' | 'week' | 'month';

export interface Task {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    priority: TaskPriority;
    priority_label: string;
    category_id: number;
    category_name: string;
    is_completed: boolean;
    recurrence: TaskRecurrence | null;
    recurrence_label: string | null;
    recurrence_interval: number | null;
    recurrence_unit: RecurrenceUnit | null;
    recurrence_days: number[] | null;
}

export interface Category {
    id: number;
    name: string;
}

export interface CategoryWithCount extends Category {
    tasks_count: number;
}

export interface CalendarDay {
    date: string;
    day: number;
    in_month: boolean;
    is_today: boolean;
    is_past: boolean;
}

export interface TaskFilters {
    category?: string | null;
    priority?: string | null;
    open?: string | boolean | null;
    search?: string | null;
    date_from?: string | null;
    date_to?: string | null;
}

export interface DashboardStats {
    total: number;
    completed: number;
    open: number;
    due_today: number;
    overdue: number;
    completion_rate: number;
}

export interface FocusTask {
    id: number;
    title: string;
    due_date: string | null;
    due_status: 'overdue' | 'today' | null;
    priority: TaskPriority;
    priority_label: string;
    category_name: string | null;
}

export interface PriorityBreakdown {
    priority: TaskPriority;
    label: string;
    count: number;
}

export interface CategoryBreakdown {
    id: number;
    name: string;
    count: number;
}

export interface ActivityDay {
    date: string;
    count: number;
    is_today: boolean;
    is_future: boolean;
}

export interface ActivityStats {
    week: ActivityDay[];
    this_week: number;
    last_week: number;
    current_streak: number;
    best_streak: number;
    completed_today: number;
}

export interface PeriodProgress {
    total: number;
    completed: number;
    percent: number;
}

export interface DashboardProgress {
    today: PeriodProgress;
    week: PeriodProgress;
    month: PeriodProgress;
}

export type BreadcrumbItemType = BreadcrumbItem;
