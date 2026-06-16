<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Http\Requests\TemplateRequest;
use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class TemplateController extends Controller
{
    /**
     * List templates.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $templates = Template::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Template $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'updated_at' => $template->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the new-template builder.
     */
    public function create(): Response
    {
        return Inertia::render('Templates/Create');
    }

    /**
     * Store a new template.
     */
    public function store(TemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $template = Template::create([
            ...$data,
            'structured_html' => $this->inlineCss($data['html']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template created.')]);

        return to_route('templates.edit', $template);
    }

    /**
     * Show the builder for an existing template.
     */
    public function edit(Template $template): Response
    {
        return Inertia::render('Templates/Edit', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'html' => $template->html,
            ],
            'lists' => EmailList::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (EmailList $list): array => ['value' => $list->id, 'label' => $list->name])
                ->all(),
        ]);
    }

    /**
     * Update a template.
     */
    public function update(TemplateRequest $request, Template $template): RedirectResponse
    {
        $data = $request->validated();

        $template->update([
            ...$data,
            'structured_html' => $this->inlineCss($data['html']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template saved.')]);

        return to_route('templates.edit', $template);
    }

    /**
     * Delete a template.
     */
    public function destroy(Template $template): RedirectResponse
    {
        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template deleted.')]);

        return to_route('templates.index');
    }

    /**
     * Start a draft campaign for a list from this template.
     */
    public function campaign(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'email_list_id' => ['required', Rule::exists('email_lists', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);

        $list = EmailList::findOrFail($data['email_list_id']);

        Campaign::create([
            'email_list_id' => $list->id,
            'template_id' => $template->id,
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'from_email' => $list->default_from_email,
            'from_name' => $list->default_from_name,
            'reply_to_email' => $list->default_reply_to_email,
            'html' => $template->html,
            'structured_html' => $template->structured_html ?? $this->inlineCss($template->html),
            'status' => CampaignStatus::DRAFT->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Draft campaign created.')]);

        return to_route('lists.show', $list);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(Template $template): RedirectResponse
    {
        $copy = $template->replicate(['created_at', 'updated_at']);
        $copy->name = "{$template->name} (copy)";
        $copy->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template duplicated.')]);

        return to_route('templates.edit', $copy);
    }

    /**
     * Send a test render of the template to an email address.
     */
    public function test(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $html = $this->inlineCss($this->fillSampleData($template->html));

        try {
            Mail::html($html, function ($message) use ($data, $template): void {
                $message->to($data['email'])->subject("[Test] {$template->name}");
            });

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Test sent to :email.', ['email' => $data['email']]),
            ]);
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Mail delivery failed: '.$e->getMessage(),
            ]);
        }

        return back();
    }

    /**
     * Store an uploaded image and return its public URL.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('templates', 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }

    /**
     * Inline a template's CSS so it renders consistently in email clients.
     */
    private function inlineCss(string $html): string
    {
        return (new CssToInlineStyles)->convert($html);
    }

    /**
     * Replace template slots with sample data for previews and test sends.
     */
    private function fillSampleData(string $html): string
    {
        return preg_replace_callback(
            '/\[\[\[\s*([a-zA-Z0-9_-]+)(?::(text|image))?\s*\]\]\]/',
            function (array $match): string {
                if (($match[2] ?? null) === 'image') {
                    return 'https://picsum.photos/seed/'.urlencode($match[1]).'/600/240';
                }

                return 'Sample '.str_replace(['-', '_'], ' ', $match[1]);
            },
            $html,
        ) ?? $html;
    }
}
