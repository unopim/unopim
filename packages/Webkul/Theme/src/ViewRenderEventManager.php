<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Event;

class ViewRenderEventManager
{
    /**
     * Contains all themes
     */
    protected array $templates = [];

    /**
     * Paramters passed with event
     */
    protected ?array $params = null;

    /**
     * Fires event for rendering template
     *
     * @return string
     */
    public function handleRenderEvent(string $eventName, ?array $params = null): array
    {
        $this->params = $params ?? [];

        Event::dispatch($eventName, $this);

        return $this->templates;
    }

    /**
     *  get params
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     *  get param
     */
    public function getParam(mixed $name): mixed
    {
        return optional($this->params)[$name];
    }

    /**
     * Add templates for render
     *
     * @param  string  $template
     */
    public function addTemplate(mixed $template): void
    {
        $this->templates[] = $template;
    }

    /**
     * Renders templates
     */
    public function render(): string
    {
        $string = '';

        foreach ($this->templates as $template) {
            if (view()->exists($template)) {
                $string .= view($template, $this->params)->render();
            } elseif (is_string($template)) {
                $string .= $template;
            }
        }

        return $string;
    }
}
