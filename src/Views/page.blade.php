@extends('site::layout')

@section('content')
    {{-- Content --}}
    <h1>{{ $model->name }}</h1>
    {!! \Modules\Opx\MarkUp\OpxMarkUp::parse($model->content) !!}
    {{-- End of content --}}
@endsection