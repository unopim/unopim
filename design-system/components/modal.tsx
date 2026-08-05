// components/ui/modal.tsx
// Matches UnoPim modal — sizes: sm 400px, md 568px, lg 900px, full
"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

type ModalSize = "sm" | "md" | "lg" | "full";

const sizeMap: Record<ModalSize, string> = {
  sm:   "w-[400px]",
  md:   "w-[568px]",
  lg:   "w-[900px]",
  full: "w-[calc(100vw-100px)]",
};

interface ModalProps {
  open: boolean;
  onClose: () => void;
  size?: ModalSize;
  children: React.ReactNode;
  className?: string;
}

function Modal({ open, onClose, size = "md", children, className }: ModalProps) {
  // Close on backdrop click
  const handleBackdrop = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) onClose();
  };

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[10002] flex items-center justify-center bg-black/60"
      onClick={handleBackdrop}
    >
      <div
        className={cn(
          "relative bg-white dark:bg-gray-900 rounded-lg overflow-hidden",
          "[box-shadow:0px_8px_10px_0px_rgba(0,0,0,0.20),0px_6px_30px_0px_rgba(0,0,0,0.12),0px_16px_24px_0px_rgba(0,0,0,0.14)]",
          sizeMap[size],
          "max-h-[90vh] flex flex-col",
          className
        )}
      >
        {children}
      </div>
    </div>
  );
}

function ModalHeader({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "flex items-center justify-between gap-2.5 px-4 py-3 border-b dark:border-gray-800",
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
}

function ModalTitle({ className, ...props }: React.HTMLAttributes<HTMLHeadingElement>) {
  return (
    <h2
      className={cn("text-sm font-semibold text-gray-800 dark:text-slate-50", className)}
      {...props}
    />
  );
}

function ModalBody({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn("px-4 py-2.5 overflow-auto flex-1 border-b dark:border-gray-800", className)}
      {...props}
    />
  );
}

function ModalFooter({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn("flex items-center justify-end gap-2 px-4 py-2.5", className)}
      {...props}
    />
  );
}

// Close button (icon-cancel style)
interface ModalCloseProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {}

function ModalClose({ className, onClick, ...props }: ModalCloseProps) {
  return (
    <button
      className={cn(
        "rounded-md p-1 text-xl text-gray-400 hover:text-gray-600 dark:hover:text-slate-200",
        "hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors",
        className
      )}
      onClick={onClick}
      {...props}
    >
      ✕
    </button>
  );
}

export { Modal, ModalHeader, ModalTitle, ModalBody, ModalFooter, ModalClose };
