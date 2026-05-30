// components/ui/layout.tsx
// Sidebar + Header layout shell — matches UnoPim admin layout exactly
"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

// ─── Header / Navbar ─────────────────────────────────────────────────────────
// sticky top-0, z-[10001], light/dark bg, border-b
interface HeaderProps extends React.HTMLAttributes<HTMLElement> {
  logo?: React.ReactNode;
  actions?: React.ReactNode;
}

function Header({ logo, actions, className, children, ...props }: HeaderProps) {
  return (
    <header
      className={cn(
        "sticky top-0 z-[10001] flex items-center justify-between gap-4",
        "px-4 py-2.5 border-b",
        "bg-white dark:bg-[#28273F] dark:border-[#26283D]",
        className
      )}
      {...props}
    >
      <div className="flex items-center gap-3">
        {logo}
        {children}
      </div>
      {actions && <div className="flex items-center gap-1">{actions}</div>}
    </header>
  );
}

// Icon button used in the header (notification bell, dark mode toggle, etc.)
function HeaderIconButton({ className, ...props }: React.ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      className={cn(
        "rounded-md p-1.5 text-2xl text-gray-600 dark:text-slate-200",
        "hover:bg-violet-50 dark:hover:bg-[#1F1C30]",
        "transition-colors cursor-pointer",
        className
      )}
      {...props}
    />
  );
}

// ─── Sidebar ──────────────────────────────────────────────────────────────────
interface SidebarProps extends React.HTMLAttributes<HTMLElement> {
  collapsed?: boolean;
}

function Sidebar({ collapsed = false, className, ...props }: SidebarProps) {
  return (
    <aside
      className={cn(
        "fixed inset-y-0 left-0 z-[1000] flex flex-col",
        "bg-white dark:bg-[#28273F]",
        "transition-all duration-300 overflow-hidden",
        "[box-shadow:0px_8px_10px_0px_rgba(0,0,0,0.20)]",
        collapsed ? "w-[70px]" : "w-[270px]",
        className
      )}
      {...props}
    />
  );
}

// Individual nav item in the sidebar
interface SidebarItemProps extends React.HTMLAttributes<HTMLDivElement> {
  icon?: React.ReactNode;
  label?: string;
  active?: boolean;
  collapsed?: boolean;
}

function SidebarItem({ icon, label, active, collapsed, className, ...props }: SidebarItemProps) {
  return (
    <div
      className={cn(
        "flex items-center gap-2 px-3 py-2 rounded-md",
        "text-sm font-medium cursor-pointer",
        "transition-colors",
        active
          ? "bg-violet-100 text-violet-700 dark:bg-[#1F1C30] dark:text-violet-400"
          : "text-gray-600 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-[#1F1C30]",
        className
      )}
      {...props}
    >
      {icon && (
        <span className={cn("text-2xl flex-shrink-0", active ? "text-violet-700 dark:text-violet-400" : "text-gray-600 dark:text-slate-50")}>
          {icon}
        </span>
      )}
      {!collapsed && label && <span className="truncate">{label}</span>}
    </div>
  );
}

// ─── Main layout shell ────────────────────────────────────────────────────────
interface AppShellProps {
  header?: React.ReactNode;
  sidebar?: React.ReactNode;
  sidebarCollapsed?: boolean;
  children: React.ReactNode;
}

function AppShell({ header, sidebar, sidebarCollapsed = false, children }: AppShellProps) {
  const sidebarWidth = sidebarCollapsed ? 70 : 270;

  return (
    <div className="min-h-screen bg-violet-50/50 dark:bg-[#1F1C30]">
      {header}
      <div className="flex">
        {sidebar && (
          <div style={{ width: sidebarWidth }} className="flex-shrink-0 transition-all duration-300">
            {sidebar}
          </div>
        )}
        <main
          className="flex-1 min-w-0 p-4 transition-all duration-300"
        >
          {children}
        </main>
      </div>
    </div>
  );
}

export { Header, HeaderIconButton, Sidebar, SidebarItem, AppShell };
