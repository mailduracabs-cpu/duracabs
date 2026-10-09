<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Partner KYC review</title>
<style>body{font:16px system-ui;background:#f3f6fa;color:#182432;max-width:900px;margin:30px auto;padding:16px}article{background:white;padding:24px;margin:16px 0;border-radius:12px}input,textarea,select,button{font:inherit;padding:10px;max-width:100%;box-sizing:border-box}label{display:block;margin:12px 0}a{color:#075eb3}button{background:#075eb3;color:white;border:0;border-radius:6px}textarea{width:100%}</style></head><body>
<h1>Partner KYC review</h1><p>Document review and partner account approval are separate. Account approval remains in the existing transporter admin screen.</p>
@if(session('status'))<article>{{ session('status') }}</article>@endif
@if($errors->any())<article>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</article>@endif
@if(!$profile)
@forelse($profiles as $item)<article><a href="{{ url('/api/v1/partner/admin-kyc/'.$item->id) }}">{{ $item->company_name ?: $item->contact_person ?: 'Partner #'.$item->id }}</a><p>{{ $item->mobile }} · {{ $item->partner_type }} · Account: {{ $item->verification_status }}</p></article>@empty<p>No partner KYC documents uploaded yet.</p>@endforelse
<p>Page {{ $profiles->currentPage() }} of {{ $profiles->lastPage() }}</p>
@if($profiles->previousPageUrl())<a href="{{ $profiles->previousPageUrl() }}">Previous</a>@endif
@if($profiles->nextPageUrl())<a href="{{ $profiles->nextPageUrl() }}">Next</a>@endif
@else
<p><a href="{{ url('/api/v1/partner/admin-kyc') }}">All submissions</a></p>
<h2>{{ $profile->company_name ?: $profile->contact_person }}</h2><p>{{ $profile->mobile }} · Account: {{ $profile->verification_status }}</p>
@forelse($documents as $document)
<article><h2>{{ strtoupper($document['type']) }}</h2><p>Number: {{ $document['record']->document_number }}</p><p>Status: {{ $document['record']->status }} · Uploaded/updated: {{ $document['record']->updated_at }}</p>
<a target="_blank" rel="noopener" href="{{ url('/api/v1/partner/admin-kyc/'.$profile->id.'/'.$document['type'].'/download') }}">Open private document</a>
<form method="post" action="{{ url('/api/v1/partner/admin-kyc/'.$profile->id.'/'.$document['type'].'/review') }}">@csrf
<input type="hidden" name="revision" value="{{ $document['revision'] }}">
<label>Decision <select name="status" required><option value="">Choose</option><option value="verified">Verify document</option><option value="rejected">Reject document</option></select></label>
<label>Review note (required for rejection)<textarea name="remarks" maxlength="1000">{{ $document['record']->remarks }}</textarea></label>
<button type="submit">Save review</button></form></article>
@empty<p>No documents uploaded.</p>@endforelse
@endif
</body></html>
