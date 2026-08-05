// components/ui/accordion.tsx
// Matches UnoPim accordion component
"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

interface AccordionItem {
  value: string;
  title: React.ReactNode;
  content: React.ReactNode;
}

interface AccordionProps {
  items: AccordionItem[];
  defaultOpen?: string[];
  className?: string;
}

function Accordion({ items, defaultOpen = [], className }: AccordionProps) {
  const [open, setOpen] = React.useState<Set<string>>(new Set(defaultOpen));

  const toggle = (value: string) => {
    setOpen((prev) => {
      const next = new Set(prev);
      next.has(value) ? next.delete(value) : next.add(value);
      return next;
    });
  };

  return (
    <div className={cn("flex flex-col gap-2", className)}>
      {items.map((item) => {
        const isOpen = open.has(item.value);
        return (
          <div
            key={item.value}
            className={cn(
              "bg-white dark:bg-[#26283D] rounded-md border border-gray-200 dark:border-[#26283D] overflow-hidden",
              "[box-shadow:0px_0px_0px_0px_rgba(0,0,0,0.03),0px_1px_1px_0px_rgba(0,0,0,0.03),0px_3px_3px_0px_rgba(0,0,0,0.03)]"
            )}
          >
            {/* Header */}
            <button
              onClick={() => toggle(item.value)}
              className={cn(
                "w-full flex items-center justify-between p-1.5 px-3",
                "text-sm font-medium text-gray-700 dark:text-slate-200",
                "hover:bg-gray-50 dark:hover:bg-[#28273F] transition-colors cursor-pointer"
              )}
            >
              <span>{item.title}</span>
              {/* chevron-up / chevron-down icon */}
              <span className={cn("text-lg text-gray-400 transition-transform duration-200", isOpen && "rotate-180")}>
                ▾
              </span>
            </button>

            {/* Content */}
            {isOpen && (
              <div className="px-4 pb-4 text-sm text-gray-600 dark:text-gray-300">
                {item.content}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

export { Accordion };
