<div class="p-4 rounded bg-white dark:bg-cherry-800 box-shadow">
    <div class="flex gap-4 flex-wrap">
        @for ($i = 1; $i <= 6; $i++)
            <div class="flex gap-2.5 flex-1 min-w-[200px]">
                <div class="shimmer w-[60px] h-[60px]"></div>

                <div class="grid gap-1 place-content-start">
                    <div class="shimmer w-[60px] h-[17px]"></div>

                    <div class="shimmer w-[100px] h-[17px]"></div>
                    
                    <div class="shimmer w-10 h-[17px]"></div>
                </div>
            </div>
        @endfor
    </div>
</div>