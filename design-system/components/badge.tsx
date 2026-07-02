// components/ui/badge.tsx
// Matches UnoPim .label-* and .pill-* status classes
import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

// ─── Status Badge (matches .label-* classes) ─────────────────────────────────
export const badgeVariants = cva(
  "inline-flex items-center rounded px-1.5 py-0.5 text-xs font-semibold",
  {
    variants: {
      variant: {
        pending:    "bg-yellow-100 text-yellow-800",
        processing: "bg-cyan-100   text-cyan-800",
        completed:  "bg-green-100  text-green-800",
        active:     "bg-green-100  text-green-800",
        closed:     "bg-blue-100   text-blue-800",
        canceled:   "bg-red-100    text-red-800",
        fraud:      "bg-red-100    text-red-800",
        info:       "bg-slate-100  text-slate-600",
        default:    "bg-gray-100   text-gray-700",
      },
    },
    defaultVariants: { variant: "default" },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return (
    <span className={cn(badgeVariants({ variant }), className)} {...props} />
  );
}

// ─── Priority Pill (matches .pill-low/medium/high) ────────────────────────────
export const pillVariants = cva(
  "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border text-white",
  {
    variants: {
      severity: {
        low:    "bg-red-600    border-red-700",
        medium: "bg-yellow-500 border-yellow-600",
        high:   "bg-green-600  border-green-700",
      },
    },
    defaultVariants: { severity: "medium" },
  }
);

export interface PillProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof pillVariants> {}

function Pill({ className, severity, ...props }: PillProps) {
  return (
    <span className={cn(pillVariants({ severity }), className)} {...props} />
  );
}

export { Badge, Pill };
