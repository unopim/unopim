import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import path from "path";
import fs from "fs";

/**
 * Self-host the TinyMCE distribution instead of loading it from a CDN. The
 * editor lazy-loads skins/themes/models/plugins/icons from `base_url` at
 * runtime, so the whole dist folder is copied verbatim (unhashed) to a stable
 * public path the Blade component points `base_url` at.
 */
function copyTinymce(destination) {
    const source = path.resolve(__dirname, "node_modules/tinymce");

    const isRedundant = (file) => {
        if (/\.(d\.ts|map|md)$/i.test(file) || file === "bower.json") {
            return true;
        }

        const min = file.replace(/\.(js|css)$/i, ".min.$1");

        return min !== file && fs.existsSync(min);
    };

    const prune = (dir) => {
        for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
            const full = path.join(dir, entry.name);

            if (entry.isDirectory()) {
                prune(full);
            } else if (isRedundant(full)) {
                fs.rmSync(full);
            }
        }
    };

    return {
        name: "unopim-copy-tinymce",
        apply: "build",
        closeBundle() {
            if (! fs.existsSync(source)) {
                this.warn("tinymce package not found in node_modules; skipping copy.");

                return;
            }

            fs.rmSync(destination, { recursive: true, force: true });
            fs.cpSync(source, destination, { recursive: true });
            prune(destination);
        },
    };
}

export default defineConfig(({ mode }) => {
    const envDir = "../../../";

    Object.assign(process.env, loadEnv(mode, envDir));

    return {
        build: {
            emptyOutDir: true,
            minify: "esbuild",
            cssCodeSplit: true,
            rollupOptions: {
                output: {
                    manualChunks: {
                        vue: ["vue"],
                        veeValidate: [
                            "vee-validate",
                            "@vee-validate/rules",
                            "@vee-validate/i18n",
                        ],
                        vendor: ["axios", "mitt", "flatpickr"],
                    },
                },
            },
        },

        envDir,

        server: {
            host: process.env.VITE_HOST || "localhost",
            port: process.env.VITE_PORT || 5173,
        },

        plugins: [
            vue(),

            copyTinymce(
                path.resolve(
                    __dirname,
                    "../../../public/themes/admin/default/build/tinymce"
                )
            ),

            laravel({
                hotFile: "../../../public/admin-default-vite.hot",
                publicDirectory: "../../../public",
                buildDirectory: "themes/admin/default/build",
                input: [
                    "src/Resources/assets/css/app.css",
                    "src/Resources/assets/js/app.js",
                ],
                refresh: true,
            }),
        ],

        experimental: {
            renderBuiltUrl(filename, { hostId, hostType, type }) {
                if (hostType === "css") {
                    return path.basename(filename);
                }
            },
        },
    };
});
