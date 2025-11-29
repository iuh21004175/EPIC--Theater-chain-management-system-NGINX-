@extends('internal.layout')

@section('title', 'Duyệt suất chiếu')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/duyet-suat-chieu.js"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .cinema-card {
            animation: fadeIn 0.5s ease-out forwards;
            animation-delay: calc(var(--card-index) * 0.1s);
            opacity: 0;
        }
        
        .cinema-card:hover {
            transform: translateY(-4px);
        }
    </style>
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
<div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-2xl shadow-xl p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-3 mb-2">
            <div class="w-1.5 h-12 bg-gradient-to-b from-blue-500 to-indigo-600 rounded-full"></div>
            <h2 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                Danh sách rạp phim
            </h2>
        </div>
        <p class="text-sm text-gray-600 ml-5 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Chọn rạp để xem và duyệt suất chiếu
        </p>
    </div>
    
    <!-- Cinema Grid -->
    <div id="cinema-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-url="{{$_ENV['URL_WEB_BASE']}}">
        <!-- Loading State -->
        <div class="col-span-full flex items-center justify-center py-20">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-2xl mb-4 animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                </div>
                <p class="text-gray-600 font-medium">Đang tải danh sách rạp...</p>
            </div>
        </div>
    </div>
</div>
@endsection