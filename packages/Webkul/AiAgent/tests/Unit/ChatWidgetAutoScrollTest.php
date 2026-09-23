<?php

/**
 * The transcript must follow streamed text, tool-status rows and lazily
 * rendered blocks, and the current step must stay visible above the composer
 * even when the transcript is scrolled away from the latest message.
 */
function chatWidgetTemplate(): string
{
    $path = base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php');

    expect($path)->toBeReadableFile();

    return (string) file_get_contents($path);
}

it('observes transcript mutations so streamed output keeps scrolling', function () {
    $template = chatWidgetTemplate();

    expect($template)
        ->toContain('observeMessages()')
        ->toContain('new MutationObserver(() => this.scrollBottom())')
        ->toContain('childList: true, subtree: true, characterData: true')
        ->toContain("el.addEventListener('load', () => this.scrollBottom(), true)")
        ->toContain('this.messagesObserver?.disconnect();');
});

it('stops auto-scrolling while the user reads back, and resumes on their own actions', function () {
    $template = chatWidgetTemplate();

    expect($template)
        ->toContain('@scroll.passive="onMessagesScroll"')
        ->toContain('autoScroll: true,')
        ->toContain('scrollBottom(force = false)')
        ->toContain('this.autoScroll = (el.scrollHeight - el.scrollTop - el.clientHeight) <= 80;')
        ->toContain('this.scrollBottom(true);');
});

it('pins the live status above the composer', function () {
    $template = chatWidgetTemplate();

    expect($template)
        ->toContain('class="ap-live-status"')
        ->toContain('v-text="streamingStatus || trans.processing"')
        ->toContain('.ap-live-status {');

    expect(substr_count($template, 'v-text="streamingStatus'))->toBe(1);

    expect(substr_count($template, 'v-if="isLoading"'))->toBe(1)
        ->and($template)->not->toContain('animate-bounce');
});
