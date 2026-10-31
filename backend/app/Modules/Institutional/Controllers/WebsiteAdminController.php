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

class WebsiteAdminController extends Controller
{
    private function authorizeEditor(Request $request): void
    {
        if (!$request->user()) {
            throw new AuthenticationException('Please sign in to continue.', ['web'], '/admissions/login');
        }
        abort_unless($request->user()->hasAnyRole(['superadmin', 'admin']), 403);
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
        InstitutionContent::updateOrCreate(
            ['section' => $data['section'], 'content_key' => $data['content_key']],
            ['body' => $data['body'] ?? null, 'updated_by' => $request->user()->id]
        );
        return back()->with('success','Institutional page content saved.');
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
        return back()->with('success','Publication saved.');
    }

    public function deletePost(Request $request, InstitutionPost $post): RedirectResponse
    {
        $this->authorizeEditor($request);
        $post->delete();
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
        $path = $request->file('photo')->store('stsams/institution', 'public');
        try {
            InstitutionMedia::create([
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
        return back()->with('success', 'Institutional photograph uploaded.');
    }

    public function deleteMedia(Request $request, InstitutionMedia $media): RedirectResponse
    {
        $this->authorizeEditor($request);
        $path = $media->file_path;
        $media->delete();
        Storage::disk('public')->delete($path);
        return back()->with('success','Photograph removed.');
    }

    public function resolveInquiry(Request $request, InstitutionInquiry $inquiry): RedirectResponse
    {
        $this->authorizeEditor($request);
        $request->validate(['status' => ['required',Rule::in(['new','reviewed','closed'])]]);
        $inquiry->update(['status'=>$request->input('status')]);
        return back()->with('success','Inquiry updated.');
    }
}
