// components/ui/drawer.tsx
// Matches UnoPim drawer/panel — slides in from left or right
"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

interface DrawerProps {
  open: boolean;
  onClose: () => void;
  side?: "left" | "right";
  width?: string;
  children: React.ReactNode;
  className?: string;
}

function Drawer({ open, onClose, side = "right", width = "500px", children, className }: DrawerProps) {
  return (
    <>
      {/* Backdrop */}
      {open && (
        <div
          className="fixed inset-0 z-[10001] bg-black/60"
          onClick={onClose}
        />
      )}

      {/* Panel */}
      <div
        className={cn(
          "fixed inset-y-0 z-[10002] flex flex-col",
          "bg-white dark:bg-[#1F1C30]",
          "[box-shadow:0px_8px_10px_0px_rgba(0,0,0,0.20)]",
          "transition-transform duration-200 ease-in-out",
          side === "right" ? "right-0" : "left-0",
          // Slide in/out
          side === "right"
            ? open ? "translate-x-0" : "translate-x-full"
            : open ? "translate-x-0" : "-translate-x-full",
          className
        )}
        style={{ width }}
      >
        {children}
      </div>
    </>
  );
}

function DrawerHeader({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn("grid gap-y-2.5 p-3 border-b dark:border-[#26283D]", className)}
      {...props}
    />
  );
}

function DrawerBody({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={cn("flex-1 overflow-auto p-3", className)} {...props} />
  );
}

function DrawerFooter({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn("flex items-center justify-end gap-2 p-3 border-t dark:border-[#26283D]", className)}
      {...props}
    />
  );
}

export { Drawer, DrawerHeader, DrawerBody, DrawerFooter };
