import Tribute from "tributejs";

export default {
    install(app) {
        app.config.globalProperties.$tribute = {
            /**
             * Initializes a Tribute instance with the provided configuration object.
             *
             * @param {Object} config - Configuration object for Tribute.
             * @returns {Tribute} Tribute instance.
             */
            init: (config) => {
                const defaultConfig = {
                    values: null,
                    trigger: "@",
                    lookup: "key",
                    fillAttr: "value",
                    selectClass: 'highlighted-tribute-item',
                    containerClass: 'tribute-container bg-white border border-gray-300 px-4 py-2 rounded shadow-lg z-[99999] min-w-[210px] max-w-[360px] max-h-[50vh] overflow-y-auto dark:bg-cherry-800 dark:text-white',
                    selectTemplate: (item) => `@${item.original.value}`,
                    menuItemTemplate: (item) => `<div class="p-1.5 rounded-md text-base cursor-pointer transition-all max-sm:place-self-center">${item.original.key}</div>`,
                    noMatchTemplate: null,
                };

                // Merge the default config with the user-provided config
                const finalConfig = { ...defaultConfig, ...config };
                // Create and return the Tribute instance
                return new Tribute(finalConfig);
            },
        };
    },
};
