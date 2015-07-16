@extends('layouts.master')

@section('content')
<section>
    <div class="row">
        <div class="small-12 columns">
            <h1>OAuth Identity</h1>
            <div class="panel">
                <pre>{{ print_r($identity) }}</pre>
            </div>
        </div>
    </div>
</section>
@endsection
