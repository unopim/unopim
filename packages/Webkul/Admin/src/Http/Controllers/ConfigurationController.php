<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Requests\ConfigurationForm;
use Webkul\Core\Contracts\Validator\ConfigValidator;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Tree;

class ConfigurationController extends Controller
{
    /**
     * Tree instance.
     *
     * @var Tree
     */
    protected $configTree;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected CoreConfigRepository $coreConfigRepository)
    {
        $this->prepareConfigTree();
    }

    /**
     * Prepares config tree.
     *
     * @return void
     */
    public function prepareConfigTree()
    {
        $tree = Tree::create();

        foreach (config('core') as $item) {
            $tree->add($item);
        }

        $tree->items = core()->sortItems($tree->items);

        $this->configTree = $tree;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View|RedirectResponse
    {
        $groups = Arr::get(
            $this->configTree->items,
            request()->route('slug').'.children.'.request()->route('slug2').'.children'
        );

        if ($groups) {
            return view('admin::configuration.edit', [
                'config' => $this->configTree,
                'groups' => $groups,
            ]);
        }

        if (! $groups) {
            return back();
        }

        return view('admin::configuration.index', ['config' => $this->configTree]);
    }

    /**
     * Display a listing of the resource.
     */
    public function search(): JsonResponse
    {
        $results = $this->coreConfigRepository->search($this->configTree->items, request()->query('query'));

        return new JsonResponse([
            'data' => $results,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Restrict the persisted payload to declared config fields, so a crafted
     * request cannot write arbitrary/undeclared core-config codes. When the group
     * is addressed via the URL slug, the allow-list is further narrowed to that
     * section and its descendants (e.g. general.magic_ai edits
     * general.magic_ai.settings/.translation/...); the legacy slug-less post
     * (Magic AI) falls back to all declared fields.
     *
     * @return array<string, mixed>
     */
    protected function allowedConfig(ConfigurationForm $request, string $groupKey): array
    {
        $allowed = [];

        foreach (config('core') as $section) {
            $key = $section['key'] ?? null;

            if ($key === null) {
                continue;
            }

            if ($groupKey !== '' && $key !== $groupKey && ! str_starts_with((string) $key, $groupKey.'.')) {
                continue;
            }

            foreach ((array) ($section['fields'] ?? []) as $field) {
                if (! empty($field['name'])) {
                    $allowed[] = $key.'.'.$field['name'];
                }
            }
        }

        /** @var array<string, mixed> $payload */
        $payload = [];

        foreach (Arr::dot($request->except(['_token', 'admin_locale'])) as $code => $value) {
            $matches = collect($allowed)->contains(
                fn ($allowedCode) => $code === $allowedCode || str_starts_with($code, $allowedCode.'.')
            );

            if ($matches) {
                Arr::set($payload, $code, $value);
            }
        }

        foreach (['locale', 'channel'] as $scope) {
            if ($request->filled($scope)) {
                $payload[$scope] = $request->input($scope);
            }
        }

        return $payload;
    }

    public function store(ConfigurationForm $request): RedirectResponse
    {
        try {
            $data = $request->all();
            $slug = request()->route('slug');
            $slug2 = request()->route('slug2');
            $groupKey = implode('.', array_filter([$slug, $slug2]));

            foreach (config('core') as $section) {
                if (($section['key'] ?? null) === $groupKey) {
                    $configValidator = isset($section['validator']) ? app($section['validator']) : null;

                    if ($configValidator instanceof ConfigValidator) {
                        $configValidator->validate($data);
                    }

                    break;
                }
            }

            $this->coreConfigRepository->create($this->allowedConfig($request, $groupKey));

            session()->flash('success', trans('admin::app.configuration.index.save-message'));

            return redirect()->back();
        } catch (\Throwable $th) {
            \Log::info($th);
            session()->flash('error', trans('admin::app.catalog.products.index.magic-ai-validate-error'));

            return redirect()->back();
        }
    }

    /**
     * Download the file for the specified resource.
     */
    public function download(): StreamedResponse
    {
        $path = request()->route()->parameters()['path'];

        $fileName = 'configuration/'.$path;

        $config = $this->coreConfigRepository->findOneByField('value', $fileName);

        return Storage::download($config['value']);
    }
}
