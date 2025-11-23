@extends('internal.layout')

@section('title', 'Duyệt suất chiếu')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/duyet-suat-chieu.js"></script>
@endsection
@section('breadcrumb')
   @section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Danh sách rạp</span>
    </div>
</li>
@endsection
@endsection
@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-xl font-bold mb-4">Danh sách rạp phim</h2>
    <div id="cinema-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-url="{{$_ENV['URL_WEB_BASE']}}">
        <!-- Danh sách rạp sẽ được render ở đây -->
    </div>
</div>
@endsection