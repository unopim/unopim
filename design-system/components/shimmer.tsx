// components/ui/shimmer.tsx
// Matches UnoPim skeleton loading states
import * as React from "react";
import { cn } from "@/lib/utils";

interface ShimmerProps extends React.HTMLAttributes<HTMLDivElement> {
  animated?: boolean;
  height?: string;
  width?: string;
  rounded?: string;
}

function Shimmer({
  animated = true,
  height = "h-4",
  width = "w-full",
  rounded = "rounded",
  className,
  ...props
}: ShimmerProps) {
  return (
    <div
      className={cn(
        height,
        width,
        rounded,
        animated
          ? "bg-gray-200 dark:bg-[#28273F] animate-pulse"
          : "bg-gray-200 dark:bg-[#28273F]",
        className
      )}
      {...props}
    />
  );
}

// Pre-built card skeleton
function CardShimmer({ className }: { className?: string }) {
  return (
    <div className={cn("bg-white dark:bg-[#26283D] rounded-md border border-gray-200 dark:border-[#26283D] p-4 space-y-3", className)}>
      <Shimmer height="h-4" width="w-1/3" />
      <Shimmer height="h-4" width="w-full" />
      <Shimmer height="h-4" width="w-4/5" />
      <Shimmer height="h-4" width="w-2/3" />
    </div>
  );
}

// Table row skeleton
function TableRowShimmer({ columns = 4, className }: { columns?: number; className?: string }) {
  return (
    <div className={cn("flex items-center gap-4 px-4 py-4 border-b border-gray-100 dark:border-[#28273F]", className)}>
      {Array.from({ length: columns }).map((_, i) => (
        <Shimmer key={i} height="h-4" width="w-full" />
      ))}
    </div>
  );
}

export { Shimmer, CardShimmer, TableRowShimmer };
