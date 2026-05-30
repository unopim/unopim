// components/ui/table.tsx
// Matches UnoPim datagrid/table styling
import * as React from "react";
import { cn } from "@/lib/utils";

function Table({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "bg-white dark:bg-[#26283D]",
        "rounded-md border border-gray-200 dark:border-[#26283D]",
        "overflow-hidden",
        className
      )}
      {...props}
    />
  );
}

function TableHeader({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "flex items-center min-h-[47px] px-4 py-2.5",
        "bg-violet-50 dark:bg-[#26283D]",
        "border-b border-gray-200 dark:border-[#26283D]",
        "text-xs font-medium text-gray-600 dark:text-gray-300",
        className
      )}
      {...props}
    />
  );
}

function TableRow({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "flex items-center px-4 py-4",
        "border-b border-gray-100 dark:border-[#28273F]",
        "text-sm text-gray-600 dark:text-gray-300",
        "hover:bg-violet-50/30 dark:hover:bg-[#28273F]/30",
        "transition-colors",
        className
      )}
      {...props}
    />
  );
}

function TableCell({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={cn("flex-1 min-w-0", className)} {...props} />
  );
}

// Action button in table rows (edit, delete, view etc.)
function TableActionButton({ className, ...props }: React.ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      className={cn(
        "p-1.5 rounded-md text-2xl",
        "text-gray-600 dark:text-slate-300",
        "hover:bg-violet-100 dark:hover:bg-gray-800",
        "transition-colors cursor-pointer",
        className
      )}
      {...props}
    />
  );
}

// Empty state
function TableEmpty({ message = "No records found.", className }: { message?: string; className?: string }) {
  return (
    <div className={cn("flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500", className)}>
      <p className="text-sm">{message}</p>
    </div>
  );
}

export { Table, TableHeader, TableRow, TableCell, TableActionButton, TableEmpty };
