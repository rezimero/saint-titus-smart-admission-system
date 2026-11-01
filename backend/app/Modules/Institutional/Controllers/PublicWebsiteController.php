<?php

namespace App\Modules\Institutional\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InstitutionContent;
use App\Models\InstitutionInquiry;
use App\Models\InstitutionMedia;
use App\Models\InstitutionPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PublicWebsiteController extends Controller
{
    private const SECTIONS = [
        'about' => ['About the Institution', 'Our identity, values and institutional leadership.'],
        'academics' => ['Academics', 'Academic structures, programme categories and academic information.'],
        'admissions' => ['Admissions', 'Your route to applying to Saint Titus.'],
        'campus' => ['Campus Life', 'Student experience and support services.'],
        'news' => ['News & Events', 'Official updates and the institutional calendar.'],
        'administration' => ['Administration', 'Institutional offices and services.'],
    ];

    private function content(string $section): array
    {
        // Approved editorial copy is the initial public content. A published CMS
        // record overrides the default, while pending drafts stay private.
        $defaults = config('stsams_institution_editorial.sections.'.$section, []);
        $published = InstitutionContent::query()->where('section', $section)
            ->pluck('body', 'content_key')->all();
        $published = array_filter($published, static fn ($body) => is_string($body) && trim($body) !== '');

        return array_replace($defaults, $published);
    }

    private function publishedPosts(?string $category = null, int $limit = 8)
    {
        return InstitutionPost::query()->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($category === 'event', fn ($query) => $query->where('event_starts_at', '>=', now()))
            ->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get();
    }

    public function home(): View
    {
        return view('institutional.home', [
            'title' => 'Welcome',
            'content' => $this->content('home'),
            'aboutContent' => $this->content('about'),
            'announcements' => $this->publishedPosts('announcement', 3),
            'admissionNotices' => $this->publishedPosts('admission', 3),
            'news' => $this->publishedPosts('news', 3),
            'events' => $this->publishedPosts('event', 3),
            'hero' => InstitutionMedia::query()->where('kind','hero')->where('is_published',true)->latest()->first(),
            'gallery' => InstitutionMedia::query()->where('kind','gallery')->where('is_published',true)->latest()->limit(3)->get(),
        ]);
    }

    public function page(string $section): View
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);
        $posts = $section === 'news' ? $this->publishedPosts(null, 60) : collect();
        return view('institutional.page', [
            'section' => $section,
            'title' => self::SECTIONS[$section][0],
            'subtitle' => self::SECTIONS[$section][1],
            'topics' => config("stsams_institution.topics.{$section}", []),
            'content' => $this->content($section),
            'posts' => $posts,
            'gallery' => $section === 'news'
                ? InstitutionMedia::query()->where('kind','gallery')->where('is_published',true)->latest()->get()
                : collect(),
        ]);
    }

    public function post(string $slug): View
    {
        $post = InstitutionPost::query()->where('slug', $slug)->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })->firstOrFail();
        return view('institutional.post', ['post' => $post, 'title' => $post->title]);
    }

    public function contact(): View
    {
        $links = [];
        foreach ($this->content('contact') as $network => $url) {
            if (filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')) {
                $links[$network] = $url;
            }
        }
        return view('institutional.contact', ['title' => 'Contact Us', 'socialLinks' => $links]);
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:140'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:15', 'max:6000'],
            'company_website' => ['nullable', 'string', 'max:100'],
            'consent' => ['accepted'],
        ]);
        // A honeypot makes automated spam less attractive without leaking a detection signal.
        if (!empty($data['company_website'])) {
            return back()->with('success', 'Thank you. Your message has been received.');
        }
        InstitutionInquiry::create(collect($data)->only(['name','email','subject','message'])->all());
        return back()->with('success', 'Thank you. Your inquiry was received by the college.');
    }
}
