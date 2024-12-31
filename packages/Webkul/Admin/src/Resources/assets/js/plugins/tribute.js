import Tribute from "tributejs";

export default {
    install(app) {
        app.config.globalProperties.$tribute = {
            /**
             * Generates a formatted price string using the provided price, localeCode, and currencyCode.
             *
             * @param {array} values - data.
             */
            init: (
                values,
                noMatchTemplate = null,
                trigger = '@', 
                lookup = "key",
                fillAttr = "value",
                containerClass = 'tribute-container bg-white border border-gray-300 px-4 py-2 rounded shadow-lg z-[99999] min-w-[210px] max-w-[360px] max-h-[50vh] overflow-y-auto'
            ) => {
                const tribute = new Tribute({
                    values: values,
                    selectTemplate: (item) => {
                        return `@${item.original.value}`; // Format how the mention will appear
                    },
                    containerClass: containerClass,
                    menuItemTemplate: (item) => {
                        return `<div class="p-1.5 rounded-md text-base cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center">${item.original.key}</div>`; // Format how the dropdown list will appear
                    },

                    noMatchTemplate: noMatchTemplate,
                    trigger: trigger,
                    lookup: lookup,
                    fillAttr: fillAttr,
                });

                return tribute;
            },
        };
    },
};
