# Building Assets — UnoPim

UnoPim uses **Vite** for frontend asset bundling with **Vue 3** and **Tailwind CSS**.

---

## Commands

```bash
# Install dependencies
npm install

# Build all assets (production)
npm run build

# Development mode with hot reload
npm run dev
```

---

## Vite Configuration

Each package can have its own `vite.config.js`. The root `vite.config.js` coordinates builds.

### Blade Integration

Use the `@unoPimVite()` directive in Blade templates to load built assets:

```blade
@unoPimVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'], 'admin')
```

Configuration in `config/unopim-vite.php`.

---

## Plugin Assets

For plugins with frontend assets:

1. Create `vite.config.js` in your package
2. Add build script to package.json
3. Use `@unoPimVite()` in views
4. Publish assets:

```bash
php artisan vendor:publish --tag=example-assets
```

---

## After Asset Changes

1. Rebuild the **Admin package** (if you changed files under `packages/Webkul/Admin/src/Resources/assets/`):

```bash
cd packages/Webkul/Admin && npx vite build
```

This builds to `public/themes/admin/default/build/`. The root `npm run build` does **NOT** build the Admin package — it only builds `resources/js/app.js`.

2. Rebuild the **root assets** (if you changed files under `resources/`):

```bash
npm run build
```

3. Publish plugin assets: `php artisan vendor:publish --tag={tag} --force`
4. Clear view cache: `php artisan view:clear`

> **CRITICAL**: After modifying ANY `.vue`, `.js`, or `.css` file inside the Admin package, you MUST run the Admin Vite build. Changes will NOT appear in the browser without this step.
