@extends('layouts.master')

@section('content')
<section>
    <div class="row">
        <div class="small-12 columns">
            <h1>Begin OAuth Authentication Flow</h1>
            @if (count($errors) > 0 || session('message'))
            <div data-alert class="alert-box warning">
                <ul>
                    @if (session('message'))
                    <li>{{ session('message') }}</li>
                    @endif
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form method="post" action="{{ route('auth.redirect') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <label>provider</label>
                <select name="protocol_provider">
                    @foreach ($protocols as $group => $providers)
                    @if (!empty($providers))
                    <optgroup label="{{ $group }}">
                    @foreach ($providers as $provider)
                    <option value="{{ $group . ':' . $provider }}"@if (($group . ':' . $provider) == old('protocol_provider'))selected="selected"@endif>{{ $provider }}</option>
                    @endforeach
                    </optgroup>
                    @endif
                    @endforeach
                </select>
                <label>client key</label>
                <input type="text" name="key" value="{{ old('key') }}" />
                <label>client secret</label>
                <input type="text" name="secret" value="{{ old('secret') }}" />
                <label>scopes (comma separated)</label>
                <input type="text" name="scopes" value="{{ old('scopes') }}" />
                <input type="submit" value="begin" class="button" />
            </form>
        </div>
    </div>
</section>
@endsection
