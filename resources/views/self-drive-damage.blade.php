<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Self Drive Damage Review</title>
<style>body{font:16px system-ui;background:#f3f5f8;color:#17223b;margin:0}main{max-width:1100px;margin:30px auto;padding:24px}article,section{background:white;border-radius:12px;padding:20px;margin-bottom:20px}a{color:#184cb6}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}img{width:100%;height:270px;object-fit:contain;background:#eef1f4}p{white-space:pre-wrap}.tag{font-weight:bold;color:#92400e}</style></head><body><main>
<h1>Self Drive Damage Review</h1>
@if(isset($reports))
@forelse($reports as $row)<article><a href="{{ route('self-drive-damage.show',$row->id) }}">Booking #{{ $row->booking_id }} · Report #{{ $row->id }}</a><p>{{ str_replace('_',' ',$row->status) }} · {{ $row->created_at }}</p></article>@empty<p>No reports yet.</p>@endforelse
{{ $reports->links() }}
@else
<a href="{{ route('self-drive-damage.index') }}">All reports</a><h2>Booking #{{ $report->booking_id }} · Report #{{ $report->id }}</h2>
<section><p class="tag">{{ str_replace('_',' ',$report->status) }}</p><p>AI assistance only. Review original inspection records and photos before deciding. No automatic charge or deduction.</p><p>{{ $result['summary'] ?? $report->error }}</p><p>{{ $result['limitations'] ?? '' }}</p><small>Model: {{ $report->model ?: 'not configured' }} · Created: {{ $report->created_at }}</small></section>
@foreach(($result['findings'] ?? []) as $finding)<article><h3>Possible new damage</h3><p>{{ $finding['description'] }}</p><p>Evidence: {{ $finding['before_id'] }} → {{ $finding['after_id'] }} · Model confidence: {{ round($finding['confidence']*100) }}% (not calibrated)</p></article>@endforeach
@foreach(['BEFORE'=>'Pickup photos','AFTER'=>'Return photos'] as $phase=>$heading)<h2>{{ $heading }}</h2><div class="grid">
@foreach($manifest as $key=>$image)@if($image['phase']===$phase && isset($image['path']))<article><a href="{{ route('self-drive-damage.photo',[$report->id,$key]) }}" target="_blank" rel="noopener"><img src="{{ route('self-drive-damage.photo',[$report->id,$key]) }}" alt="{{ $key }}"></a><p>{{ $key }} · {{ $image['label'] }}</p></article>@endif@endforeach</div>@endforeach
@endif
</main></body></html>
