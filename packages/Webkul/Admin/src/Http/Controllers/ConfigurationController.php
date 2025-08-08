<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Requests\ConfigurationForm;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Tree;
use Webkul\MagicAI\Contracts\Validator\ConfigValidator;

class ConfigurationController extends Controller
{
    /**
     * Tree instance.
     *
     * @var \Webkul\Core\Tree
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
     *
     * @return \Illuminate\View\View
     */
    public function index()
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $results = $this->coreConfigRepository->search($this->configTree->items, request()->query('query'));

        return new JsonResponse([
            'data' => $results,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ConfigurationForm $request)
    {
        try {

            $data = $request->all();
            $slug = request()->route('slug');
            $slug2 = request()->route('slug2');
            $config = config('core');
            foreach ($config as $section) {
                if (isset($section['key']) && $section['key'] == "{$slug}.{$slug2}") {
                    $configValidator = isset($section['validator']) ? app($section['validator']) : null;

                    if ($configValidator instanceof ConfigValidator) {
                        $configValidator->validate($data);
                    }
                }
            }

            $this->coreConfigRepository->create($request->except(['_token', 'admin_locale']));

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
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download()
    {
        $path = request()->route()->parameters()['path'];

        $fileName = 'configuration/'.$path;

        $config = $this->coreConfigRepository->findOneByField('value', $fileName);

        return Storage::download($config['value']);
    }
}
