<?php

namespace Webkul\Admin\Http\Controllers\Magic;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\DataGrids\MagicAI\MagicSystemPromptGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MagicAI\Repository\MagicSystemPromptRepository;
use Webkul\MagicAI\Services\Prompt\Prompt;

class MagicSystemPromptController extends Controller
{
    public function __construct(
        protected MagicSystemPromptRepository $magicSystemPromptRepository,
        protected Prompt $promptService,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return app(MagicSystemPromptGrid::class)->toJson();
        }

        return view('admin::configuration.magic-ai-system-prompt.index');
    }

    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'title'       => 'required',
            'tone'        => 'required',
            'max_tokens'  => 'required|integer|min:1|max:32768',
            'temperature' => 'required|numeric|between:0,2',
        ]);

        $data = request()->only([
            'title',
            'tone',
            'max_tokens',
            'temperature',
        ]);

        $this->magicSystemPromptRepository->create($data);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.prompt.message.save-success'),
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $prompt = $this->magicSystemPromptRepository->findOrFail($id);

        return new JsonResponse([
            'data' => $prompt,
        ]);
    }

    public function update(): JsonResponse
    {
        $this->validate(request(), [
            'title'      => 'required',
            'tone'       => 'required',
        ]);

        $data = request()->only(['title', 'tone']);
        $this->magicSystemPromptRepository->update($data, request()->id);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.prompt.message.update-success'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->magicSystemPromptRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.prompt.message.delete-success'),
            ]);
        } catch (\Exception $e) {
            Log::info($e);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.prompt.message.delete-fail'),
            ], 500);
        }
    }
}
