// components/ui/button.tsx
// Matches UnoPim admin button system exactly
import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

export const buttonVariants = cva(
  // Base — shared across all variants
  "inline-flex items-center gap-x-1 rounded-md text-sm font-semibold " +
  "cursor-pointer transition-all select-none " +
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-1 " +
  "disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none",
  {
    variants: {
      variant: {
        // .primary-button — violet filled
        default:
          "bg-violet-600 border border-violet-700 text-slate-50 " +
          "hover:bg-violet-500 hover:border-violet-500",

        // .secondary-button — outline
        secondary:
          "bg-white border-2 border-violet-600 text-violet-600 " +
          "hover:text-violet-500 hover:border-violet-500 hover:bg-violet-100 " +
          "dark:bg-[#26283D] dark:border-violet-500 dark:text-violet-400 " +
          "dark:hover:bg-[#1F1C30]",

        // .transparent-button — ghost/text
        ghost:
          "border-2 border-transparent text-violet-600 " +
          "hover:text-violet-500 hover:bg-violet-50 " +
          "dark:text-slate-50 dark:hover:bg-[#26283D]",

        // .danger-button — destructive
        destructive:
          "bg-red-600 border border-red-700 text-slate-50 hover:opacity-90",

        // Link style
        link:
          "text-violet-600 underline-offset-4 hover:underline p-0",
      },
      size: {
        default: "px-3 py-1.5",
        sm:      "px-2 py-1 text-xs",
        lg:      "px-4 py-2 text-base",
        icon:    "p-1.5",            // icon-only square button (header icons)
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, ...props }, ref) => {
    return (
      <button
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    );
  }
);
Button.displayName = "Button";

export { Button };
