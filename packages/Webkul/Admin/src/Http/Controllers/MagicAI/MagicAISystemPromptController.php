<?php

namespace Webkul\Admin\Http\Controllers\MagicAI;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\DataGrids\MagicAI\MagicAISystemPromptGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MagicAI\Repository\MagicAISystemPromptRepository;
use Webkul\MagicAI\Services\Prompt\Prompt;

class MagicAISystemPromptController extends Controller
{
    public function __construct(
        protected MagicAISystemPromptRepository $magicAiSystemPromptRepository,
        protected Prompt $promptService,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return app(MagicAISystemPromptGrid::class)->toJson();
        }

        return view('admin::configuration.magic-ai.system-prompt.index');
    }

    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'title'       => 'required',
            'tone'        => 'required',
            'is_enabled'  => 'required|boolean',
            'max_tokens'  => 'required|integer|min:1|max:32768',
            'temperature' => 'required|numeric|between:0,2',
        ]);

        $data = request()->only([
            'title',
            'tone',
            'is_enabled',
            'max_tokens',
            'temperature',
        ]);

        if ($data['is_enabled']) {
            $this->magicAiSystemPromptRepository->disableAllEnabledPrompts();
        }

        $this->magicAiSystemPromptRepository->create($data);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.system-prompt.message.save-success'),
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $prompt = $this->magicAiSystemPromptRepository->findOrFail($id);

        return new JsonResponse([
            'data' => $prompt,
        ]);
    }

    public function update(): JsonResponse
    {
        $this->validate(request(), [
            'title'       => 'required',
            'tone'        => 'required',
            'is_enabled'  => 'required|boolean',
            'max_tokens'  => 'required|integer|min:1|max:32768',
            'temperature' => 'required|numeric|between:0,2',
        ]);

        $data = request()->only(['title', 'tone', 'is_enabled', 'max_tokens', 'temperature']);

        $id = request()->id;

        if ($data['is_enabled']) {
            $this->magicAiSystemPromptRepository->disableAllEnabledPrompts();
        }

        $this->magicAiSystemPromptRepository->update($data, $id);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.system-prompt.message.update-success'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->magicAiSystemPromptRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.system-prompt.message.delete-success'),
            ]);
        } catch (\Exception $e) {
            Log::info($e);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.system-prompt.message.delete-fail'),
            ], 500);
        }
    }
}
