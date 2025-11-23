@extends('internal.layout')

@section('title', 'Quản lý phòng chiếu')

@section('head')
    <script type="module" src="{{$_ENV['URL_INTERNAL_BASE']}}/js/phong-chieu.js"></script>
    <style>
        /* Room type and status badges */
        .room-type-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        .room-type-badge.type-2d {
            @apply bg-blue-100 text-blue-800;
        }
        .room-type-badge.type-3d {
            @apply bg-green-100 text-green-800;
        }
        .room-type-badge.type-imax-2d {
            @apply bg-purple-100 text-purple-800;
        }
        .room-type-badge.type-imax-3d {
            @apply bg-indigo-100 text-indigo-800;
        }
        .status-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        .status-badge.active {
            @apply bg-green-100 text-green-800;
        }
        .status-badge.inactive {
            @apply bg-red-100 text-red-800;
        }
        .status-badge.maintenance {
            @apply bg-yellow-100 text-yellow-800;
        }

        /* Improved Seat Layout Styling */
        .seat-layout-container {
            @apply relative bg-gray-900 rounded-md shadow p-4 mb-6;
            max-height: 600px;
            overflow: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        /* Improved rectangular screen with border and centered text */
        .screen {
            width: 100%;
            max-width: 700px; /* Match the width of your seat layout */
            margin: 0 auto 20px;
            background-image: linear-gradient(to bottom, #444, #222);
            height: 3.5rem;
            border: 2px solid #444;
            border-bottom: 4px solid #666;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .screen-text {
            @apply text-center text-base font-medium;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
            display: block;
            width: 100%;
            letter-spacing: 1px;
            font-size: 1rem;
        }
        
        /* Seat grid with centered alignment */
        .seat-grid {
            @apply mx-auto;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: transparent;
            padding: 20px;
            border-radius: 8px;
            position: relative; /* Add position relative for absolute positioning of row labels */
        }
        
        /* Create a distinct area for column headers like in your image */
        .column-headers {
            display: flex;
            justify-content: center;
            margin-bottom: 8px;
            background-color: #f0f0f0; /* Light background to stand out */
            padding: 5px;
            border-radius: 5px 5px 0 0;
            border: 1px solid #ddd;
            width: auto;
            min-width: 500px;
        }

        .column-header {
            @apply flex items-center justify-center text-sm font-medium;
            width: 38px;
            height: 24px;
            margin: 0 4px;
            color: #333; /* Darker text for better visibility */
            font-weight: bold;
        }
        
        /* Create a grid layout container to align everything */
        .seating-area-container {
            display: flex;
            margin-top: 10px;
            justify-content: center;
            width: 100%;
        }

        /* Create a distinct area for row labels like in your image */
        .row-labels-container {
            display: flex;
            flex-direction: column;
            background-color: #f0f0f0; /* Light background to stand out */
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            padding: 5px 10px 5px 5px;
            margin-right: 10px;
        }

        .row-label {
            @apply flex items-center justify-center font-medium;
            width: 30px;
            height: 38px;
            margin: 2px 0;
            color: #333; /* Darker text for better visibility */
            font-weight: bold;
        }
        
        /* Center seat rows */
        .seat-row {
            display: flex;
            align-items: center;
            margin-bottom: 2px;
            justify-content: flex-start;
        }
        
        /* Better seat styling with consistent spacing */
        .seat {
            @apply flex items-center justify-center cursor-pointer text-xs font-medium;
            width: 38px;
            height: 38px;
            margin: 0 4px;
            transition: all 0.15s ease;
            border-radius: 4px;
            position: relative;
        }
        
        .seat:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 10px rgba(255,255,255,0.15);
        }
        
        /* Match colors exactly from the reference image */
        .seat.regular {
            background-color: #B8B8B8;
            color: #333;
        }
        
        .seat.vip {
            background-color: #9C182F; /* Adjusted to match reference image */
            color: white;
        }
        
        .seat.premium {
            background-color: #D35D89; /* Adjusted to match reference image */
            color: white;
        }
        
        .seat.sweet-box {
            background-color: #E91E63;
            color: white;
            width: 76px; /* Make sweet box seats wider */
        }
        
        .seat.empty {
            @apply bg-transparent border border-dashed border-gray-700 text-gray-500;
            box-shadow: none;
        }
        
        /* Add animation for selected seats */
        .seat.selected {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        /* Highlight seats during selection */
        .seat.highlight-seat {
            box-shadow: 0 0 0 2px #4CAF50, 0 0 10px #4CAF50;
        }
        
        /* Seat change animation */
        .seat-change-animation {
            animation: seatChange 0.5s ease;
        }
        
        @keyframes seatChange {
            0% {
                transform: scale(0.9);
                opacity: 0.7;
            }
            50% {
                transform: scale(1.1);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Pulse animation for feedback */
        .pulse-once {
            animation: pulseOnce 0.5s ease;
        }
        
        @keyframes pulseOnce {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        /* Seat Type Selection Table */
        .seat-type-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .seat-type-table th, .seat-type-table td {
            padding: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .seat-type-table th {
            background-color: #f9fafb;
            font-weight: 500;
            font-size: 14px;
        }
        
        .seat-type-table .color-cell {
            height: 30px;
            border: 1px solid #e5e7eb;
        }
        
        .seat-type-table .color-cell.regular {
            background-color: #B8B8B8;
        }
        
        .seat-type-table .color-cell.vip {
            background-color: #D35D89;
        }
        
        .seat-type-table .color-cell.premium {
            background-color: #9C182F;
        }
        
        .seat-type-table .color-cell.sweet-box {
            background-color: #E91E63;
        }
        
        .seat-type-table .color-cell.empty {
            background-color: transparent;
            border: 1px dashed #6b7280;
        }
        
        .seat-type-table th.active, .seat-type-table td.active {
            position: relative;
            box-shadow: inset 0 0 0 2px #3b82f6;
        }
        
        .seat-type-table th.active::after {
            content: '✓';
            display: block;
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 10px;
            color: #3b82f6;
        }
        
        /* Keep the existing seat type buttons for compatibility but hide them visually */
        .seat-type-options {
            display: none;
        }

        /* Hide layout elements until created */
        .layout-elements-container {
            display: none;
        }

        .layout-elements-container.visible {
            display: block;
            width: 100%;
        }

        /* Modal improvements for better fit */
        .modal-content-area {
            max-height: calc(100vh - 240px); /* Reduce height to avoid touching header */
            overflow-y: auto !important; /* Use auto instead of scroll and make it important */
            padding-right: 10px; /* Increase padding for scrollbar */
        }

        /* For webkit browsers like Chrome/Safari */
        .modal-content-area::-webkit-scrollbar {
            width: 8px;
            display: block !important; /* Force display of scrollbar */
        }

        .modal-content-area::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }

        .modal-content-area::-webkit-scrollbar-thumb {
            background-color: #a0aec0; /* Darker color for better visibility */
            border-radius: 4px;
        }

        /* Make sure modals fit within viewport but don't change display property */
        .modal-dialog {
            max-height: 95vh; /* Tăng từ 85vh lên 95vh */
            margin: 40px auto 20px;
            position: relative;
            z-index: 50; /* Lower z-index than the background overlay */
        }

        /* Ensure the background overlay covers everything including the top header */
        .fixed.inset-0.bg-gray-500.bg-opacity-75 {
            z-index: 40; /* High enough to cover the site header */
        }

        /* Ensure modals are above the overlay */
        #modal-add-room, #modal-edit-room, #modal-status-change {
            z-index: 50; /* Higher than background overlay */
        }

        /* Target the site header specifically - this selector would need to match your actual header */
        header, .site-header, nav.main-nav {
            z-index: 30; /* Lower than the background overlay */
            position: relative;
        }

        /* Keep the modal positioned correctly */
        .modal-dialog {
            position: relative;
            z-index: 50; /* Same as the modal container */
            margin-top: 60px; /* Provide some space from the top */
        }

        /* Remove the previous styles that were positioning the content below the header */
        .flex.items-center.justify-center.min-h-screen {
            padding-top: 0; /* Remove the extra padding we added earlier */
        }

        /* Position the background overlay with proper z-index */
        .fixed.inset-0.bg-gray-500.bg-opacity-75 {
            z-index: 40; /* High enough to cover the site header */
        }

        /* Ensure modals are above the overlay */
        #modal-add-room, #modal-edit-room, #modal-status-change {
            z-index: 50; /* Higher than background overlay */
        }

        /* Target the site header specifically - this selector would need to match your actual header */
        header, .site-header, nav.main-nav {
            z-index: 30; /* Lower than the background overlay */
            position: relative;
        }

        /* Keep the modal positioned correctly */
        .modal-dialog {
            position: relative;
            z-index: 50; /* Same as the modal container */
            margin-top: 60px; /* Provide some space from the top */
        }

        /* Remove the previous styles that were positioning the content below the header */
        .flex.items-center.justify-center.min-h-screen {
            padding-top: 0; /* Remove the extra padding we added earlier */
        }

        /* Specific fix for the status change modal to appear above the background */
        #modal-status-change .bg-white.rounded-lg {
            position: relative;
            z-index: 60; /* Higher than the modal container and background */
        }

        /* Make sure the status change modal is properly positioned */
        #modal-status-change .flex.items-end {
            align-items: center !important; /* Center vertically instead of at the bottom */
        }

        /* Adjust the background overlay specifically for status change modal */
        #modal-status-change .fixed.inset-0.bg-gray-500.bg-opacity-75 {
            z-index: 55; /* Between main modal z-index (50) and dialog z-index (60) */
        }

        /* Make status change modal a bit more compact */
        #modal-status-change .inline-block.align-bottom {
            margin-top: 20vh; /* Push it down from the top a bit */
            max-width: 500px; /* Limit width for better appearance */
        }

        /* Enhanced Modal Styling */
        .modal-dialog {
            animation: modalFadeIn 0.3s ease-out;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Enhanced Modal Header */
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 3px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        #modal-add-room .modal-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        #modal-edit-room .modal-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .modal-header h3 {
            color: white;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .modal-header .flex-shrink-0 {
            background: rgba(255, 255, 255, 0.2) !important;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .modal-header svg {
            color: white !important;
        }

        /* Close Button */
        .modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-close-btn svg {
            width: 1.5rem;
            height: 1.5rem;
            color: white;
        }

        /* Enhanced Form Inputs */
        .form-input-group {
            position: relative;
        }

        .form-input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
            z-index: 10;
        }

        .form-input-icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .form-input-with-icon {
            padding-left: 2.75rem !important;
        }

        .form-input-with-icon:focus + .form-input-icon,
        .form-input-with-icon:not(:placeholder-shown) + .form-input-icon {
            color: #ef4444;
        }

        /* Enhanced Select Styling */
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        select:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23ef4444' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        }

        /* Enhanced Input Focus States */
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
            transform: translateY(-1px);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-header-icon {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.5rem;
            color: white;
        }

        .section-header-icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .section-header h4 {
            margin: 0;
            font-weight: 600;
            color: #1f2937;
        }

        /* Enhanced Buttons */
        .modal-footer button {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .modal-footer button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .modal-footer button:hover::before {
            width: 300px;
            height: 300px;
        }

        .modal-footer button span {
            position: relative;
            z-index: 1;
        }

        /* Layout Controls Enhancement */
        .layout-controls {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .dimension-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .dimension-control {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .dimension-control label {
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .dimension-control input {
            padding: 0.625rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .dimension-control input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .preset-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .preset-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .preset-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }

        /* Helper Text Styling */
        .helper-text {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }

        .helper-text p {
            margin: 0;
            color: #1e40af;
            font-size: 0.875rem;
        }

        .helper-text p + p {
            margin-top: 0.5rem;
        }

        /* Current Type Message */
        .current-type-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            font-size: 0.875rem;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.2);
        }

        /* Enhanced Modal Content Area */
        .modal-content-area {
            background: #ffffff;
        }

        /* Form Container */
        .modal-form-container {
            padding: 1.5rem 0;
        }

        /* Responsive Improvements */
        @media (max-width: 640px) {
            .modal-dialog {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }

            .dimension-controls {
                flex-direction: column;
            }

            .preset-buttons {
                flex-direction: column;
            }

            .preset-btn,
            #btn-generate-layout,
            #btn-edit-generate-layout {
                width: 100%;
            }
        }

        /* Loading State for Buttons */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 1rem;
            height: 1rem;
            top: 50%;
            left: 50%;
            margin-left: -0.5rem;
            margin-top: -0.5rem;
            border: 2px solid currentColor;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endsection

@section('breadcrumbs')
<li>
    <div class="flex items-center">
        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <span class="ml-1 text-gray-500 hover:text-gray-700 text-sm font-medium">Quản lý phòng chiếu</span>
    </div>
</li>
@endsection

@section('content')
    <!-- Page header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Quản lý phòng chiếu</h1>
        <button type="button" id="btn-add-room" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Thêm phòng chiếu mới
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6 mb-6">
        <div class="md:flex md:items-center md:justify-between">
            <div class="flex-1 min-w-0">
                <h2 class="text-lg leading-6 font-medium text-gray-900 mb-4">Bộ lọc</h2>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
            <div class="sm:col-span-2">
                <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select id="filter-status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="1">Đang hoạt động</option>
                    <option value="0">Đang bảo trì</option>
                    <option value="-1">Ngưng hoạt động</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="filter-type" class="block text-sm font-medium text-gray-700 mb-1">Loại phòng</label>
                <select id="filter-type" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                    <option value="all">Tất cả loại phòng</option>
                    <option value="2D">2D</option>
                    <option value="3D">3D</option>
                    <option value="IMAX 2D">IMAX 2D</option>
                    <option value="IMAX 3D">IMAX 3D</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="filter-search" class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <div class="flex rounded-md shadow-sm">
                    <input type="text" id="filter-search" class="focus:ring-red-500 focus:border-red-500 flex-1 block w-full rounded-md sm:text-sm border-gray-300" placeholder="Tên hoặc mã phòng...">
                </div>
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button type="button" id="btn-apply-filters" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                </svg>
                Áp dụng bộ lọc
            </button>
        </div>
    </div>

    <!-- Cinema screens list -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg" id="box-list" style="min-height: 300px;">
        <ul class="divide-y divide-gray-200" id="rooms-list" data-url="{{$_ENV['URL_WEB_BASE']}}">
            <li class="px-6 py-4 flex items-center justify-center">
                <div class="flex items-center text-gray-500">
                    
                </div>
            </li>
        </ul>
    </div>

    <!-- Add room modal -->
    <div id="modal-add-room" class="fixed inset-0 overflow-hidden hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full modal-dialog">
                <div class="bg-white px-4 pt-5 pb-2 sm:p-6 sm:pb-3 modal-header">
                    <div class="sm:flex sm:items-start relative">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Thêm phòng chiếu mới
                            </h3>
                            <p class="mt-1 text-sm text-gray-300">Điền thông tin để tạo phòng chiếu mới</p>
                        </div>
                        <button type="button" class="modal-close-btn btn-cancel" aria-label="Đóng">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="modal-content-area">
                    <div class="px-4 sm:px-6">
                        <form id="form-add-room" class="modal-form-container">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                <!-- Basic Information Section -->
                                <div class="sm:col-span-6">
                                    <div class="section-header">
                                        <div class="section-header-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <h4 class="text-md font-medium text-gray-900">
                                            Thông tin cơ bản
                                        </h4>
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="room-name" class="block text-sm font-medium text-gray-700 mb-1">Tên phòng chiếu <span class="text-red-600">*</span></label>
                                    <div class="form-input-group">
                                        <input type="text" name="name" id="room-name" class="form-input-with-icon block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" placeholder="Ví dụ: Phòng 1" required>
                                        <div class="form-input-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="name-error"></p>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="room-code" class="block text-sm font-medium text-gray-700 mb-1">Mã phòng <span class="text-red-600">*</span></label>
                                    <div class="form-input-group">
                                        <input type="text" name="code" id="room-code" class="form-input-with-icon block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" placeholder="Ví dụ: P001" required>
                                        <div class="form-input-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="code-error"></p>
                                </div>
                                <div class="sm:col-span-6">
                                    <label for="room-description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                    <textarea name="description" id="room-description" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" placeholder="Nhập mô tả về phòng chiếu (tùy chọn)"></textarea>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="description-error"></p>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="room-type" class="block text-sm font-medium text-gray-700 mb-1">Loại phòng chiếu <span class="text-red-600">*</span></label>
                                    <select name="type" id="room-type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                                        <option value="">-- Chọn loại phòng --</option>
                                        <option value="2D">2D</option>
                                        <option value="3D">3D</option>
                                        <option value="IMAX 2D">IMAX 2D</option>
                                        <option value="IMAX 3D">IMAX 3D</option>
                                    </select>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="type-error"></p>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="room-status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái <span class="text-red-600">*</span></label>
                                    <select name="status" id="room-status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                                        <option value="1">Đang hoạt động</option>
                                        <option value="0">Đang bảo trì</option>
                                        <option value="-1">Ngưng hoạt động</option>
                                    </select>
                                </div>
                                
                                <!-- Seat Layout Section -->
                                <div class="sm:col-span-6 mt-4">
                                    <div class="section-header">
                                        <div class="section-header-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                        </div>
                                        <h4 class="text-md font-medium text-gray-900">
                                            Sơ đồ ghế
                                        </h4>
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-6">
                                    <div class="layout-controls">
                                        <div class="dimension-controls">
                                            <div class="dimension-control">
                                                <label for="seat-rows">Số hàng:</label>
                                                <input type="number" id="seat-rows" min="1" max="26" value="10" class="focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                            <div class="dimension-control">
                                                <label for="seat-columns">Số cột:</label>
                                                <input type="number" id="seat-columns" min="1" max="20" value="10" class="focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                        </div>
                                        <div class="preset-buttons">
                                            <button type="button" class="preset-btn" data-preset="small">Phòng nhỏ</button>
                                            <button type="button" class="preset-btn" data-preset="medium">Phòng vừa</button>
                                            <button type="button" class="preset-btn" data-preset="large">Phòng lớn</button>
                                            <button type="button" id="btn-generate-layout" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm font-medium">
                                                <span>Tạo sơ đồ</span>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="seat-type-bar">
                                        <span class="seat-type-title font-medium text-gray-700 mb-2 block">Chọn loại ghế:</span>
                                        
                                        <!-- New seat type table -->
                                        <table class="seat-type-table">
                                            <!-- <tr>
                                                <th class="seat-type-cell active" data-type="regular">Ghế thường</th>
                                                <th class="seat-type-cell" data-type="vip">Ghế VIP</th>
                                                <th class="seat-type-cell" data-type="premium">Ghế Premium</th>
                                                <th class="seat-type-cell" data-type="sweet-box">Sweet Box</th>
                                                <th class="seat-type-cell" data-type="empty">Trống</th>
                                            </tr>
                                            <tr>
                                                <td class="color-cell regular active" data-type="regular"></td>
                                                <td class="color-cell vip" data-type="vip"></td>
                                                <td class="color-cell premium" data-type="premium"></td>
                                                <td class="color-cell sweet-box" data-type="sweet-box"></td>
                                                <td class="color-cell empty" data-type="empty"></td>
                                            </tr> -->
                                        </table>
                                        
                                        <!-- Keep the original buttons but hidden for JS compatibility -->
                                        <div class="seat-type-options">
                                            <button type="button" class="seat-type-btn regular active" data-type="regular">Ghế thường</button>
                                            <button type="button" class="seat-type-btn vip" data-type="vip">Ghế VIP</button>
                                            <button type="button" class="seat-type-btn premium" data-type="premium">Ghế Premium</button>
                                            <button type="button" class="seat-type-btn sweet-box" data-type="sweet-box">Sweet Box</button>
                                            <button type="button" class="seat-type-btn empty" data-type="empty">Trống</button>
                                        </div>
                                    </div>
                                    
                                    <div class="seat-layout-container">
                                        <div class="screen" style="margin-top: 10px;">
                                            <span class="screen-text" style="text-align: center;">MÀN HÌNH</span>
                                        </div>
                                        <div class="column-headers" id="column-headers">
                                            <!-- Column headers will be generated here -->
                                        </div>
                                        <div class="seat-grid" id="seat-layout">
                                            <!-- Seat layout will be generated here -->
                                            <div class="p-10 text-center text-gray-500 bg-gray-800 rounded">
                                                Vui lòng nhấn "Tạo sơ đồ" để tạo bố cục ghế ngồi
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Remove the seat-legend div completely -->
                                    <input type="hidden" id="seat-layout-data" name="seat_layout">

                                    <!-- Enhance feedback element to be more visible -->
                                    <div class="current-type-message">
                                        <strong>Loại ghế đang chọn:</strong> <span class="text-blue-600 font-bold">Ghế thường</span>
                                    </div>
                                    <div class="seat-change-feedback hidden"></div>

                                    <div class="helper-text">
                                        <p>Nhấp vào ghế để thay đổi loại ghế theo loại đã chọn ở trên.</p>
                                        <p class="mt-1"><strong>Mẹo:</strong> Chọn "Trống" để tạo lối đi hoặc không gian giữa các ghế.</p>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="seat-layout-error"></p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse modal-footer border-t border-gray-200">
                    <button type="button" id="btn-submit-add" class="w-full inline-flex items-center justify-center rounded-md border border-transparent shadow-sm px-6 py-2.5 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300">
                        <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Thêm phòng chiếu</span>
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex items-center justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300">
                        <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Hủy bỏ</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit room modal -->
    <div id="modal-edit-room" class="fixed inset-0 overflow-hidden hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full modal-dialog">
                <div class="bg-white px-4 pt-5 pb-2 sm:p-6 sm:pb-3 modal-header">
                    <div class="sm:flex sm:items-start relative">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Chỉnh sửa phòng chiếu
                            </h3>
                            <p class="mt-1 text-sm text-gray-300">Cập nhật thông tin phòng chiếu</p>
                        </div>
                        <button type="button" class="modal-close-btn btn-cancel" aria-label="Đóng">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="modal-content-area">
                    <div class="px-4 sm:px-6">
                        <form id="form-edit-room" class="modal-form-container">
                            <input type="hidden" id="edit-room-id">
                            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                                <!-- Basic Information Section -->
                                <div class="sm:col-span-6">
                                    <div class="section-header">
                                        <div class="section-header-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <h4 class="text-md font-medium text-gray-900">
                                            Thông tin cơ bản
                                        </h4>
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <label for="edit-room-name" class="block text-sm font-medium text-gray-700 mb-1">Tên phòng chiếu <span class="text-red-600">*</span></label>
                                    <div class="form-input-group">
                                        <input type="text" name="name" id="edit-room-name" class="form-input-with-icon block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Ví dụ: Phòng 1" required>
                                        <div class="form-input-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="edit-name-error"></p>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="edit-room-code" class="block text-sm font-medium text-gray-700 mb-1">Mã phòng <span class="text-red-600">*</span></label>
                                    <div class="form-input-group">
                                        <input type="text" name="code" id="edit-room-code" class="form-input-with-icon block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Ví dụ: P001" required>
                                        <div class="form-input-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="edit-code-error"></p>
                                </div>
                                <div class="sm:col-span-6">
                                    <label for="edit-room-description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                    <textarea name="description" id="edit-room-description" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Nhập mô tả về phòng chiếu (tùy chọn)"></textarea>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="edit-description-error"></p>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="edit-room-type" class="block text-sm font-medium text-gray-700 mb-1">Loại phòng chiếu <span class="text-red-600">*</span></label>
                                    <select name="type" id="edit-room-type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                                        <option value="">-- Chọn loại phòng --</option>
                                        <option value="2D">2D</option>
                                        <option value="3D">3D</option>
                                        <option value="IMAX 2D">IMAX 2D</option>
                                        <option value="IMAX 3D">IMAX 3D</option>
                                    </select>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="edit-type-error"></p>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="edit-room-status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái <span class="text-red-600">*</span></label>
                                    <select name="status" id="edit-room-status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                                        <option value="1">Đang hoạt động</option>
                                        <option value="0">Đang bảo trì</option>
                                        <option value="-1">Ngưng hoạt động</option>
                                    </select>
                                </div>
                                
                                <!-- Seat Layout Section -->
                                <div class="sm:col-span-6 mt-4">
                                    <div class="section-header">
                                        <div class="section-header-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                        </div>
                                        <h4 class="text-md font-medium text-gray-900">
                                            Sơ đồ ghế
                                        </h4>
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-6">
                                    <div class="layout-controls">
                                        <div class="dimension-controls">
                                            <div class="dimension-control">
                                                <label for="edit-seat-rows">Số hàng:</label>
                                                <input type="number" id="edit-seat-rows" min="1" max="26" value="10" class="focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                            <div class="dimension-control">
                                                <label for="edit-seat-columns">Số cột:</label>
                                                <input type="number" id="edit-seat-columns" min="1" max="20" value="10" class="focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                        </div>
                                        <div class="preset-buttons">
                                            <button type="button" class="preset-btn" data-preset="small">Phòng nhỏ</button>
                                            <button type="button" class="preset-btn" data-preset="medium">Phòng vừa</button>
                                            <button type="button" class="preset-btn" data-preset="large">Phòng lớn</button>
                                            <button type="button" id="btn-edit-generate-layout" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-sm font-medium">
                                                <span>Tạo lại sơ đồ</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="seat-type-bar">
                                        <span class="seat-type-title font-medium text-gray-700 mb-2 block">Chọn loại ghế:</span>
                                        
                                        <!-- New seat type table -->
                                        <table class="seat-type-table">
                                            <tr>
                                                <th class="seat-type-cell active" data-type="regular">Ghế thường</th>
                                                <th class="seat-type-cell" data-type="vip">Ghế VIP</th>
                                                <th class="seat-type-cell" data-type="premium">Ghế Premium</th>
                                                <th class="seat-type-cell" data-type="sweet-box">Sweet Box</th>
                                                <th class="seat-type-cell" data-type="empty">Trống</th>
                                            </tr>
                                            <tr>
                                                <td class="color-cell regular active" data-type="regular"></td>
                                                <td class="color-cell vip" data-type="vip"></td>
                                                <td class="color-cell premium" data-type="premium"></td>
                                                <td class="color-cell sweet-box" data-type="sweet-box"></td>
                                                <td class="color-cell empty" data-type="empty"></td>
                                            </tr>
                                        </table>
                                        
                                        <!-- Keep the original buttons but hidden for JS compatibility -->
                                        <div class="seat-type-options">
                                            <button type="button" class="seat-type-btn regular active" data-type="regular">Ghế thường</button>
                                            <button type="button" class="seat-type-btn vip" data-type="vip">Ghế VIP</button>
                                            <button type="button" class="seat-type-btn premium" data-type="premium">Ghế Premium</button>
                                            <button type="button" class="seat-type-btn sweet-box" data-type="sweet-box">Sweet Box</button>
                                            <button type="button" class="seat-type-btn empty" data-type="empty">Trống</button>
                                        </div>
                                    </div>
                                    
                                    <div class="seat-layout-container">
                                        <div class="screen">
                                            <span style="text-align: center;" class="screen-text">MÀN HÌNH</span>
                                        </div>
                                        <div class="column-headers" id="edit-column-headers">
                                            <!-- Column headers will be generated here -->
                                        </div>
                                        <div class="seat-grid" id="edit-seat-layout">
                                            <!-- Seat layout will be generated here -->
                                        </div>
                                    </div>
                                    <!-- Remove the seat-legend div completely -->
                                    <input type="hidden" id="edit-seat-layout-data" name="seat_layout">

                                    <!-- Enhance feedback element to be more visible -->
                                    <div class="current-type-message">
                                        <strong>Loại ghế đang chọn:</strong> <span class="text-blue-600 font-bold">Ghế thường</span>
                                    </div>
                                    <div class="seat-change-feedback hidden"></div>

                                    <div class="helper-text">
                                        <p>Nhấp vào ghế để thay đổi loại ghế theo loại đã chọn ở trên.</p>
                                        <p class="mt-1"><strong>Mẹo:</strong> Chọn "Trống" để tạo lối đi hoặc không gian giữa các ghế.</p>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="edit-seat-layout-error"></p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse modal-footer border-t border-gray-200">
                    <button type="button" id="btn-submit-edit" class="w-full inline-flex items-center justify-center rounded-md border border-transparent shadow-sm px-6 py-2.5 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300">
                        <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Lưu thay đổi</span>
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex items-center justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300">
                        <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Hủy bỏ</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status change confirmation modal -->
    <div id="modal-status-change" class="fixed inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="status-change-title">
                                Xác nhận thay đổi trạng thái
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="status-change-message">
                                    Bạn có chắc chắn muốn thay đổi trạng thái của phòng chiếu này không?
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="btn-confirm-status-change" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Xác nhận
                    </button>
                    <button type="button" class="btn-cancel mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy bỏ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast-notification" class="fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg transform transition-all duration-300 translate-y-20 opacity-0 z-50 flex items-center">
        <span class="text-white"></span>
    </div>
@endsection