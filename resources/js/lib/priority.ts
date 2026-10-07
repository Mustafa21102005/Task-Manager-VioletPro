import type { TaskPriority } from '@/types';

export const priorityBadgeClasses: Record<TaskPriority, string> = {
    low: 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-800 dark:bg-slate-500/20 dark:text-slate-300 dark:hover:bg-slate-500/30 dark:hover:text-slate-200',

    medium: 'bg-sky-100 text-sky-800 hover:bg-sky-200 hover:text-sky-900 dark:bg-sky-500/20 dark:text-sky-300 dark:hover:bg-sky-500/30 dark:hover:text-sky-200',

    high: 'bg-amber-100 text-amber-800 hover:bg-amber-200 hover:text-amber-900 dark:bg-amber-500/20 dark:text-amber-300 dark:hover:bg-amber-500/30 dark:hover:text-amber-200',

    urgent: 'bg-red-600 font-semibold text-white hover:bg-red-700 hover:text-white',
};

export const priorityBarClasses: Record<TaskPriority, string> = {
    low: 'bg-slate-400',
    medium: 'bg-sky-500',
    high: 'bg-amber-500',
    urgent: 'bg-red-600',
};
