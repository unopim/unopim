// components/ui/tabs.tsx
// Matches UnoPim tab navigation
"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

interface TabsProps {
  value: string;
  onChange: (value: string) => void;
  tabs: Array<{ value: string; label: string; disabled?: boolean }>;
  className?: string;
}

function Tabs({ value, onChange, tabs, className }: TabsProps) {
  return (
    <div
      className={cn(
        "flex gap-4 border-b border-gray-200 dark:border-[#26283D]",
        className
      )}
    >
      {tabs.map((tab) => (
        <button
          key={tab.value}
          disabled={tab.disabled}
          onClick={() => onChange(tab.value)}
          className={cn(
            "px-1 pb-2 text-base font-medium border-b-2 -mb-px transition-colors cursor-pointer",
            tab.value === value
              ? "text-violet-700 dark:text-violet-400 border-violet-700 dark:border-violet-400"
              : "text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-200",
            tab.disabled && "opacity-50 cursor-not-allowed"
          )}
        >
          {tab.label}
        </button>
      ))}
    </div>
  );
}

export { Tabs };
