<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MagicAI\Contracts\MagicAISystemPrompt as MagicAISystemPromptContract;
use Webkul\MagicAI\Database\Factories\MagicAISystemPromptFactory;

class MagicAISystemPrompt extends Model implements HistoryAuditable, MagicAISystemPromptContract
{
    use HasFactory;
    use HistoryTrait;

    protected $table = 'magic_ai_system_prompts';

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['magicSystemPrompt'];

    protected $fillable = [
        'title',
        'tone',
        'max_tokens',
        'temperature',
        'is_enabled',
    ];

    protected static function newFactory(): Factory
    {
        return MagicAISystemPromptFactory::new();
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->is_enabled) {
                static::where('id', '!=', $model->id)->update(['is_enabled' => false]);
            }
        });
    }
}
