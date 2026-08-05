// components/ui/input.tsx
// Matches UnoPim form input styling
import * as React from "react";
import { cn } from "@/lib/utils";

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  error?: boolean;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, type, error, ...props }, ref) => {
    return (
      <input
        type={type}
        className={cn(
          // Base
          "w-full rounded-md border px-3 py-2.5 text-sm transition-colors",
          // Light mode
          "border-gray-400 text-gray-600 bg-white",
          "placeholder:text-gray-400",
          "hover:border-gray-400",
          "focus:border-gray-400 focus:outline-none",
          // Dark mode
          "dark:bg-[#26283D] dark:text-gray-300 dark:border-gray-600",
          "dark:placeholder:text-gray-500",
          "dark:hover:border-slate-300 dark:focus:border-slate-300",
          // Error state
          error && "!border-red-600",
          className
        )}
        ref={ref}
        {...props}
      />
    );
  }
);
Input.displayName = "Input";

// ─── Label ───────────────────────────────────────────────────────────────────
export interface LabelProps extends React.LabelHTMLAttributes<HTMLLabelElement> {
  required?: boolean;
  locale?: string; // shows a localizable badge e.g. "en"
}

const Label = React.forwardRef<HTMLLabelElement, LabelProps>(
  ({ className, children, required, locale, ...props }, ref) => {
    return (
      <label
        className={cn(
          "flex items-center gap-1 mb-1.5 text-xs text-gray-800 dark:text-white font-medium",
          className
        )}
        ref={ref}
        {...props}
      >
        {children}
        {required && <span className="text-red-600">*</span>}
        {locale && (
          <span className="bg-gray-100 border border-gray-200 rounded px-1 text-[10px] text-gray-600 font-semibold">
            {locale}
          </span>
        )}
      </label>
    );
  }
);
Label.displayName = "Label";

// ─── Textarea ────────────────────────────────────────────────────────────────
const Textarea = React.forwardRef<
  HTMLTextAreaElement,
  React.TextareaHTMLAttributes<HTMLTextAreaElement> & { error?: boolean }
>(({ className, error, ...props }, ref) => {
  return (
    <textarea
      className={cn(
        "w-full rounded-md border px-3 py-2.5 text-sm transition-colors resize-y min-h-[80px]",
        "border-gray-400 text-gray-600 bg-white",
        "placeholder:text-gray-400",
        "hover:border-gray-400 focus:border-gray-400 focus:outline-none",
        "dark:bg-[#26283D] dark:text-gray-300 dark:border-gray-600",
        "dark:hover:border-slate-300 dark:focus:border-slate-300",
        error && "!border-red-600",
        className
      )}
      ref={ref}
      {...props}
    />
  );
});
Textarea.displayName = "Textarea";

// ─── Form Control Group ───────────────────────────────────────────────────────
// Convenience wrapper: label + input + error message
interface ControlGroupProps {
  label?: string;
  required?: boolean;
  locale?: string;
  error?: string;
  children: React.ReactNode;
  className?: string;
}

function ControlGroup({ label, required, locale, error, children, className }: ControlGroupProps) {
  return (
    <div className={cn("flex flex-col", className)}>
      {label && (
        <Label required={required} locale={locale}>
          {label}
        </Label>
      )}
      {children}
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}

export { Input, Label, Textarea, ControlGroup };
