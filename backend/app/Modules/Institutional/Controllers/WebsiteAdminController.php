<?php

namespace App\Modules\Institutional\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InstitutionContent;
use App\Models\InstitutionInquiry;
use App\Models\InstitutionMedia;
use App\Models\InstitutionPost;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Modules\Operations\Services\OperationsAudit;
use Illuminate\Support\Facades\Gate;

class WebsiteAdminController extends Controller
{
    private function authorizeEditor(Request $request): void
    {
        if (!$request->user()) {
            throw new AuthenticationException('Please sign in to continue.', ['web'], '/admissions/login');
        }
        abort_unless($request->user()->can('website.manage'), 403);
    }

    public function dashboard(Request $request): View
    {
        $this->authorizeEditor($request);
        return view('institutional.admin-dashboard', [
            'title' => 'Website Management',
            'postCount' => InstitutionPost::count(),
            'inquiryCount' => InstitutionInquiry::where('status','new')->count(),
            'imageCount' => InstitutionMedia::count(),
            'posts' => InstitutionPost::latest()->limit(30)->get(),
            'inquiries' => InstitutionInquiry::latest()->limit(20)->get(),
            'media' => InstitutionMedia::latest()->limit(30)->get(),
            'sections' => config('stsams_institution.topics'),
            'content' => InstitutionContent::all()->keyBy(fn ($item) => $item->section . '.' . $item->content_key),
        ]);
    }

    public function saveContent(Request $request): RedirectResponse
    {
        $this->authorizeEditor($request);
        $data = $request->validate([
            'section' => ['required','string','max:40'],
            'content_key' => ['required','string','max:100'],
            'body' => ['nullable','string','max:40000'],
        ]);
        $topics = config('stsams_institution.topics', []);
        abort_unless(isset($topics[$data['section']][$data['content_key']]), 422);
        $record = InstitutionContent::firstOrNew([
            'section' => $data['section'], 'content_key' => $data['content_key'],
        ]);
        $record->forceFill(['pending_body' => $data['body'] ?? '', 'pending_by' => $request->user()->id])->save();
        app(OperationsAudit::class)->record($request->user(), 'website_content', $record->id,
            'website.content.drafted', ['section' => $data['section'], 'key' => $data['content_key']]);
        return back()->with('success','Draft saved. An authorized publisher must publish it.');
    }

    public function publishContent(Request $request, InstitutionContent $content): RedirectResponse
    {
        $this->authorizeEditor($request);
        Gate::authorize('website.publish');
        abort_if($content->pending_by === null, 422, 'There is no content draft to publish.');
        $content->forceFill([
            'body' => $content->pending_body, 'updated_by' => $request->user()->id,
            'pending_body' => null, 'pending_by' => null,
        ])->save();
        app(OperationsAudit::class)->record($request->user(), 'website_content', $content->id,
            'website.content.published', ['section' => $content->section, 'key' => $content->content_key]);
        return back()->with('success', 'Approved website content published.');
    }

    public function savePost(Request $request, ?InstitutionPost $post = null): RedirectResponse
    {
        $this->authorizeEditor($request);
        $data = $request->validate([
            'category' => ['required',Rule::in(['announcement','admission','news','event'])],
            'title' => ['required','string','max:180'],
            'summary' => ['required','string','max:1200'],
            'body' => ['required','string','max:40000'],
            'publish' => ['nullable','boolean'],
            'event_starts_at' => ['nullable','date'],
            'event_location' => ['nullable','string','max:180'],
        ]);
        $wantsPublish = (bool)($data['publish'] ?? false);
        if ($wantsPublish) { Gate::authorize('website.publish'); }
        if ($post?->exists && $post->is_published) { Gate::authorize('website.publish'); }
        $post ??= new InstitutionPost();
        if (!$post->exists) {
            $post->created_by = $request->user()->id;
            $post->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(8));
        }
        $post->fill(collect($data)->only(['category','title','summary','body','event_starts_at','event_location'])->all());
        $post->is_published = (bool)($data['publish'] ?? false);
        if ($post->is_published && !$post->published_at) {
            $post->published_at = now();
        }
        $post->save();
        app(OperationsAudit::class)->record($request->user(), 'website_post', $post->id,
            $wantsPublish ? 'website.post.published' : 'website.post.drafted', ['category' => $post->category]);
        return back()->with('success','Publication saved.');
    }

    public function deletePost(Request $request, InstitutionPost $post): RedirectResponse
    {
        $this->authorizeEditor($request);
        if ($post->is_published) { Gate::authorize('website.publish'); }
        $post->delete();
        app(OperationsAudit::class)->record($request->user(), 'website_post', $post->id,
            'website.post.deleted', ['category' => $post->category]);
        return back()->with('success','Publication deleted.');
    }

    public function uploadMedia(Request $request): RedirectResponse
    {
        $this->authorizeEditor($request);
        $data = $request->validate([
            'kind' => ['required',Rule::in(['hero','gallery'])],
            'title' => ['required','string','max:160'],
            'alt_text' => ['required','string','max:200'],
            'photo' => ['required','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'publish' => ['nullable','boolean'],
        ]);
        if ($data['publish'] ?? false) { Gate::authorize('website.publish'); }
        $path = $request->file('photo')->store('stsams/institution', 'public');
        try {
            $media = InstitutionMedia::create([
                'kind' => $data['kind'],
                'title' => $data['title'],
                'alt_text' => $data['alt_text'],
                'file_path' => $path,
                'is_published' => (bool)($data['publish'] ?? false),
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        app(OperationsAudit::class)->record($request->user(), 'website_media', $media->id,
            ($data['publish'] ?? false) ? 'website.media.published' : 'website.media.drafted', ['kind' => $data['kind']]);
        return back()->with('success', 'Institutional photograph uploaded.');
    }

    public function deleteMedia(Request $request, InstitutionMedia $media): RedirectResponse
    {
        $this->authorizeEditor($request);
        if ($media->is_published) { Gate::authorize('website.publish'); }
        $path = $media->file_path;
        $media->delete();
        Storage::disk('public')->delete($path);
        app(OperationsAudit::class)->record($request->user(), 'website_media', $media->id,
            'website.media.deleted', ['kind' => $media->kind]);
        return back()->with('success','Photograph removed.');
    }

    public function resolveInquiry(Request $request, InstitutionInquiry $inquiry): RedirectResponse
    {
        $this->authorizeEditor($request);
        $request->validate(['status' => ['required',Rule::in(['new','reviewed','closed'])]]);
        $before = $inquiry->status;
        $inquiry->update(['status'=>$request->input('status')]);
        app(OperationsAudit::class)->record($request->user(), 'website_inquiry', $inquiry->id,
            'website.inquiry.updated', ['from' => $before, 'to' => $inquiry->status]);
        return back()->with('success','Inquiry updated.');
    }
}
