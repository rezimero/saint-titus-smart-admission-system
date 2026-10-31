@extends('institutional.layout')
@section('content')
<section class="page-hero admin-hero"><div class="shell"><span class="eyebrow light">AUTHORIZED USERS ONLY</span><h1>Website administration</h1><p>Manage verified institutional information, public announcements, campus photographs and incoming inquiries.</p></div></section>
<div class="shell breadcrumb"><a href="{{ route('institution.home') }}">Website</a> / Website management</div>
<div class="shell admin-workspace">
 <div class="admin-stat-grid"><div class="admin-stat"><span>Publications</span><strong>{{ $postCount }}</strong></div><div class="admin-stat"><span>New contact inquiries</span><strong>{{ $inquiryCount }}</strong></div><div class="admin-stat"><span>Uploaded photographs</span><strong>{{ $imageCount }}</strong></div><a class="admin-stat link-stat" href="{{ url('/admissions/admin/catalogue') }}"><span>Admissions catalogue</span><strong>↗</strong></a></div>
 <p class="editor-warning"><strong>Official publication control:</strong> Publish only institution-approved information. Verify accreditation, programme approvals, fees and leadership titles before releasing them publicly. All text is displayed as plain text to prevent unsafe embedded HTML.</p>
 <section class="admin-block" id="page-content"><div class="heading-pair"><div><span class="eyebrow">01 / WEBSITE SECTIONS</span><h2>Page content editor</h2></div></div><p>Open a section and update its individual information fields. Unfilled fields remain clearly marked as awaiting official publication.</p>
 @foreach($sections as $section => $topics)
 <details class="editor-details"><summary>{{ ucfirst($section) }} <span>{{ count($topics) }} fields</span></summary>
   @foreach($topics as $key => $label)
    <form action="{{ route('institution.admin.content') }}" method="POST" class="editor-form">@csrf
      <input type="hidden" name="section" value="{{ $section }}"><input type="hidden" name="content_key" value="{{ $key }}"><label for="field-{{ $section }}-{{ $key }}">{{ $label }}</label><textarea id="field-{{ $section }}-{{ $key }}" name="body" rows="4" maxlength="40000" placeholder="Add approved {{ strtolower($label) }} information here...">{{ $content->get($section.'.'.$key)?->body }}</textarea><button class="btn btn-primary btn-small" type="submit">Save this field</button>
    </form>
   @endforeach
 </details>
 @endforeach
 </section>
 <section class="admin-block" id="publications"><div class="heading-pair"><div><span class="eyebrow">02 / PUBLIC NEWSROOM</span><h2>News, events & admissions notices</h2></div></div>
 <details class="editor-details" open><summary>Create a publication</summary><form class="editor-form" action="{{ route('institution.admin.posts.create') }}" method="POST">@csrf
 <div class="form-two"><div><label for="newcategory">Category</label><select id="newcategory" name="category" required><option value="announcement">Announcement</option><option value="admission">Admission notification</option><option value="news">Institutional news / press release</option><option value="event">Upcoming event</option></select></div><div><label for="newtitle">Title</label><input id="newtitle" name="title" required maxlength="180"></div></div>
 <label for="newsummary">Short summary</label><textarea id="newsummary" name="summary" maxlength="1200" rows="2" required></textarea><label for="newbody">Full information</label><textarea id="newbody" name="body" maxlength="40000" rows="6" required></textarea>
 <div class="form-two"><div><label for="newtime">Event start (only for events)</label><input id="newtime" type="datetime-local" name="event_starts_at"></div><div><label for="newplace">Event venue (if applicable)</label><input id="newplace" name="event_location" maxlength="180"></div></div>
 <label class="check-line"><input type="checkbox" name="publish" value="1"> Publish publicly now</label><button class="btn btn-primary btn-small" type="submit">Save publication</button></form></details>
 @forelse($posts as $post)<details class="editor-details"><summary><strong>{{ $post->title }}</strong> <span>{{ ucfirst($post->category) }} · {{ $post->is_published ? 'Published' : 'Draft' }}</span></summary>
 <form class="editor-form" method="POST" action="{{ route('institution.admin.posts.update', $post) }}">@csrf @method('PUT')
 <label>Category</label><select name="category">@foreach(['announcement'=>'Announcement','admission'=>'Admission notification','news'=>'News','event'=>'Event'] as $type=>$label)<option value="{{ $type }}" @selected($post->category === $type)>{{ $label }}</option>@endforeach</select>
 <label>Title</label><input name="title" value="{{ $post->title }}" maxlength="180" required><label>Summary</label><textarea name="summary" rows="2" maxlength="1200" required>{{ $post->summary }}</textarea><label>Body</label><textarea name="body" rows="5" maxlength="40000" required>{{ $post->body }}</textarea>
 <div class="form-two"><div><label>Event date</label><input type="datetime-local" name="event_starts_at" value="{{ $post->event_starts_at?->format('Y-m-d\TH:i') }}"></div><div><label>Venue</label><input name="event_location" value="{{ $post->event_location }}" maxlength="180"></div></div>
 <label class="check-line"><input type="checkbox" name="publish" value="1" @checked($post->is_published)> Published</label><button class="btn btn-primary btn-small" type="submit">Update publication</button></form>
 <form class="delete-form" method="POST" action="{{ route('institution.admin.posts.delete', $post) }}" onsubmit="return confirm('Delete this publication permanently?')">@csrf @method('DELETE')<button class="delete-button" type="submit">Delete publication</button></form></details>
 @empty<p class="pending-text">No newsroom entries created yet.</p>@endforelse
 </section>
 <section class="admin-block" id="media"><div class="heading-pair"><div><span class="eyebrow">03 / GALLERY & BANNER</span><h2>Publish campus photographs</h2></div></div><p>Upload only authentic, institution-approved images. JPEG, PNG and WebP, up to 4 MB. The most recently uploaded published hero image appears on the homepage.</p>
 <form class="editor-form" method="POST" action="{{ route('institution.admin.media.create') }}" enctype="multipart/form-data">@csrf
 <div class="form-two"><div><label for="media-kind">Placement</label><select id="media-kind" name="kind"><option value="hero">Homepage hero banner</option><option value="gallery">Campus photo gallery</option></select></div><div><label for="media-file">Select image</label><input id="media-file" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></div></div>
 <label for="media-title">Photo title</label><input id="media-title" name="title" maxlength="160" required><label for="media-alt">Accessible image description</label><input id="media-alt" name="alt_text" maxlength="200" required><label class="check-line"><input type="checkbox" name="publish" value="1"> Publish image publicly</label><button class="btn btn-primary btn-small" type="submit">Upload photograph</button></form>
 <div class="media-admin-list">@forelse($media as $photo)<div><img src="{{ Storage::disk('public')->url($photo->file_path) }}" alt="{{ $photo->alt_text }}"><span>{{ $photo->title }} ({{ $photo->kind }} / {{ $photo->is_published ? 'Published' : 'Private' }})</span><form method="POST" action="{{ route('institution.admin.media.delete',$photo) }}" onsubmit="return confirm('Remove this photograph?')">@csrf @method('DELETE')<button class="delete-button" type="submit">Remove</button></form></div>@empty<p>No photos uploaded yet.</p>@endforelse</div>
 </section>
 <section class="admin-block" id="inquiries"><div class="heading-pair"><div><span class="eyebrow">04 / CONTACT INBOX</span><h2>Visitor inquiries</h2></div></div><p>Messages are available to superadmins and admins only. Do not share visitor data unnecessarily. The contact form does not send automatic emails.</p>
 @forelse($inquiries as $inquiry)<details class="editor-details"><summary>{{ $inquiry->subject }} <span>{{ $inquiry->status }} · {{ $inquiry->created_at->format('d M Y') }}</span></summary><div class="inquiry-body"><strong>{{ $inquiry->name }}</strong> — <a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a><p class="body-text">{{ $inquiry->message }}</p></div><form class="editor-form" action="{{ route('institution.admin.inquiries.update',$inquiry) }}" method="POST">@csrf @method('PATCH')<label>Review status</label><select name="status">@foreach(['new'=>'New','reviewed'=>'Reviewed','closed'=>'Closed'] as $value=>$label)<option value="{{ $value }}" @selected($inquiry->status===$value)>{{ $label }}</option>@endforeach</select><button class="btn btn-primary btn-small" type="submit">Update inquiry</button></form></details>
 @empty<p>No inquiries received.</p>@endforelse
 </section>
</div>
@endsection
