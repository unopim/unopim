// components/ui/switch.tsx
// Matches UnoPim boolean toggle styling exactly
"use client";

import * as React from "react";
import { cn } from "@/lib/utils";

interface SwitchProps {
  checked?: boolean;
  onChange?: (checked: boolean) => void;
  disabled?: boolean;
  className?: string;
  label?: string;
}

function Switch({ checked = false, onChange, disabled = false, className, label }: SwitchProps) {
  const handleClick = () => {
    if (!disabled) onChange?.(!checked);
  };

  return (
    <label className={cn("inline-flex items-center gap-2.5 cursor-pointer select-none", disabled && "opacity-50 cursor-not-allowed", className)}>
      <div
        role="switch"
        aria-checked={checked}
        onClick={handleClick}
        className={cn(
          // Track
          "relative inline-flex h-6 w-11 rounded-full transition-colors duration-200",
          checked ? "bg-violet-700" : "bg-gray-300 dark:bg-gray-600"
        )}
      >
        {/* Thumb */}
        <span
          className={cn(
            "absolute top-1 h-4 w-4 rounded-full bg-white shadow",
            "transition-transform duration-200",
            checked ? "translate-x-6" : "translate-x-1"
          )}
        />
      </div>
      {label && <span className="text-sm text-gray-600 dark:text-gray-300">{label}</span>}
    </label>
  );
}

export { Switch };
